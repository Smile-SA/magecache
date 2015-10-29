<?php
/**
 * MageCache
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade MageCache to newer
 * versions in the future.
 */

/**
 * Page cache processor
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Processor
{
    /**
     * Cache storage id
     */
    const CACHE_TAGS_STORAGE_ID = 'SMILE_MAGECACHE_TAGS';

    /**
     * Lock identifier
     */
    const LOCK_NAME = 'SMILE_MAGECACHE_LOCK';

    /**
     * Exception blocks list
     *
     * @var array
     */
    protected $_cacheTagShortcuts = null;

    /**
     * Exception blocks list
     *
     * @var array
     */
    protected $_exceptionBlockList = null;

    /**
     * Exception blocks stack
     *
     * @var array
     */
    protected $_exceptionBlockStock = array();

    /**
     * Cache engines list
     *
     * @var array
     */
    protected $_engines = null;

    /**
     * Flag that indicates if at least one engine is enabled
     *
     * @var bool
     */
    protected $_isEnabled = null;

    /**
     * Check if connector is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        $this->unlock();
        return (Mage::app()->loadCache(self::LOCK_NAME) !== false);
    }

    /**
     * Lock connector
     *
     * @return void
     */
    public function lock()
    {
        Mage::app()->saveCache('LOCK', self::LOCK_NAME, array());
    }

    /**
     * Unlock connector
     *
     * @return void
     */
    public function unlock()
    {
        Mage::app()->removeCache(self::LOCK_NAME);
    }

    /**
     * Retrieve cache engines list
     *
     * @return array
     */
    public function getEngines()
    {
        if (is_null($this->_engines)) {
            $this->_engines = array();
            $path = 'global/smile_magecache/engines';
            $engines = array();
            foreach (Mage::app()->getConfig()->getNode($path)->children() as $node) {
                $engines[(string)$node->order] = (string)$node->model;
            }
            ksort($engines);
            foreach ($engines as $order => $model) {
                $obj = new stdClass();
                $obj->instance = Mage::getSingleton($model);
                $obj->order = $order;
                if ($node->config) {
                    $obj->instance->setConfig($node->config);
                }
                $this->_engines[] = $obj;
            }
        }
        return $this->_engines;
    }

    /**
     * Check if at least one cache engine is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (is_null($this->_isEnabled)) {
            $this->_isEnabled = false;
            foreach ($this->getEngines() as $engine) {
                $this->_isEnabled = true;
                break;
            }
        }
        return $this->_isEnabled;
    }

    /**
     * Clean page cache
     *
     * @return void
     */
    public function cleanCache()
    {
        $tagsToClean = Mage::helper('smile_magecache')->getTagsToClean();
        if (empty($tagsToClean)) {
            return;
        }
        foreach ($tagsToClean as &$tag) {
            $tag = $this->_getCacheTagShortcut($tag);
        }
        unset($tag);
        if (!Mage::getStoreConfig('smile_magecache/general/asynchronous_flush')) {
            try {
                $this->purgeTags($tagsToClean);
            } catch (Smile_MageCache_Model_Exception $e) {
                Mage::log(
                    Mage::helper('smile_magecache')->__('Error during cache clean. Reason: %s', $e->getMessage()),
                    Zend_Log::ERR
                );
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            $this->addTagsToQueue($tagsToClean);
        }
    }

    /**
     * Purge page cache by specified tags
     *
     * @param array $tags tags list
     *
     * @return void
     * @throws Smile_MageCache_Model_Exception
     */
    public function purgeTags(array $tags)
    {
        if ($this->isLocked()) {
            throw new Smile_MageCache_Model_Exception(
                Mage::helper('smile_magecache')->__('Processor is locked')
            );
        }
        try {
            $this->lock();
            foreach ($this->getEngines() as $engine) {
                if ($engine->instance->isEnabled()) {
                    $engine->instance->purgeTags($tags);
                }
            }
            $this->removeTagsFromQueue($tags);
        } catch (Exception $e) {
            $this->unlock();
            throw $e;
        }
        $this->unlock();
    }

    /**
     * Purge page cache by given URL pattern
     *
     * @param array $pattern URL
     *
     * @return void
     * @throws Smile_MageCache_Model_Exception
     */
    public function purgeUrl($pattern)
    {
        if ($this->isLocked()) {
            throw new Smile_MageCache_Model_Exception(
                Mage::helper('smile_magecache')->__('Processor is locked')
            );
        }
        try {
            $this->lock();
            foreach ($this->getEngines() as $engine) {
                if ($engine->instance->isEnabled() && $engine->instance instanceof Smile_MageCache_Model_Engine_Feature_PurgeUrl) {
                    $engine->instance->purgeUrl($pattern);
                }
            }
        } catch (Exception $e) {
            $this->unlock();
            throw $e;
        }
        $this->unlock();
    }

    /**
     * Add cache tags from the given model
     *
     * @param Mage_Core_Model_Abstract $model source model
     *
     * @return void
     */
    public function addModelCacheTags(Mage_Core_Model_Abstract $model)
    {
        // Strange check, but it seems that it's the only way to see if $_cacheTag is set (and not just equal to "true")
        if ($model->getCacheTags() !== false && $model->getCacheTags() !== array()) {
            $tags = $model->getCacheIdTags();
            Mage::helper('smile_magecache')->addTags($tags);
        }
    }

    /**
     * Get tags from queue
     *
     * @return string
     */
    public function getTagsFromQueue()
    {
        $tags = Mage::app()->loadCache(self::CACHE_TAGS_STORAGE_ID);
        if (!empty($tags)) {
            $tags = unserialize($tags);
            if (!is_array($tags)) {
                $tags = array();
            }
        } else {
            $tags = array();
        }
        return $tags;
    }

    /**
     * Add tags to queue
     *
     * @param array $tags tags list
     *
     * @return void
     */
    public function addTagsToQueue(array $tags)
    {
        $oldTags = $this->getTagsFromQueue();
        $tags = array_unique(array_merge($tags, $oldTags));
        Mage::app()->saveCache(serialize($tags), self::CACHE_TAGS_STORAGE_ID);
    }

    /**
     * Remove tags from queue
     *
     * @param array $tags tags list
     *
     * @return void
     */
    public function removeTagsFromQueue(array $tags)
    {
        $oldTags = $this->getTagsFromQueue();
        $tags = array_diff($oldTags, array_intersect($tags, $oldTags));
        Mage::app()->saveCache(serialize($tags), self::CACHE_TAGS_STORAGE_ID);
    }

    /**
     * Get cache tag shotcuts list
     *
     * @return array
     */
    protected function _getCacheTagShortcuts()
    {
        if (is_null($this->_cacheTagShortcuts)) {
            $this->_cacheTagShortcuts = array();
            $path = Smile_MageCache_Model_Config::XML_PATH_CACHE_SHORTCUTS;
            foreach (Mage::app()->getConfig()->getNode($path)->children() as $node) {
                $this->_cacheTagShortcuts[(string)$node->source] = (string)$node->target;
            }
        }
        return $this->_cacheTagShortcuts;
    }

    /**
     * Get short version of cache tag if available
     *
     * @param string $tag source tag
     *
     * @return string
     */
    protected function _getCacheTagShortcut($tag)
    {
        $shortcuts = $this->_getCacheTagShortcuts();
        foreach ($shortcuts as $source => $target) {
            if (strpos($tag, $source) === 0) {
                return str_replace($source, $target, $tag);
            }
        }
        return $tag;
    }

    /**
     * Get exception blocks
     *
     * @return array
     */
    protected function _getExceptionBlockList()
    {
        if (is_null($this->_exceptionBlockList)) {
            $this->_exceptionBlockList = array();
            $path = 'frontend/smile_magecache/block_exceptions';
            foreach (Mage::app()->getConfig()->getNode($path)->children() as $node) {
                $block = Mage::getConfig()->getBlockClassName((string)$node->type);
                $callback = true;
                if (isset($node->callback)) {
                    $callback = new stdClass();
                    $callback->model = Mage::getSingleton((string)$node->callback->model);
                    $callback->method = (string)$node->callback->method;
                }
                $this->_exceptionBlockList[$block] = $callback;
            }
        }
        return $this->_exceptionBlockList;
    }

    /**
     * Check current block is in exception list and deactivate tags collection if necessary
     *
     * @param Mage_Core_Block_Abstract $block block
     *
     * @return void
     */
    public function checkBlockExceptionBeforeRender(Mage_Core_Block_Abstract $block)
    {
        $exceptionBlockList = $this->_getExceptionBlockList();
        if (isset($exceptionBlockList[get_class($block)])) {
            array_push($this->_exceptionBlockStock, get_class($block));
            Mage::helper('smile_magecache')->setCollectTags(false);
        }
    }

    /**
     * Check if exception is already rendered and reactivate tags collection if necessary
     *
     * @param Mage_Core_Block_Abstract $block block
     *
     * @return void
     */
    public function checkBlockExceptionAfterRender(Mage_Core_Block_Abstract $block)
    {
        if (empty($this->_exceptionBlockStock)) {
            return;
        }
        $lastBlock = $this->_exceptionBlockStock[count($this->_exceptionBlockStock) - 1];
        if ($lastBlock == get_class($block)) {
            array_pop($this->_exceptionBlockStock);
            Mage::helper('smile_magecache')->setCollectTags(true);
            $exceptionBlockList = $this->_getExceptionBlockList();
            if (is_object($exceptionBlockList[$lastBlock])) {
                call_user_func(array($exceptionBlockList[$lastBlock]->model, $exceptionBlockList[$lastBlock]->method), $block);
            }
            if (!empty($this->_exceptionBlockStock)) {
                Mage::helper('smile_magecache')->setCollectTags(false);
            }
        }
    }

    /**
     * Put tags and process response
     *
     * @param Zend_Controller_Response_Http $response response
     * @param Zend_Controller_Request_Http  $request  request
     *
     * @return void
     */
    public function processResponse(Zend_Controller_Response_Http $response, Zend_Controller_Request_Http $request)
    {
        if (!Mage::helper('smile_magecache')->isActive()) {
            return;
        }
        $tagsToPut = Mage::helper('smile_magecache')->getTagsToPut();
        foreach ($tagsToPut as &$tag) {
            foreach ($this->getEngines() as $engine) {
                if ($engine->instance->isEnabled()) {
                    $engine->instance->addRequestTag($this->_getCacheTagShortcut($tag));
                }
            }
        }
        foreach ($this->getEngines() as $engine) {
            if ($engine->instance->isEnabled()) {
                $engine->instance->processResponse($response, $request);
            }
        }
    }

    /**
     * Disable module if given route should be ignored
     *
     * @param Mage_Core_Controller_Front_Action $action controller object
     *
     * @return void
     */
    public function processIgnoredRoutes(Mage_Core_Controller_Front_Action $action)
    {
        $path = 'frontend/smile_magecache/ignored_routes';
        $ignoreRoute = false;
        foreach (Mage::app()->getConfig()->getNode($path)->children() as $node) {
            $route = (string)$node;
            if (substr($action->getRequest()->getRequestUri(), 0, strlen($route)) === $route) {
                $ignoreRoute = true;
                break;
            }
        }
        if (!$ignoreRoute) {
            Mage::helper('smile_magecache')->activate();
        }
    }
}

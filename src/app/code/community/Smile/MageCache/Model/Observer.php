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
 * Base observer model
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Observer
{
    /**
     * Check if module is active
     *
     * @return bool
     */
    protected function _isActive()
    {
        return Mage::helper('smile_magecache')->isActive() && $this->_isEnabled();
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    protected function _isEnabled()
    {
        return Mage::getSingleton('smile_magecache/processor')->isEnabled();
    }

    /**
     * Set cache header
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function processCache(Varien_Event_Observer $observer)
    {
        if (!$this->_isActive()) {
            return;
        }
        $controller = $observer->getEvent()->getFront();
        Mage::getSingleton('smile_magecache/processor')->processResponse(
            $controller->getResponse(),
            $controller->getRequest()
        );
    }

    /**
     * Clean cache
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function cleanCache(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled()) {
            return;
        }
        Mage::getSingleton('smile_magecache/processor')->cleanCache();
    }

    /**
     * Add model cache tage on load
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function addModelCacheTagsOnLoad(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        if (!$this->_isActive() || !Mage::helper('smile_magecache')->getCollectTags()) {
            return;
        }
        Mage::getSingleton('smile_magecache/processor')->addModelCacheTags($object);
    }

    /**
     * Add collection cache tage on load
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function addCollectionCacheTagsOnLoad(Varien_Event_Observer $observer)
    {
        if (!$this->_isActive() || !Mage::helper('smile_magecache')->getCollectTags()) {
            return;
        }
        $collection = $observer->getEvent()->getCollection();
        foreach ($collection as $item) {
            Mage::getSingleton('smile_magecache/processor')->addModelCacheTags($item);
        }
    }

    /**
     * Add category collection cache tage on load (yep-yep, the interface is not the same)
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function addCategoryCollectionCacheTagsOnLoad(Varien_Event_Observer $observer)
    {
        if (!$this->_isActive() || !Mage::helper('smile_magecache')->getCollectTags()) {
            return;
        }
        $collection = $observer->getEvent()->getCategoryCollection();
        foreach ($collection as $item) {
            Mage::getSingleton('smile_magecache/processor')->addModelCacheTags($item);
        }
    }

    /**
     * Clean cache tags when application do it
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function cleanCacheByTags(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled()) {
            return;
        }
        $tags = $observer->getEvent()->getTags();
        Mage::helper('smile_magecache')->cleanCache($tags);
    }

    /**
     * Enter exception mode for specific HTML block
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function checkBlockExceptionBeforeRender(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        Mage::getSingleton('smile_magecache/processor')->checkBlockExceptionBeforeRender($block);
    }

    /**
     * Disable exception mode if was enabled before
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function checkBlockExceptionAfterRender(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        Mage::getSingleton('smile_magecache/processor')->checkBlockExceptionAfterRender($block);
    }

    /**
     * Process ignored routes
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function processIgnoredRoutes(Varien_Event_Observer $observer)
    {
        $action = $observer->getEvent()->getControllerAction();
        Mage::getSingleton('smile_magecache/processor')->processIgnoredRoutes($action);
    }
}

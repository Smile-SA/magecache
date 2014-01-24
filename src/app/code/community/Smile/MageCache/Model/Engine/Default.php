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
 * Default application page cache processor
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Engine_Default extends Smile_MageCache_Model_Engine_Abstract
{
    /**
     * Request id prefix
     *
     * @var string
     */
    const REQUEST_ID_PREFIX = 'REQUEST_';

    /**
     * Metadata suffix
     *
     * @var string
     */
    const METADATA_CACHE_SUFFIX = '_metadata';

    /**
     * Flag that indicates if engine is enabled
     *
     * @var bool
     */
    protected $_isEnabled = null;

    /**
     * Request identifier
     *
     * @var string
     */
    protected $_requestId;

    /**
     * Request page cache identifier
     *
     * @var string
     */
    protected $_requestCacheId;

    /**
     * Cache service info
     *
     * @var mixed
     */
    protected $_metaData = null;

    /**
     * Adapter
     *
     * @var Smile_MageCache_Model_Engine_Default_Adapter
     */
    protected $_adapter = null;

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_adapter = new Smile_MageCache_Model_Engine_Default_Adapter();
        $this->_createRequestIds();
    }

    /**
     * Check if engine is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (is_null($this->_isEnabled)) {
            $this->_isEnabled = (bool)(Mage::getStoreConfig('smile_magecache/default/enabled'));
        }
        return $this->_isEnabled;
    }

    /**
     * Populate request ids
     *
     * @return Smile_MageCache_Model_Engine_Default
     */
    protected function _createRequestIds()
    {
        $uri = $this->_getFullPageUrl();

        $this->_requestId = $uri;
        $this->_requestCacheId = $this->prepareCacheId($this->_requestId);

        return $this;
    }

    /**
     * Prepare page identifier
     *
     * @param string $id id
     *
     * @return string
     */
    public function prepareCacheId($id)
    {
        return self::REQUEST_ID_PREFIX . md5($id);
    }

    /**
     * Get HTTP request identifier
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->_requestId;
    }

    /**
     * Get page identifier for loading page from cache
     *
     * @return string
     */
    public function getRequestCacheId()
    {
        return $this->_requestCacheId;
    }

    /**
     * Check if processor is allowed for current HTTP request.
     * Disable processing HTTPS requests and requests with "NO_CACHE" cookie
     *
     * @return bool
     */
    public function isAllowed()
    {
        if (!$this->_requestId) {
            return false;
        }
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return false;
        }
        if (isset($_COOKIE['NO_CACHE'])) {
            return false;
        }
        if (isset($_GET['no_cache'])) {
            return false;
        }

        return true;
    }

    /**
     * Get page content from cache storage
     *
     * @param string $content content
     *
     * @return string|false
     */
    public function extractContent($content)
    {
        if (!$content && $this->isAllowed()) {
            $content = $this->_adapter->load($this->getRequestCacheId());
            if ($content) {
                if (function_exists('gzuncompress')) {
                    $content = gzuncompress($content);
                }
                // Restore response headers
                $responseHeaders = $this->getMetadata('response_headers');
                if (is_array($responseHeaders)) {
                    foreach ($responseHeaders as $header) {
                        Mage::app()->getResponse()->setHeader($header['name'], $header['value'], $header['replace']);
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Purge tags
     *
     * @param array $tags tags list
     *
     * @return void
     */
    public function purgeTags($tags)
    {
        $this->_adapter->purgeTags($tags);
    }

    /**
     * Process response body by specific request
     *
     * @param Zend_Controller_Response_Http $response response
     * @param Zend_Controller_Request_Http  $request  request
     *
     * @return Smile_MageCache_Model_Engine_Default
     */
    public function processResponse(Zend_Controller_Response_Http $response, Zend_Controller_Request_Http $request)
    {
        /*
         * Check request and response.
         * Note: Deal only with 200 for the moment
         */
        if ($this->canProcessRequest($request) && $response->getHttpResponseCode() == 200) {
            $cacheId = $this->getRequestCacheId();
            $content = $response->getBody();
            if (function_exists('gzcompress')) {
                $content = gzcompress($content);
            }
            $res = $this->_adapter->save($content, $cacheId, $this->getRequestTags());
            $this->setMetadata('response_headers', $response->getHeaders());
            $this->_saveMetadata();
        }
        return $this;
    }

    /**
     * Retrieve allowed actions
     *
     * @return array
     */
    protected function _getAllowedActions()
    {
        $actions = array();
        $config = $this->getConfing('actions');
        if ($config) {
            $array = $config->asArray();
            if (is_array($array)) {
                $actions = array_keys($array);
            }
        }
        return $actions;
    }

    /**
     * Do basic validation for request to be cached
     *
     * @param Zend_Controller_Request_Http $request request
     *
     * @return bool
     */
    public function canProcessRequest(Zend_Controller_Request_Http $request)
    {
        $res = $this->isAllowed();
        $res = $res && $this->isEnabled();
        $fullActionName = $request->getModuleName().'_'.$request->getControllerName().'_'.$request->getActionName();
        if (!in_array($fullActionName, $this->_getAllowedActions())) {
            $res = false;
        }
        if ($request->getParam('no_cache')) {
            $res = false;
        }
        return $res;
    }

    /**
     * Set metadata value for specified key
     *
     * @param string $key   key
     * @param string $value value
     *
     * @return Enterprise_MageCache_Model_Processor
     */
    public function setMetadata($key, $value)
    {
        $this->_loadMetadata();
        $this->_metaData[$key] = $value;
        return $this;
    }

    /**
     * Get metadata value for specified key
     *
     * @param string $key key
     *
     * @return mixed
     */
    public function getMetadata($key)
    {
        $this->_loadMetadata();
        return (isset($this->_metaData[$key])) ? $this->_metaData[$key] : null;
    }

    /**
     * Return current page base url
     *
     * @return string
     */
    protected function _getFullPageUrl()
    {
        $uri = false;
        /**
         * Define server HTTP HOST
         */
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri = $_SERVER['SERVER_NAME'];
        }

        /**
         * Define request URI
         */
        if ($uri) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $uri.= $_SERVER['REQUEST_URI'];
            } elseif (!empty($_SERVER['IIS_WasUrlRewritten']) && !empty($_SERVER['UNENCODED_URL'])) {
                $uri.= $_SERVER['UNENCODED_URL'];
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
                $uri.= $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $uri.= $_SERVER['QUERY_STRING'];
                }
            }
        }
        return $uri;
    }

    /**
     * Save metadata for cache in cache storage
     *
     * @return void
     */
    protected function _saveMetadata()
    {
        $this->_adapter->save(
            serialize($this->_metaData),
            $this->getRequestCacheId() . self::METADATA_CACHE_SUFFIX,
            $this->getRequestTags()
        );
    }

    /**
     * Load cache metadata from storage
     *
     * @return void
     */
    protected function _loadMetadata()
    {
        if ($this->_metaData === null) {
            $cacheMetadata = $this->_adapter->load($this->getRequestCacheId() . self::METADATA_CACHE_SUFFIX);
            if ($cacheMetadata) {
                $cacheMetadata = unserialize($cacheMetadata);
            }
            $this->_metaData = (empty($cacheMetadata) || !is_array($cacheMetadata)) ? array() : $cacheMetadata;
        }
    }
}

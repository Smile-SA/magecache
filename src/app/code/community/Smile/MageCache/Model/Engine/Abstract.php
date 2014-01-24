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
 * Abstract cache engine
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Smile_MageCache_Model_Engine_Abstract
{
    /**
     * Engine config
     *
     * @var Mage_Core_Model_Config_Element
     */
    protected $_config = null;

    /**
     * Cache tags related with request
     * @var array
     */
    protected $_requestTags = array();

    /**
     * Check if engine is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return false;
    }

    /**
     * Associate tag with page cache request identifier
     *
     * @param array|string $tag tags list
     *
     * @return Enterprise_MageCache_Model_Processor
     */
    public function addRequestTag($tag)
    {
        if (is_array($tag)) {
            $this->_requestTags = array_merge($this->_requestTags, $tag);
        } else {
            $this->_requestTags[] = $tag;
        }
        return $this;
    }

    /**
     * Get cache request associated tags
     *
     * @return array
     */
    public function getRequestTags()
    {
        return $this->_requestTags;
    }

    /**
     * Set engine config
     *
     * @param Mage_Core_Model_Config_Element $config config node
     *
     * @return Smile_MageCache_Model_Engine_Abstract
     */
    public function setConfig(Mage_Core_Model_Config_Element $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Get config valude
     *
     * @param string $path config path
     *
     * @return Varien_Simplexml_Element
     */
    public function getConfing($path)
    {
        if (is_null($this->_config) || !$this->_config->hasChildren()) {
            return null;
        }
        return $this->_config->descend($path);
    }

    /**
     * Purge varnish cache by specified tags
     *
     * @param array $tags tags list
     *
     * @return void
     * @throws Smile_MageCache_Model_Connector_Exception
     */
    abstract function purgeTags($tags);

    /**
     * Add cache tag to response
     *
     * @param Zend_Controller_Response_Http $response request
     * @param Zend_Controller_Request_Http  $request  response
     *
     * @return void
     * @see Smile_MageCache_Model_Engine_Abstract::processResponse()
     */
    abstract public function processResponse(Zend_Controller_Response_Http $response, Zend_Controller_Request_Http $request);
}

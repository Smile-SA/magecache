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
 * Base config model
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Config
{
    /**
     * Cache shortcuts node path
     */
    const XML_PATH_CACHE_SHORTCUTS = 'global/smile_magecache/cache_tag_shortcuts';

    /**
     * Actions node path
     */
    const XML_PATH_ACTIONS = 'global/smile_magecache/actions';

    /**
     * Actions config object
     *
     * @var Mage_Core_Model_Config_Element
     */
    protected static $_actionsConfigNode = null;

    /**
     * Acions config
     *
     * @var array
     */
    protected static $_actionsConfig = array();

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_loadActionsConfig();
    }

    /**
     * Load actions config
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    protected function _loadActionsConfig()
    {
        foreach ($this->getActionsConfigNode()->children() as $code => $node) {
            $config = new Varien_Object();
            $config->setCode($code);
            $model = Mage::getModel($node);
            if (!($model instanceof Smile_MageCache_Model_Action)) {
                Mage::throwException(
                    Mage::helper('smile_magecache')->__('Class of "%s" action is not valid', $code)
                );
            }
            $config->setModel($model);
            self::$_actionsConfig[$code] = $config;
        }
    }

    /**
     * Retrieve raw actions config
     *
     * @return Mage_Core_Model_Config_Element
     */
    public function getActionsConfigNode()
    {
        if (is_null(self::$_actionsConfigNode)) {
            self::$_actionsConfigNode = Mage::app()->getConfig()->getNode(self::XML_PATH_ACTIONS);
        }
        return self::$_actionsConfigNode;
    }

    /**
     * Retrieve a config for the given action
     *
     * @param string $code code
     *
     * @return array|Varien_Object
     */
    public function getActionConfig($code = null)
    {
        if (is_null($code)) {
            return self::$_actionsConfig;
        } elseif (isset(self::$_actionsConfig[$code])) {
            return self::$_actionsConfig[$code];
        }
        return null;
    }
}
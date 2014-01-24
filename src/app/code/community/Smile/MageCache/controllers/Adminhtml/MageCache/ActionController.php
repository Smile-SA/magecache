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
 * Actions controller
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Adminhtml_MageCache_ActionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Current action
     *
     * @var Smile_MageCache_Model_Action
     */
    protected $_action = null;

    /**
     * Init action
     *
     * @return Smile_MageCache_Adminhtml_MageCache_ActionController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/smile_magecache')
            ->_addBreadcrumb($this->__('System'), $this->__('System'))
            ->_addBreadcrumb($this->__('Actions'), $this->__('Actions'));
        return $this;
    }

    /**
     * Actions list
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Page Cache'))
             ->_title($this->__('Actions'));
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('smile_magecache/adminhtml_action'))
            ->renderLayout();
    }

    /**
     * Get action from request
     *
     * @return Smile_MageCache_Model_Action
     */
    private function _getAction()
    {
        if (is_null($this->_action)) {
            $actionCode = $this->getRequest()->get('action_code');
            if (empty($actionCode)) {
                Mage::throwException($this->__('Action code is empty'));
            }
            $action = Mage::getSingleton('smile_magecache/config')
                ->getActionConfig($actionCode)
                ->getModel();
            if (is_null($action)) {
                Mage::throwException(
                    $this->__('Action "%s" does not exist', $actionCode)
                );
            }
            $this->_action = $action;
        }
        return $this->_action;
    }

    /**
     * Execute actions
     *
     * @return void
     */
    public function executeAction()
    {
        try {
            $action = $this->_getAction();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Can not initialize action'));
            $this->_redirect('*/mageCache_action/');
            return;
        }
        try {
            $action->run();
            $this->_getSession()->addSuccess($this->__('Action was successfully executed'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Error during action execution'));
            Mage::logException($e);
        }
        $this->_redirect('*/mageCache_action/');
    }
}
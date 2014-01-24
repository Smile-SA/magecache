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
 * Actions grid container
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Block_Adminhtml_Action extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize actions list page
     *
     * @return void
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_action';
        $this->_blockGroup = 'smile_magecache';
        $this->_headerText = Mage::helper('smile_magecache')->__('Actions');
        parent::__construct();
        $this->_removeButton('add');
    }
}

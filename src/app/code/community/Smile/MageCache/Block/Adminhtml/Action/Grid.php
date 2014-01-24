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
 * Actions grid
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Block_Adminhtml_Action_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Pager visibility
     *
     * @var boolean
     */
    protected $_pagerVisibility = false;

    /**
     * Set defaults
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('actionsGrid');
        $this->setDefaultSort('position');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(false);
    }

    /**
     * Prepare data collection
     *
     * @return Smile_MageCache_Model_Action_Collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('smile_magecache/action_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return void
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'name',
            array(
                'header' => $this->__('Name'),
                'type'   => 'text',
                'index'  => 'name',
                'width' => '300px'
            )
        );

        $this->addColumn(
            'description',
            array(
                'header'   => $this->__('Action Description'),
                'type'     => 'text',
                'index'    => 'description',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'execute',
            array(
                'header'   => $this->__('Action'),
                'type'     => 'action',
                'index'    => 'code',
                'filter'   => false,
                'sortable' => false,
                'width'    => '100px',
                'actions'  => array(
                    array(
                        'caption' => $this->__('Execute'),
                        'url'     => array('base' => '*/*/execute/'),
                        'field'   => 'action_code',
                    )
                )
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Grid row URL getter
     *
     * @param Varien_Object $item item
     *
     * @return string
     */
    public function getRowUrl($item)
    {
        return '';
    }
}

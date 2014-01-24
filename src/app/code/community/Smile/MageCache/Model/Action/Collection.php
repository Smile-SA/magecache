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
 * Actions collection model
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Ivan Shcherbakov <ivan.shcherbakov@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Action_Collection extends Varien_Data_Collection
{
    /**
     * Load collection data
     *
     * @param bool $printQuery need to print query
     * @param bool $logQuery   need to log query
     *
     * @return void
     * @throws Exception
     * @see Varien_Data_Collection::loadData()
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return;
        }
        $items = $this->_getActions();
        foreach ($items as $index => $item) {
            if (!$this->checkFilter($item)) {
                unset($items[$index]);
            }
        }
        if (!empty($this->_orders)) {
            uasort($items, array($this, 'orderCallback'));
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        $this->_setIsLoaded(true);
    }

    /**
     * Retrieve actions collection
     *
     * @return array
     * @throws Exception
     */
    protected function _getActions()
    {
        $config = Mage::getSingleton('smile_magecache/config');
        $actionsConfig = $config->getActionConfig();
        $items = array();
        $i = 0;
        foreach ($actionsConfig as $code => $actionConfig) {
            $action = new Varien_Object();
            $action->setPosition($i++);
            $action->setCode($actionConfig->getCode());
            $action->setName($actionConfig->getModel()->getLabel());
            $action->setDescription($actionConfig->getModel()->getDescription());
            $items[] = $action;
        }
        return $items;
    }

    /**
     * Add field to filter implementation
     *
     * @param string $field field to filter
     * @param mixed  $cond  condition
     * @param string $type  and|or
     *
     * @return Smile_MageCache_Model_Action_Collection
     */
    public function addFieldToFilter($field, $cond, $type = 'and')
    {
        if (is_array($cond)) {
            if (isset($cond['like'])) {
                return $this->addCallbackFilter($field, $cond['like'], $type, 'filterCallbackLike');
            }
            if (isset($cond['eq'])) {
                return $this->addCallbackFilter($field, $cond['eq'], $type, 'filterCallbackEqual');
            }
        } else {
            return $this->addCallbackFilter($field, $cond);
        }
        return $this;
    }

    /**
     * Check item filter
     *
     * @param Varien_Object $item collection item
     *
     * @return bool
     * @throws Exception
     */
    public function checkFilter($item)
    {
        foreach ($this->_filters as $filter) {
            if (!$item->hasData($filter->getField())) {
                throw new Exception('Undefined filter : '.$filter->getField());
            }
            $callback = array($this, $filter->getCallback());
            $callbackParams = array(
                $filter->getField(),
                $filter->getValue(),
                $item
            );
            if (!call_user_func_array($callback, $callbackParams)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add collection filter. Rewrited to be used with filter callbacks
     *
     * @param string $field field to filter
     * @param string $value value
     * @param string $type  and|or
     *
     * @return Smile_MageCache_Model_Action_Collection
     * @see Varien_Data_Collection::addFilter()
     */
    public function addFilter($field, $value, $type = 'and')
    {
        return $this->addCallbackFilter($field, $value);
    }

    /**
     * Add filter with a callback
     *
     * @param string $field    field to filter
     * @param string $value    value
     * @param string $type     and|or
     * @param string $callback callback method
     *
     * @return Smile_MageCache_Model_Action_Collection
     */
    public function addCallbackFilter($field, $value, $type = 'and', $callback = 'filterCallbackEqual')
    {
        $filter = new Varien_Object();
        $filter->setField($field);
        $filter->setValue($value);
        $filter->setType(strtolower($type));
        $filter->setCallback($callback);
        $this->_filters[] = $filter;
        $this->_isFiltersRendered = false;
        return $this;
    }

    /**
     * Check "Like" filter
     *
     * @param string        $field       field to filter
     * @param string        $filterValue value
     * @param Varien_Object $item        collection item
     *
     * @return bool
     */
    public function filterCallbackLike($field, $filterValue, $item)
    {
        $filterValueRegex = str_replace('%', '(.*?)', preg_quote($filterValue, '/'));
        return (bool)preg_match("/^{$filterValueRegex}$/i", $item[$field]);
    }

    /**
     * Check "Equal" filter
     *
     * @param string        $field       field to filter
     * @param string        $filterValue value
     * @param Varien_Object $item        collection item
     *
     * @return bool
     */
    public function filterCallbackEqual($field, $filterValue, $item)
    {
        return $item[$field] == $filterValue;
    }

    /**
     * Force standard function as we want to use action code as id
     *
     * @param Varien_Object $item collection item
     *
     * @return mixed
     */
    protected function _getItemId(Varien_Object $item)
    {
        return $item->getCode();
    }

    /**
     * Order callback function
     *
     * @param Varien_Object $itemOne item to compare
     * @param Varien_Object $itemTwo item to compare
     *
     * @return int (1 if $itemOne > $itemTwo, -1 if $itemOne < $itemTwo, 0 if items are equal)
     */
    public function orderCallback($itemOne, $itemTwo)
    {
        $result = 0;
        foreach ($this->_orders as $field => $direction) {
            $direction = strtolower($direction);
            if ($itemOne->getData($field) > $itemTwo->getData($field)) {
                $result = ($direction == 'asc') ? 1 : -1;
                break;
            }
            if ($itemOne->getData($field) < $itemTwo->getData($field)) {
                $result = ($direction == 'asc') ? -1 : 1;
                break;
            }
        }
        return $result;
    }
}
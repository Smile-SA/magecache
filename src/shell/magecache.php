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

require_once 'abstract.php';
/**
 * Script to flush page cache
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Shell_MageCache extends Mage_Shell_Abstract
{
    /**
     * Purge tags action code
     *
     * @var string
     */
    const ACTION_PURGE_TAGS = 'purge_tags';

    /**
     * Purge URL action code
     *
     * @var string
     */
    const ACTION_PURGE_URL = 'purge_url';

    /**
     * Get supported action
     *
     * @return array
     */
    public function getSupportedActions()
    {
        return array(
            self::ACTION_PURGE_TAGS => array(
                'argument' => '<tags>',
                'description' => 'Purge cache by tags'
            ),
            self::ACTION_PURGE_URL =>  array(
                'argument' => '<pattern>',
                'description' => 'Purge cache by URL pattern'
            )
        );
    }

    /**
     * Retrieve page cache processor
     *
     * @return Smile_MageCache_Model_Processor
     */
    protected function _getProcessor()
    {
        return Mage::getSingleton('smile_magecache/processor');
    }

    /**
     * Extract action from request
     *
     * @param string &$action   action
     * @param string &$argument argument
     *
     * @return void
     */
    protected function _extractAction(&$action, &$argument)
    {
        $action = false;
        foreach (array_keys($this->getSupportedActions()) as $_action) {
            $_argument = $this->getArg($_action);
            if (is_string($_argument)) {
                $action = $_action;
                $argument = $_argument;
                if ($action == self::ACTION_PURGE_TAGS) {
                    $argument = explode(',', $argument);
                }
                break;
            }
        }
        if (!$action) {
            $action = self::ACTION_PURGE_TAGS;
            $argument = $this->_getProcessor()->getTagsFromQueue();
        }
    }

    /**
     * Run script
     *
     * @return void
     */
    public function run()
    {
        $this->_extractAction($action, $argument);
        if (empty($argument)) {
            echo 'Nothing to process'."\n";
            return;
        }
        $processor = $this->_getProcessor();
        try {
            if ($action == self::ACTION_PURGE_TAGS) {
                $processor->purgeTags($argument);
                $count = count($argument);
                echo 'Requested tag'.(($count > 1) ? 's were' : ' was').' purged'."\n";
            } else {
                $processor->purgeUrl($argument);
                echo 'Requested URLs were purged'."\n";
            }
        } catch (Smile_MageCache_Model_Exception $e) {
            Mage::log(
                Mage::helper('smile_magecache')->__('Error during cache clean. Reason: %s', $e->getMessage()),
                Zend_Log::ERR,
                'magecache.log'
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Retrieve usage help message
     *
     * @return string
     */
    public function usageHelp()
    {
        $usage = 'Usage:  php -f magecache.php -- [options].'."\n";
        $supportedOptions = array();
        foreach ($this->getSupportedActions() as $action => $params) {
            $supportedOptions['--'.$action.' '.$params['argument']] = $params['description'];
        }
        $supportedOptions['-h'] = 'Short alias for help';
        $supportedOptions['help'] = 'This help';
        foreach ($supportedOptions as $option => $description) {
            $usage .= sprintf('  %-25s', $option).$description."\n";
        }
        return $usage;
    }
}

$shell = new Smile_Shell_MageCache();
$shell->run();

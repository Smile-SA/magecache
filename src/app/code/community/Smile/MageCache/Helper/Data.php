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
 * Basic module helper
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Final commit (default mode) - tags are purged at the end of the script
     * !Note: This mode use event controller related event so it can not be accessible in shell context
     */
    const COMMIT_MODE_FINAL = 'final';

    /**
     * Partial commit - tags are purged as soos as they are added (by default used in shell / webservice context)
     */
    const COMMIT_MODE_PARTIAL = 'patial';

    /**
     * Cache tags to attach to the page
     *
     * @var array
     */
    protected $_tagsToPut = array();

    /**
     * Cache tags to clean after script execution
     *
     * @var array
     */
    protected $_tagsToClean = array();

    /**
     * Collect tags flag, do not collect cache tags if false
     *
     * @var bool
     */
    protected $_collectTags = true;

    /**
     * Activation flag
     * Indicates if module should collect tag for current request (similar to $_collectedTags but on global level)
     *
     * @var bool
     */
    protected static $_isActive = false;

    /**
     * Commit mode
     *
     * @var string
     */
    protected static $_commitMode = null;

    /**
     * Activate module
     *
     * @return void
     */
    public function activate()
    {
        self::$_isActive = true;
    }

    /**
     * Check if module is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return self::$_isActive;
    }

    /**
     * Set commit mode
     *
     * @param string $mode commit mode (final / partial)
     *
     * @return void
     */
    public function setCommitMode($mode)
    {
        if (!in_array($mode, array(self::COMMIT_MODE_FINAL, self::COMMIT_MODE_PARTIAL))) {
            Mage::throwException($this->__("Unknown commit mode '%s'", $mode));
        }
        self::$_commitMode = $mode;
    }

    /**
     * Retrieve current commit mode
     *
     * @return string
     */
    public function getCommitMode()
    {
        if (is_null(self::$_commitMode)) {
            if (!is_null(Mage::registry('controller'))) {
                self::$_commitMode = self::COMMIT_MODE_FINAL;
            } else {
                self::$_commitMode = self::COMMIT_MODE_PARTIAL;
            }
        }
        return self::$_commitMode;
    }

    /**
     * Add one or several tags
     *
     * @param array|string $tags tag(s) to add
     *
     * @return void
     */
    public function addTags($tags)
    {
        if (!$this->getCollectTags() || empty($tags)) {
            return;
        }
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $this->_tagsToPut = array_merge($this->_tagsToPut, $tags);
    }

    /**
     * Get cache tags to put in the header
     *
     * @return array
     */
    public function getTagsToPut()
    {
        $this->_tagsToPut = array_unique($this->_tagsToPut);
        foreach ($this->_tagsToPut as $index => $tag) {
            if (!empty($tag)) {
                $this->_tagsToPut[$index] = strtoupper($tag);
            } else {
                unset($this->_tagsToPut[$index]);
            }
        }
        return $this->_tagsToPut;
    }

    /**
     * Clean one or several tags
     *
     * @param array|string $tags tag(s) to clean
     *
     * @return void
     */
    public function cleanCache($tags)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        $this->_tagsToClean = array_merge($this->_tagsToClean, $tags);
        if ($this->getCommitMode() == self::COMMIT_MODE_PARTIAL) {
            Mage::getSingleton('smile_magecache/processor')->cleanCache();
        }
    }

    /**
     * Get cache tags to clean in the end of the script
     *
     * @return array
     */
    public function getTagsToClean()
    {
        $this->_tagsToClean = array_unique($this->_tagsToClean);
        foreach ($this->_tagsToClean as $index => $tag) {
            $this->_tagsToClean[$index] = strtoupper($tag);
        }
        return $this->_tagsToClean;
    }

    /**
     * Get collect tags flag value
     *
     * @return bool
     */
    public function getCollectTags()
    {
        return $this->_collectTags;
    }

    /**
     * Set collect tags flag value
     *
     * @param bool $value value
     *
     * @return void
     */
    public function setCollectTags($value)
    {
        $this->_collectTags = (bool)$value;
    }
}
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
 * Cache adapter for default application page cache processor
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Engine_Default_Adapter
{
    /**
     * Cache instance
     *
     * @var Mage_Core_Model_Cache
     */
    static protected $_cache = null;

    /**
     * Retrive cache instance
     *
     * @return Mage_Core_Model_Cache
     */
    static public function getCacheInstance()
    {
        if (is_null(self::$_cache)) {
            $options = Mage::app()->getConfig()->getNode('global/smile_magecache/backend_options');
            if ($options) {
                $options = $options->asArray();
                if (!empty($options['cache_dir'])) {
                    $options['cache_dir'] = Mage::getBaseDir('var').DS.$options['cache_dir'];
                    Mage::app()->getConfig()->getOptions()->createDirIfNotExists($options['cache_dir']);
                }
                self::$_cache = Mage::getModel('core/cache', $options);
            } else {
                self::$_cache = Mage::app()->getCacheInstance();
            }
        }
        return self::$_cache;
    }

    /**
     * Load data from cache by id
     *
     * @param string $id cache id
     *
     * @return string
     */
    public function load($id)
    {
        return self::getCacheInstance()->load($id);
    }

    /**
     * Save data
     *
     * @param string $data     data to cache
     * @param string $id       cache id
     * @param array  $tags     cache tags
     * @param int    $lifeTime life time
     *
     * @return bool
     */
    public function save($data, $id, $tags = array(), $lifeTime = null)
    {
        return self::getCacheInstance()->save($data, $id, $tags, $lifeTime);
    }

    /**
     * Purge tags
     *
     * @param array $tags tags to purge
     *
     * @return bool
     */
    public function purgeTags($tags)
    {
        return self::getCacheInstance()->clean($tags);
    }
}

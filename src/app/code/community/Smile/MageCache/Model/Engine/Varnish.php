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
 * Varnish cache engine
 *
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_MageCache_Model_Engine_Varnish extends Smile_MageCache_Model_Engine_Abstract
    implements Smile_MageCache_Model_Engine_Feature_PurgeUrl
{
    /**
     * The limit before warning in the log.
     * The real apache2 limit is 8192 => we have 50% marge
     */
    const HEADER_LENGTH_LIMIT = 4096;

    /**
     * Cache header name
     */
    const CACHE_HEADER_NAME = 'X-Cache-Tags';

    /**
     * Tags separator in the cache tags
     */
    const CACHE_HEADER_TAG_SEPARATOR = '~';

    /**
     * Cache tags related with request
     *
     * @var array
     */
    protected $_requestTags = array();

    /**
     * Flag that indicates if engine is enabled
     *
     * @var bool
     */
    protected $_isEnabled = null;

    /**
     * Varnish servers list
     *
     * @var array
     */
    protected $_servers = null;

    /**
     * Varnish connector
     *
     * @var Varien_Connector
     */
    protected $_connector = null;

    /**
     * Check if engine is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (is_null($this->_isEnabled)) {
            $servers = $this->getServers();
            $this->_isEnabled = (bool)(Mage::getStoreConfig('smile_magecache/varnish/enabled') && !empty($servers));
        }
        return $this->_isEnabled;
    }

    /**
     * Get varnish servers
     *
     * @return array
     */
    public function getServers()
    {
        if (is_null($this->_servers)) {
            $this->_servers = array();
            $servers = trim(Mage::getStoreConfig('smile_magecache/varnish/servers'), ' ');
            if (!empty($servers)) {
                $servers = explode(';', $servers);
                foreach ($servers as $server) {
                    $parts = explode(':', $server);
                    $result = array(
                        'host' => $parts[0],
                        'port' => $parts[1]
                    );
                    if (!empty($parts[2])) {
                        $result['secret'] = $parts[2];
                        if (Mage::getStoreConfig('smile_magecache/varnish/secret_eol')) {
                            $result['secret'] .= "\n";
                        }
                    }
                    $this->_servers[] = $result;
                }
            }
        }
        return $this->_servers;
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
     * Retrive cache header
     *
     * @return string
     */
    public function getCacheHeader()
    {
        $separator = self::CACHE_HEADER_TAG_SEPARATOR;
        return $separator.join($separator, $this->getRequestTags()).$separator;
    }

    /**
     * Retrive Varnish connector object
     *
     * @return Varnish_Connector
     */
    protected function _getConnector()
    {
        if (is_null($this->_connector)) {
            Varnish_Connector::setLogger(Mage::getSingleton('smile_magecache/engine_varnish_connector_logger'));
            $connector = new Varnish_Connector();
            $connector->setConnectionType(Mage::getStoreConfig('smile_magecache/varnish/mode'));
            $connector->init(
                $this->getServers(),
                Mage::getStoreConfig('smile_magecache/general/debug')
            );
            $this->_connector = $connector;
        }
        return $this->_connector;
    }

    /**
     * Purge varnish cache by specified tags
     *
     * @param array $tags tags list
     *
     * @return void
     */
    public function purgeTags($tags)
    {
        $separator = self::CACHE_HEADER_TAG_SEPARATOR;

        $tagPurge = '';
        foreach ($tags as $tag) {

            // Create string containing new tag, and adding the new tag to it.
            $newTagPurge = $tagPurge;
            if (empty($tagPurge)) {
                $newTagPurge .=  $separator.$tag.$separator;
            } else {
                $newTagPurge .=  '|' . $separator.$tag.$separator;
            }

            // If there is to much tags to purge, then purge all previous tags, and start a new list
            // with the current tag in it.
            if (strlen(self::CACHE_HEADER_NAME . $newTagPurge) > self::HEADER_LENGTH_LIMIT) {
                $this->_getConnector()->purgeByResponseHeader(self::CACHE_HEADER_NAME, $tagPurge);

                // We must not forget the current tag. We couldn't process it as it was to big so we add it the list
                // to be processed in the next iteration.
                $tagPurge = $separator.$tag.$separator;
            } else {
                // The sier of the new tag being fine we can continue to work with it.
                $tagPurge = $newTagPurge;
            }
        }

        // Process the remainong tags.
        $this->_getConnector()->purgeByResponseHeader(self::CACHE_HEADER_NAME, $tagPurge);
    }

    /**
     * Purge varnish cache by URL pattern
     *
     * @param string $pattern URL pattern
     *
     * @return void
     */
    public function purgeUrl($pattern)
    {
        $this->_getConnector()->purgeByUrl($pattern);
    }

    /**
     * Add cache tag to response
     *
     * @param Zend_Controller_Response_Http $response response
     * @param Zend_Controller_Request_Http  $request  request
     *
     * @return void
     * @see Smile_MageCache_Model_Engine_Abstract::processResponse()
     */
    public function processResponse(Zend_Controller_Response_Http $response, Zend_Controller_Request_Http $request)
    {
        $headerValue = $this->getCacheHeader();
        $length = strlen($headerValue);

        if ($length > self::HEADER_LENGTH_LIMIT) {
            Mage::log(
                'Smile MageCache Warning: the '
                .self::CACHE_HEADER_NAME.' header has more than '
                .self::HEADER_LENGTH_LIMIT.' octets.'
            );
            Mage::log('  => URI: '.$request->getServer('SCRIPT_URI'));
            Mage::log('  => Length: '.$length);
            Mage::log('  => Nb Tags: '.count($this->getRequestTags()));
        }

        $response->setHeader(self::CACHE_HEADER_NAME, $headerValue);
    }
}

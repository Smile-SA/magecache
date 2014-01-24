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
 * Varnish connector
 *
 * @package   Varnish
 * @category  Varnish_Connector
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Varnish_Connector
{
    /**
     * Connection type
     *
     * @var string
     */
    protected $_connectionType = 'socket';

    /**
     * Connections list
     *
     * @var array
     */
    protected $_connections = null;

    /**
     * Logger
     *
     * @var array
     */
    protected static $_logger = null;

    /**
     * Set connector logger
     *
     * @param Varnish_Connector_Logger $logger logger object
     *
     * @return void
     */
    public static function setLogger(Varnish_Connector_Logger $logger)
    {
        self::$_logger = $logger;
    }

    /**
     * Get connector logger
     *
     * @return Varnish_Connector_Logger
     */
    public static function getLogger()
    {
        return self::$_logger;
    }

    /**
     * Init connector with servers list
     *
     * @param array $servers servers list
     * @param mixed $debug   debug flag
     *
     * @return bool
     */
    public function init($servers, $debug = null)
    {
        $connections = array();
        foreach ($servers as $server) {
            $connection = $this->_initConnection($server);
            if (!is_null($debug)) {
                $connection->setDebug($debug);
            }
            $connections[] = $connection;
        }
        $this->_connections = $connections;
        return true;
    }

    /**
     * Set connection type
     *
     * @param string $type connection type
     *
     * @return Varnish_Connector
     */
    public function setConnectionType($type)
    {
        $this->_connectionType = $type;
        return $this;
    }

    /**
     * Init single connection
     *
     * @param array $server server data
     *
     * @throws Varnish_Connector_Exception
     * @return Varnish_Connector_Connection
     */
    protected function _initConnection($server)
    {
        $className = 'varnish_connector_connection_'.$this->_connectionType;
        if (!class_exists($className)) {
            throw new Varnish_Connector_Exception(sprintf("Unknown connection type '%s'", $this->_connectionType));
        }
        return new $className($server);
    }

    /**
     * Check is connector is initialized
     *
     * @return bool
     */
    public function isInited()
    {
        return !is_null($this->_connections);
    }

    /**
     * Purge pages by specific response header
     *
     * @param string $header header name
     * @param string $tag    value
     *
     * @return void
     */
    public function purgeByResponseHeader($header, $tag)
    {
        if (!$this->isInited()) {
            return false;
        }
        foreach ($this->_connections as $connection) {
            $connection->purgeByResponseHeader($header, $tag);
        }
    }

    /**
     * Purge pages by URL pattern
     *
     * @param string $pattern URL pattern
     *
     * @return void
     */
    public function purgeByUrl($pattern)
    {
        if (!$this->isInited()) {
            return false;
        }
        foreach ($this->_connections as $connection) {
            $connection->purgeByUrl($pattern);
        }
    }
}

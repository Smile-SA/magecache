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
 * Abstract connection class
 *
 * @package   Varnish
 * @category  Varnish_Connector
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Varnish_Connector_Connection
{
    /**
     * Connection host
     *
     * @var string
     */
    protected $_host = null;

    /**
     * Debug mode
     *
     * @var bool
     */
    protected $_debug = false;

    /**
     * Create and object and init a connection
     *
     * @param array $server server
     *
     * @throws Varnish_Connector_Exception
     */
    abstract public function __construct($server);

    /**
     * Set debug mode
     *
     * @param bool $value value
     *
     * @return void
     */
    public function setDebug($value)
    {
        $this->_debug = (bool)$value;
    }

    /**
     * Put a log message
     *
     * @param string $message message text
     * @param int    $level   log level
     *
     * @return void
     */
    protected function _log($message, $level = null)
    {
        if (!is_null(Varnish_Connector::getLogger())) {
            Varnish_Connector::getLogger()->log($message, $level);
        }
    }

    /**
     * Purge pages by specific response header
     *
     * @param string $header header
     * @param string $tag    tag
     *
     * @return void
     */
    abstract public function purgeByResponseHeader($header, $tag);

    /**
     * Purge pages by URL pattern
     *
     * @param string $pattern URL pattern
     *
     * @return void
     */
    abstract public function purgeByUrl($pattern);

    /**
     * Translate message
     *
     * @return string
     */
    protected function _translate()
    {
        $result = null;
        $args = func_get_args();
        if (!empty($args)) {
            $result = array_shift($args);
            $result = @vsprintf($result, $args);
        }
        return $result;
    }

    /**
     * Throw connection exception
     *
     * @throws Varnish_Connector_Exception
     */
    protected function _throwException()
    {
        $message = call_user_func_array(
            array($this, '_translate'),
            func_get_args()
        );
        throw new Varnish_Connector_Exception($message);
    }
}

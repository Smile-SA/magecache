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
 * Varnish administrator socket connection
*
 * @package   Varnish
 * @category  Varnish_Connector
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/
abstract class Varnish_Connector_Connection_Socket extends Varnish_Connector_Connection
{
    /**
     * Response code for successful request
     */
    const RESPONSE_CODE_OK = 200;

    /**
     * Response code to indicate that an authentication is required
     */
    const RESPONSE_CODE_AUTH_REQUIRED = 107;

    /**
     * Socket handle
     *
     * @var resource
     */
    protected $_handler = null;

    /**
     * Varnish secret
     *
     * @var string
     */
    protected $_secret = null;

    /**
     * Create and object and init a connection
     *
     * @param array $server server
     *
     * @throws Varnish_Connector_Exception
     */
    public function __construct($server)
    {
        if (!isset($server['host']) || !isset($server['port'])) {
            $this->_throwException('Parameters are invalid');
        }
        $host = $server['host'];
        $port = $server['port'];
        $this->_handler = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->_handler === false) {
            $this->_logSocketError($this->_handler, $host, 'socket_create');
            $this->_throwException('Could not create a socket');
        }
        $result = socket_connect($this->_handler, $host, $port);
        if ($result === false) {
            $this->_logSocketError($this->_handler, $host, 'socket_connect');
            $this->_throwException('Could not connect to %s:%s', $host, $port);
        }
        $response = $this->_popLastResponse();
        if ($response->code != self::RESPONSE_CODE_OK) {
            if ($response->code == self::RESPONSE_CODE_AUTH_REQUIRED) {
                if (!isset($server['secret'])) {
                    $this->_throwException('Authentification required at %s:%s. Secret is not set', $host, $port);
                }
                $this->_secret = $server['secret'];
                $parts = explode("\n", $response->body);
                $challenge = $parts[0];
                $this->_authenticate($challenge);
                $authetificationResponse = $this->_popLastResponse();
                if ($authetificationResponse->code != self::RESPONSE_CODE_OK) {
                    $this->_throwException('Authentification failed at %s:%s', $host, $port);
                }
            } else {
                $this->_throwException(
                    'Could not retrive an information at %s:%s. Unknown response code %s',
                    $host,
                    $port,
                    $response->code
                );
            }
        }
        $this->_host = $host;
    }

    /**
     * Put a log for a socket error
     *
     * @param resource $handler  handler
     * @param string   $host     host
     * @param string   $function function
     *
     * @return void
     */
    protected function _logSocketError($handler, $host, $function)
    {
        $errno = socket_last_error($handler);
        $message = '['.$host.']['.$function.']['.$errno.'] '.socket_strerror($errno);
        $this->_log($message, Zend_Log::ERR);
    }

    /**
     * Put a command
     *
     * @param string  $command       command
     * @param boolean $checkResponse set true if need to check socket response
     *
     * @return stdClass response
     * @throws Varnish_Connector_Exception
     */
    protected function _put($command, $checkResponse = false)
    {
        $this->_log('['.$this->_host.'] '.$command, Zend_Log::INFO);
        if (Mage::getStoreConfig('smile_magecache/general/secret_eol')) {
            $command .= "\n";
        }
        if ((socket_write($this->_handler, $command, strlen($command))) === false) {
            $this->_logSocketError($this->_handler, $this->_host, 'socket_write');
            $this->_throwException('Unable to send a command to %s', $this->_host);
        }
        if ($checkResponse) {
            $response = $this->_popLastResponse();
            if ($response->code != self::RESPONSE_CODE_OK) {
                $message = $this->_translate('Command "%s" failed at %s', trim($command, "\n"), $this->_host);
                $this->_log('['.$this->_host.']'.$message."\n".'Reason: '.trim($response->body, "\n"), Zend_Log::ERR);
                $this->_throwException($message);
            }
        }
        $this->_log('OK', Zend_Log::INFO);
    }

    /**
     * Pop the last socket response
     *
     * @return stdClass
     * @throws Varnish_Connector_Exception
     */
    protected function _popLastResponse()
    {
        $rawResponse = socket_read($this->_handler, 12 + 1);
        if ($rawResponse === false) {
            $this->_logSocketError($this->_handler, $this->_host, 'socket_read');
            $this->_throwException('Error during result processing on %s', $this->_host);
        }
        $params = explode(' ', trim($rawResponse));
        $rawResponse = socket_read($this->_handler, $params[1] + 1);
        if ($rawResponse === false) {
            $this->_logSocketError($this->_handler, $this->_host, 'socket_read');
            $this->_throwException('Error during result processing on %s', $this->_host);
        }
        $response = new stdClass();
        $response->code = $params[0];
        $response->body = $rawResponse;
        return $response;
    }

    /**
     * Send authentification request
     *
     * @param string $challenge challange string
     *
     * @return void
     */
    protected function _authenticate($challenge)
    {
        $key = $challenge."\n".$this->_secret."\n".$challenge."\n";
        $key = hash('sha256', $key);
        $this->_put('auth '.$key);
    }
}

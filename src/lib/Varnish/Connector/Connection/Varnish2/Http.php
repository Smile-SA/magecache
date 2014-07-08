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
 * HTTP connection
 *
 * !Note: connector requires modification of VCL.
 * This code should be added to vcl_recv function in order to make connector working
 *
 *    if (req.http.X-Command && req.http.X-Secret == "YOUR SECRET CODE") {
 *        if (req.http.X-Command == "Purge-By-Header") {
 *            if (req.http.X-Arg-Name && req.http.X-Arg-Value) {
 *                purge("obj.http."req.http.X-Arg-Name" ~ " req.http.X-Arg-Value);
 *                error 200 "Done";
 *            } else {
 *                error 500 "Argument is missing";
 *            }
 *        } elsif (req.http.X-Command == "Purge-By-URL") {
 *            if (req.http.X-Arg-Pattern) {
 *                purge("req.url ~ " req.http.X-Arg-Pattern);
 *                error 200 "Done";
 *            } else {
 *                error 500 "Argument is missing";
 *            }
 *        } else {
 *            error 500 "Unknown command";
 *        }
 *    }
 *
 * @package   Varnish
 * @category  Varnish_Connector
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2013 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Varnish_Connector_Connection_Varnish2_Http extends Varnish_Connector_Connection
{
    /**
     * Default server port
     *
     * @var int
     */
    const DEFAULT_SERVER_PORT = 80;

    /**
     * Purge URL template
     *
     * @var string
     */
    protected $_purgeUrlTemplate = 'http://{host}';

    /**
     * Response code for successful request
     *
     * @var int
     */
    const RESPONSE_CODE_OK = 200;

    /**
     * Response status for successful request
     *
     * @var string
     */
    const RESPONSE_STATUS_OK = 'Done';

    /**
     * Connection port
     *
     * @var string
     */
    protected $_port = null;

    /**
     * Secret key to launch commands
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
        if (!isset($server['host'])) {
            $this->_throwException('Parameters are invalid');
        }
        $this->_host = $server['host'];
        $this->_port = isset($server['port']) ? $server['port'] : self::DEFAULT_SERVER_PORT;
        if (isset($server['secret'])) {
            $this->_secret = $server['secret'];
        }
    }

    /**
     * Put a command
     *
     * @param string  $command       command to launch
     * @param array   $arguments     arguments list
     * @param boolean $checkResponse check servers response
     *
     * @throws Varnish_Connector_Exception
     * @return void
     */
    protected function _put($command, array $arguments, $checkResponse = false)
    {
        $url = str_replace('{host}', $this->_host, $this->_purgeUrlTemplate);
        $headers = array('X-Command: '.$command);
        if (!is_null($this->_secret)) {
            $headers[] = 'X-Secret: '.$this->_secret;
        }
        foreach ($arguments as $key => $value) {
            $headers[] = 'X-Arg-'.ucfirst($key).': '.$value;
        }

        $this->_log('['.$this->_host.'] '.join('; ', $headers), Zend_Log::INFO);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($this->_port != self::DEFAULT_SERVER_PORT) {
            curl_setopt($ch, CURLOPT_PORT, $this->_port);
        }
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($checkResponse) {
            $error = false;
            if (!$result) {
                $error = $this->_translate('Server is not responding');
            } else {
                $headers = explode("\r\n", $result);
                $status = $headers[0];
                $parts = explode(' ', $status);
                if (count($parts) < 3) {
                    $error = $this->_translate('Unexpected response');
                } else {
                    unset($parts[0]);
                    $code = array_shift($parts);
                    $status = join(' ', $parts);
                    if ($code != self::RESPONSE_CODE_OK || $status != self::RESPONSE_STATUS_OK) {
                        $error = $code.' '.$status;
                    }
                }
            }
            if ($error !== false) {
                $message = $this->_translate('Command "%s" failed at %s', trim($command, "\n"), $this->_host);
                $this->_log('['.$this->_host.']'.$message."\n".'Reason: '.$error, Zend_Log::ERR);
                $this->_throwException($message);
            }
        }
        $this->_log('OK', Zend_Log::INFO);
    }

    /**
     * Purge pages by specific response header
     *
     * @param string $header header
     * @param string $tag    tag
     *
     * @return void
     */
    public function purgeByResponseHeader($header, $tag)
    {
        $this->_put(
            'Purge-By-Header',
            array(
                'name' => $header,
                'value' => $tag
            ),
            true
        );
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
        $this->_put(
            'Purge-By-URL',
            array('pattern' => $pattern),
            true
        );
    }
}

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
class Varnish_Connector_Connection_Varnish2_Socket extends Varnish_Connector_Connection_Socket
{
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
        $this->_put('purge obj.http.'.$header.' ~ '.$tag, true);
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
        $this->_put('purge.url '.$pattern, true);
    }
}

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
 * Varnish connection mode (source model)
*
 * @category  Smile
 * @package   Smile_MageCache
 * @author    Smile <solution.magento@smile.fr>
 * @copyright 2012 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/
class Smile_MageCache_Model_Engine_Varnish_Connector_Connection_Mode
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'varnish4_socket',
                'label' => Mage::helper('smile_magecache')->__('Varnish4: administrator socket')
            ),
            array(
                'value' => 'varnish3_socket',
                'label' => Mage::helper('smile_magecache')->__('Varnish3: administrator socket')
            ),
            array(
                'value' => 'varnish2_socket',
                'label' => Mage::helper('smile_magecache')->__('Varnish2: administrator socket')
            ),
            array(
                'value' => 'varnish2_http',
                'label' => Mage::helper('smile_magecache')->__('Varnish2: HTTP')
            )
        );
    }
}

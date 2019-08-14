<?php
/**
 * Numerno - Euro.message Magento Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the NUMERNO EUROMESSAGE MAGENTO EXTENSION License, which extends the Open Software
 * License (OSL 3.0). The Euro.message Magento Extension License is available at this URL:
 * http://numerno.com/licenses/euromsg-ce.txt The Open Software License is available at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Numerno is not held liable for any inconsistencies or
 * abnormalities in the behaviour of this code. By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Numerno, outlined in the provided Euro.message Magento Extension
 * License.
 * Upon discovery of modified code in the process of support, the Licensee is still held accountable for any and all
 * billable time Numerno spent during the support process. Numerno does not guarantee compatibility with any other
 * Magento extension. Numerno is not responsbile for any inconsistencies or abnormalities in the behaviour of this
 * code if caused by other Magento extension.
 * If you did not receive a copy of the license, please send an email to info@numerno.com or call +90-212-223-5093,
 * so we can send you a copy immediately.
 *
 * @category   [Numerno]
 * @package    [Numerno_Euromsg]
 * @copyright  Copyright (c) 2015 Numerno Bilisim Hiz. Tic. Ltd. Sti. (http://numerno.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Post Service Helper
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Helper_Post extends Mage_Core_Helper_Abstract
{

    public function getStoreConfig($path){

        return Mage::getStoreConfig('euromsg_trx/' . $path);
    }

    public function getPostParams(){

        $params = array(
            'FromName' => $this->getStoreConfig('general/from_name'),
            'FromAddress' => $this->getStoreConfig('general/from_addr'),
            'ReplyAddress' => $this->getStoreConfig('general/reply_addr')
        );

        return $params;
    }

    public function isEnabled(){

        return $this->getStoreConfig('general/enabled');
    }

    public function getDeliveryStatusOptions()
    {

        return array(
            'RE' => Mage::helper('euromsg')->__('Relayed'),
            'HU' => Mage::helper('euromsg')->__('Undelivered (HARD)'),
            'SU' => Mage::helper('euromsg')->__('Undelivered (SOFT)'),
            'RD' => Mage::helper('euromsg')->__('Read')
        );
    }
}
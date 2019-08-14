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
 * PostSms Service Helper
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Helper_Sms extends Mage_Core_Helper_Abstract
{

    public function getStoreConfig($path)
    {

        return Mage::getStoreConfig('euromsg_sms/' . $path);
    }

    public function getOriginator()
    {

        return $this->getStoreConfig('general/originator');
    }

    public function isEnabled()
    {

        return $this->getStoreConfig('general/enabled');
    }

    public function getGsmAttribute()
    {
        $attributeCode = $this->getStoreConfig('general/attribute');

        if(strlen($attributeCode)) {
            $attribute = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode);

            if($attribute->getAttributeId())
                return $attributeCode;
        }

        return false;
    }

    public function validateGsmNumber($string)
    {
        $numbersOnly = preg_replace("[^0-9]", "", $string) / 1;
        $numberOfDigits = strlen($numbersOnly);
        if( ($numberOfDigits == 10 && substr($numbersOnly, 0, 1) == '5') ||
            ($numberOfDigits == 12 && in_array(substr($numbersOnly, 0, 4), array('9050', '9053', '9054', '9055'))) ){

            return true;
        }
        return false;
    }

    public function filterGsmNumber($string)
    {
        $numbersOnly = preg_replace("[^0-9]", "", $string) / 1;
        if(strlen($numbersOnly) == 10 && substr($numbersOnly, 0, 1) == '5')
            $numbersOnly = '90' . $numbersOnly;

        if(strlen($numbersOnly) == 12 && in_array(substr($numbersOnly, 0, 4), array('9050', '9053', '9054', '9055')))
            return (string) $numbersOnly;

        return false;
    }
}
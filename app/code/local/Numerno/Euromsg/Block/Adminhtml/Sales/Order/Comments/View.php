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
 * Adminhtml Order Comments View
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Sales_Order_Comments_View extends Mage_Adminhtml_Block_Sales_Order_Comments_View
{
    /*
     * Check if eligible to send order update sms
     *
     * @return bool
     */
    public function canSendCommentSms()
    {
        $helper    = Mage::helper('euromsg/sms');
        try {
            $notify    = $helper->getStoreConfig('template/sms_order_update');

            if($notify) {
                $order = $this->getEntity()->getOrder();
                if (!$order->getCustomerIsGuest() || $order->getCustomerId()) {
                    $customer  = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    $smsPermit = $customer->getData(Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE);
                    if($smsPermit) {
                        $smsAttribute = $helper->getGsmAttribute();
                        if($smsAttribute) {
                            $gsmNumber = $customer->getData($smsAttribute);
                            if($helper->validateGsmNumber($gsmNumber))
                            {
                                return true;
                            }
                        }
                    }
                }
            }

        }
        catch(Exception $e) { Mage::logException($e); }

        return false;
    }

}
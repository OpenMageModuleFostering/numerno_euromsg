<?php
/**
 * euro.message Personalized Omni-channel Marketing Automation
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the "NUMERNO EUROMESSAGE MAGENTO EXTENSION License", which extends the Open Software
 * License (OSL 3.0).
 * The "NUMERNO EUROMESSAGE MAGENTO EXTENSION License" is available at this URL:
 *  http://www.numerno.com/licenses/euromsg-ce.txt
 * The Open Software License (OSL 3.0) is available at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Numerno is not held liable for any inconsistencies or
 * abnormalities in the behaviour of this code. By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Numerno, outlined in the provided License.
 *
 * Upon discovery of modified code in the process of support, the Licensee is still held accountable for any and all
 * billable time Numerno spent during the support process. Numerno does not guarantee compatibility with any other
 * Magento extension. Numerno is not responsbile for any inconsistencies or abnormalities in the behaviour of this
 * code if caused by other Magento extension.
 *
 * If you did not receive a copy of the license, please send an email to info@numerno.com or call +90-212-223-5093,
 * so we can send you a copy immediately.
 *
 * @category   [Numerno]
 * @package    [Numerno_Euromsg]
 * @copyright  Copyright (c) 2016 Numerno Bilisim Hiz. Tic. Ltd. Sti. (http://www.numerno.com/)
 * @license    http://numerno.com/licenses/euromsg-ce.txt NUMERNO EUROMESSAGE MAGENTO EXTENSION License
 */

/**
 * Sms
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Sms extends Mage_Core_Model_Abstract
{
    const SMS_PERMIT_ATTRIBUTE_CODE = 'em_gsm_permit';
    const COL_GSM_PERMIT            = 'GSM_PERMIT';
    const COL_GSM_NO                = 'GSM_NO';

    protected $_eventPrefix = 'euromsg_sms';
    protected $_eventObject = 'sms';

    /**
     * Initialize model
     *
     */
    protected function _construct()
    {
        $this->_init('euromsg/sms');
    }

    public function getHelper()
    {
        return Mage::helper('euromsg');
    }

    public function _send()
    {
        try {
            $client = Mage::getModel('euromsg/platform');
            $sms = $client->sendSMS($this->getMessage(), $this->getGsmNumber(), $this->getBeginTime());

            if($sms) {
                $this
                    ->setPacketId($sms->PacketID)
                    ->setResponseCode('00')
                    ->setType($sms->Type);
                foreach($sms->DeliveryResults as $delivery) {
                    $this
                        ->setDeliveryStatus($delivery->DeliveryResult)
                        ->setDeliveryMessage($delivery->DeliveryDetail)
                        ->setDeliveryTime(date('Y-m-d H:i:s', strtotime($delivery->DeliveryTime)));
                    break;
                }
            } else {
                $this->setResponseCode('99');
            }

            $client->_logout();

        }
        catch(Exception $e) {
            $this
                ->setResponseCode('99')
                ->setResponseMessage($e->getMessage())
                ->setResponseMessageDetailed((string) $e);
        }

        $this->save();
    }

    public function send($customer, $message, $sendNow = false){

        $attributeCode = $this->getHelper()->getConfigData('general/attribute', 'sms');
        if ($attributeCode && $customer->getId() && strlen($message)) {

            $gsmNumber = $customer->getData($attributeCode);
            if ($this->getHelper()->validateGsmNumber($gsmNumber)) {

                $this
                    ->setBeginTime(Mage::getModel('core/date')->date('Y-m-d H:i:s'))
                    ->setCustomerId($customer->getId())
                    ->setGsmNumber($this->getHelper()->filterGsmNumber($gsmNumber))
                    ->setMessage($message);

                if ($sendNow) {
                    $this->_send();
                } else {
                    $this
                        ->setDeliveryStatus('P')
                        ->save();
                }
            }
        }
    }
}
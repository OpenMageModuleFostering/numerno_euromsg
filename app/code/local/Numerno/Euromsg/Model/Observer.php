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
 * Observer
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Observer
{

    /**
     * Send Order Update SMS
     */
    public function sendOrderUpdateSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $controller   = $event->getControllerAction();
        $order        = Mage::registry('current_order');

        if ($order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $data         = $controller->getRequest()->getPost('history');
                    $notifySms    = isset($data['is_customer_notified_by_sms'])
                        ? $data['is_customer_notified_by_sms'] : false;
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_order_update_template');
                    $suffix       = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');

                    if($notifySms) {

                        $processor = new Varien_Filter_Template();
                        $processor->setVariables(array(
                            'order'    => $order,
                            'customer' => $customer,
                            'comment'  => $data['comment'],
                            'suffix'   => $suffix
                        ));
                        $message = trim($processor->filter($template));

                        Mage::getModel('euromsg/sms')->send($customer, $message);
                    }
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

        return true;
    }

    /**
     * Send Invoice Update SMS
     */
    public function sendInvoiceUpdateSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $controller   = $event->getControllerAction();
        $invoice      = Mage::registry('current_invoice');
        $order        = $invoice->getOrder();

        if ($order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $data         = $controller->getRequest()->getPost('comment');
                    $notifySms    = isset($data['is_customer_notified_by_sms'])
                        ? $data['is_customer_notified_by_sms'] : false;
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_order_update_template');
                    $suffix       = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');

                    if($notifySms) {
                        $processor = new Varien_Filter_Template();
                        $processor->setVariables(array(
                            'order'    => $order,
                            'customer' => $customer,
                            'comment'  => $data['comment'],
                            'suffix'   => $suffix
                        ));
                        $message = trim($processor->filter($template));

                        Mage::getModel('euromsg/sms')->send($customer, $message);
                    }
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send Shipment Update SMS
     */
    public function sendShipmentUpdateSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $controller   = $event->getControllerAction();
        $shipment     = Mage::registry('current_shipment');
        $order        = $shipment->getOrder();

        if ($order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $data         = $controller->getRequest()->getPost('comment');
                    $notifySms    = isset($data['is_customer_notified_by_sms'])
                        ? $data['is_customer_notified_by_sms'] : false;
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_order_update_template');
                    $suffix       = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');

                    if($notifySms) {
                        $processor = new Varien_Filter_Template();
                        $processor->setVariables(array(
                            'order'    => $order,
                            'customer' => $customer,
                            'comment'  => $data['comment'],
                            'suffix'   => $suffix
                        ));
                        $message = trim($processor->filter($template));

                        Mage::getModel('euromsg/sms')->send($customer, $message);
                    }
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send Creditmemo Update SMS
     */
    public function sendCreditmemoUpdateSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $controller   = $event->getControllerAction();
        $creditMemo   = Mage::registry('current_creditmemo');
        $order        = $creditMemo->getOrder();

        if ($order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $data         = $controller->getRequest()->getPost('comment');
                    $notifySms    = isset($data['is_customer_notified_by_sms'])
                        ? $data['is_customer_notified_by_sms'] : false;
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_order_update_template');
                    $suffix       = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');

                    if($notifySms) {
                        $processor = new Varien_Filter_Template();
                        $processor->setVariables(array(
                            'order'    => $order,
                            'customer' => $customer,
                            'comment'  => $data['comment'],
                            'suffix'   => $suffix
                        ));
                        $message = trim($processor->filter($template));

                        Mage::getModel('euromsg/sms')->send($customer, $message);
                    }
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send New Order SMS
     */
    public function sendNewOrderSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $order        = $event->getOrder();
        $enabled      = Mage::helper('euromsg/sms')->isEnabled();
        $notifySms    = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_order');

        if ($enabled && $notifySms && $order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_order_template');
                    $suffix     = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');
                    $processor = new Varien_Filter_Template();
                    $processor->setVariables(array(
                        'order'    => $order,
                        'customer' => $customer,
                        'suffix'   => $suffix
                    ));
                    $message = trim($processor->filter($template));

                    Mage::getModel('euromsg/sms')->send($customer, $message);
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send New Invoice SMS
     */
    public function sendNewInvoiceSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $invoice      = $event->getInvoice();
        $order        = $invoice->getOrder();
        $enabled      = Mage::helper('euromsg/sms')->isEnabled();
        $notifySms    = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_invoice');

        if ($enabled && $notifySms && $order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_invoice_template');
                    $suffix     = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');
                    $processor = new Varien_Filter_Template();
                    $processor->setVariables(array(
                        'order'    => $order,
                        'invoice'  => $invoice,
                        'customer' => $customer,
                        'suffix'   => $suffix
                    ));
                    $message = trim($processor->filter($template));

                    Mage::getModel('euromsg/sms')->send($customer, $message);
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send New Shipment SMS
     */
    public function sendNewShipmentSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $shipment     = $event->getShipment();
        $order        = $shipment->getOrder();
        $enabled      = Mage::helper('euromsg/sms')->isEnabled();
        $notifySms    = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_shipment');

        if ($enabled && $notifySms && $order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_shipment_template');
                    $suffix     = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');

                    $processor = new Varien_Filter_Template();
                    $processor->setVariables(array(
                        'order'    => $order,
                        'shipment' => $shipment,
                        'customer' => $customer,
                        'suffix'   => $suffix
                    ));
                    $message = trim($processor->filter($template));

                    Mage::getModel('euromsg/sms')->send($customer, $message);
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send New Shipment Tracking Number SMS
     */
    public function sendNewTrackSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $track        = $event->getTrack();
        $shipment     = $track->getShipment();
        $order        = $shipment->getOrder();
        $enabled      = Mage::helper('euromsg/sms')->isEnabled();
        $notifySms    = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_track');

        if ($enabled && $notifySms && $order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_track_template');
                    $suffix     = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');

                    $processor = new Varien_Filter_Template();
                    $processor->setVariables(array(
                        'order'    => $order,
                        'shipment' => $shipment,
                        'track'    => $track,
                        'customer' => $customer,
                        'suffix'   => $suffix
                    ));
                    $message = trim($processor->filter($template));

                    Mage::getModel('euromsg/sms')->send($customer, $message);
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Send New Credit Memo Number SMS
     */
    public function sendNewCreditmemoSms(Varien_Event_Observer $observer)
    {
        $event        = $observer->getEvent();
        $creditmemo   = $event->getCreditmemo();
        $order        = $creditmemo->getOrder();
        $enabled      = Mage::helper('euromsg/sms')->isEnabled();
        $notifySms    = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_creditmemo');

        if ($enabled && $notifySms && $order->getId() && $order->getCustomerId()) {
            try {
                $customer     = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if($customer->getId()) {
                    $template     = Mage::helper('euromsg/sms')->getStoreConfig('template/sms_new_creditmemo_template');
                    $suffix     = Mage::helper('euromsg/sms')->getStoreConfig('template/footer');
                    $processor = new Varien_Filter_Template();
                    $processor->setVariables(array(
                        'order'      => $order,
                        'creditmemo' => $creditmemo,
                        'customer'   => $customer,
                        'suffix'     => $suffix
                    ));
                    $message = trim($processor->filter($template));

                    Mage::getModel('euromsg/sms')->send($customer, $message);
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }

    }


}
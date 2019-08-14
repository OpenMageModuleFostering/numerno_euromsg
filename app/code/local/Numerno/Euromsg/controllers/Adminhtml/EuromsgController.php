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
 * Euromsg Controller
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Adminhtml_EuromsgController extends Mage_Adminhtml_Controller_Action
{

    public function mailLogsAction()
    {
        $this->_title($this->__('euro.message'))->_title($this->__('Mail Logs'));
        $this->loadLayout();
        $this->_setActiveMenu('euromessage/trx');
        $this->_addContent($this->getLayout()->createBlock('euromsg/adminhtml_mail_logs'));
        $this->renderLayout();
    }

    public function smsLogsAction()
    {
        $this->_title($this->__('euro.message'))->_title($this->__('SMS Logs'));
        $this->loadLayout();
        $this->_setActiveMenu('euromessage/sms');
        $this->_addContent($this->getLayout()->createBlock('euromsg/adminhtml_sms_logs'));
        $this->renderLayout();
    }

    public function smsSubscribersAction()
    {
        $this->_title($this->__('euro.message'))->_title($this->__('SMS Subscribers'));
        $this->loadLayout();
        $this->_setActiveMenu('euromessage/sms');
        $this->_addContent($this->getLayout()->createBlock('euromsg/adminhtml_sms_subscribers'));
        $this->renderLayout();
    }

    public function syncNowAction()
    {
        if ($this->getRequest()->isAjax()) {

            $session    = Mage::getSingleton('adminhtml/session');
            $syncType   = $this->getRequest()->getPost('sync');

            switch ($syncType) {
                case 'member':
                    $tableName = Mage::helper('euromsg')->getConfigData('general/filename', 'customer');
                    if (!Mage::helper('euromsg')->validateTableName($tableName)) {
                        $session->addError(
                            Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.')
                        );
                        break;
                    }
                    $process = Mage::getModel('euromsg/process')
                        ->setTableName($tableName)
                        ->setType($syncType);

                    try {
                        $process->export();

                        $session->addSuccess(
                            Mage::helper('euromsg')->__('All member data successfully exported to euro.message platform.')
                        );
                    } catch(Exception $e) {
                        $session->addError(
                            $e->getMessage()
                        );
                    }
                    break;

                case 'product':
                    $tableName = Mage::helper('euromsg')->getConfigData('general/filename', 'catalog');
                    if (!Mage::helper('euromsg')->validateTableName($tableName)) {
                        $session->addError(
                            Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.')
                        );
                        break;
                    }
                    $process = Mage::getModel('euromsg/process')
                        ->setTableName($tableName)
                        ->setType($syncType);
                    try {
                        //export process
                        $process->export();

                        $session->addSuccess(
                            Mage::helper('euromsg')->__('All product data successfully exported to euro.message platform.')
                        );
                    } catch(Exception $e) {
                        $session->addError(
                            $e->getMessage()
                        );
                    }
                    break;

                default:
                    $session->addError(
                        Mage::helper('euromsg')->__('Cannot process the request.')
                    );
                    break;
            }
        }
    }

    public function exportAction()
    {
        $session   = Mage::getSingleton('adminhtml/session');
        $tableName = $this->getRequest()->getParam('dwhname');

        if (!Mage::helper('euromsg')->validateTableName($tableName)) {
            $session->addError(
                Mage::helper('euromsg')->__('Data warehouse table name is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.')
            );

            return;
        }

        $process = Mage::getModel('euromsg/process')
            ->setTableName($tableName)
            ->setType('member');

        if ($this->getRequest()->getParam('internal_customer')) {
            $customerIds = explode(',', $this->getRequest()->getParam('internal_customer'));
            $process->addFilter('customer_id', $customerIds);
        }

        if ($this->getRequest()->getParam('internal_subscriber')) {
            $subscriberIds = explode(',', $this->getRequest()->getParam('internal_subscriber'));
            $process->addFilter('subscriber_id', $subscriberIds);
        }

        try {
            $process->export();

            $session->addSuccess(
                Mage::helper('euromsg')->__('%s table successfully exported to euro.message platform.', $tableName)
            );
        } catch(Exception $e) {
            $session->addError(
                $e->getMessage()
            );
        }
    }

    public function massSmsSubscribeAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if (!is_array($customersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select customer(s).')
            );

        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    $customer->setData((string) Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE, true);
                    $customer->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated.', count($customersIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/smsSubscribers');
    }

    public function massSmsUnsubscribeAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if (!is_array($customersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select customer(s).')
            );
        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    $customer->setData((string) Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE, false);
                    $customer->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated.', count($customersIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/smsSubscribers');
    }

}
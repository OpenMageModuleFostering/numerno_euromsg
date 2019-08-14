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
 * Cron Jobs
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Cron
{
    /**
     * Send Customer Data to euro.message Data Warehouse
     *
     * @return bool
     */
    public function syncCustomerData()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $tableName = Mage::getStoreConfig('euromsg_customer/general/filename');

        $validatorTableName = new Zend_Validate_Regex(array('pattern' => '/^[A-Za-z][A-Za-z_0-9]{1,254}$/'));
        if (!$validatorTableName->isValid($tableName)) {
            $session->addError(
                Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only '
                    . 'letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a'
                    . ' letter.')
            );
        }
        $process = Mage::getModel('euromsg/process')
            ->setTableName($tableName)
            ->setType('member');
        try {
            //export process
            $process->export(true);

            $session->addSuccess(
                Mage::helper('euromsg')->__('All member data successfully exported to euro.message platform.')
            );
        }catch(Exception $e) {
            $session->addError(
                $e->getMessage()
            );
        }

        return true;
    }

    /**
     * Send Catalog Data to euro.message Data Warehouse
     *
     * @return bool
     */
    public function syncCatalogData()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $current = Mage::app()->getStore()->getId();

        foreach(Mage::app()->getStores() as $store) {

            Mage::app()->setCurrentStore($store->getId());

            $tableName = Mage::getStoreConfig('euromsg_catalog/general/filename');
            $validatorTableName = new Zend_Validate_Regex(array('pattern' => '/^[A-Za-z][A-Za-z_0-9]{1,254}$/'));
            if (!$validatorTableName->isValid($tableName)) {
                $session->addError(
                    Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only '
                        . 'letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a'
                        . ' letter.')
                );
            }
            $process = Mage::getModel('euromsg/process')
                ->setTableName($tableName)
                ->setType('product');
            try {
                //export process
                $process->export(true);

                $session->addSuccess(
                    Mage::helper('euromsg')->__('All product data successfully exported to euro.message platform.')
                );
            }catch(Exception $e) {
                $session->addError(
                    $e->getMessage()
                );
            }

        }
        Mage::app()->setCurrentStore($current);

        return true;
    }

    /**
     * Send Catalog Data to euro.message Data Warehouse
     *
     * @return bool
     */
    public function runAllProcesses()
    {
        if(Mage::helper('euromsg')->getStoreConfig('/dwh/policy') == Numerno_Euromsg_Model_Process::POLICY_ASYNC) {
            $collection = Mage::getResourceModel('euromsg/process_collection')
                ->addFieldToFilter('status', 'pending');

            foreach($collection as $process){
                $process->export(true);
            }
        }

        return true;
    }

    /**
     * Check Data Warehouse Report files
     *
     * @return bool
     */
    public function checkDwhReports()
    {
        $helper = Mage::helper('euromsg');

        if($helper->getStoreConfig('/feedback/autosync')) {
            if($helper->getStoreConfig('/feedback/destination') == 'sftp') {
                $con = $helper->getSftpConnection('feedback');
                $dir = $helper->getStoreConfig('/feedback/sftp_dir');
                $con->cd($dir);
                foreach($con->ls() as $file) {
                    $extension = pathinfo($file['id'], PATHINFO_EXTENSION);
                    if ($extension == 'zip') {
                        $zipFile = sys_get_temp_dir() . DS . $file['text'];
                        file_put_contents($zipFile, $con->read($file['id']));
                        $helper->processFeedbackZip($zipFile);

                        if($helper->getStoreConfig('/feedback/delete_after'))
                            $con->rm($file['id']);
                    }
                }
            } elseif($helper->getStoreConfig('/feedback/destination') == 'local') {
                $dir = Mage::getBaseDir() . Mage::helper('euromsg')->getStoreConfig('/feedback/local_dir');
                $dircontents = scandir($dir);
                foreach ($dircontents as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    if ($extension == 'zip') {
                        $helper->processFeedbackZip($dir . DS . $file);
                        if($helper->getStoreConfig('/feedback/delete_after'))
                            unlink($dir . DS . $file);
                    }
                }
            }
        }
    }

    /**
     * Clean Mail Logs
     *
     * @return bool
     */
    public function cleanMailLogs()
    {
        $helper  = Mage::helper('euromsg/post');
        $clean = $helper->getStoreConfig('log/autosync');

        if($clean) {
            $logs  = Mage::getModel('euromsg/mail_log')->getCollection();

            $keep = $helper->getStoreConfig('log/keeplogs');
            if($keep) {
                $date = date('Y-m-d H:i:s', strtotime("-$keep days"));
            } else {
                $date = date('Y-m-d H:i:s');
            }

            $logs->addFieldToFilter('send_at', array('lt' => $date));

            foreach($logs as $log)
                $log->delete();

        }
    }

    /**
     * Track Mail Delivery
     *
     * @return bool
     */
    public function trackMailDelivery()
    {
        $helper  = Mage::helper('euromsg/post');
        $tracking = $helper->getStoreConfig('log/track_delivery');

        if($tracking) {

            $client  = Mage::getModel('euromsg/platform');
            $client->_login();

            $service = $client->getPostService();
            $logs    = Mage::getModel('euromsg/mail_log')->getCollection()
                ->addFieldToFilter('post_id', array('notnull' => true))
                ->addFieldToFilter('delivery_relay_status', array(array('neq' => 'D'), array('null' => true)));

            foreach($logs as $post) {

                $status = $service->check($post->getPostId());
                if($post->getDeliveryRelayStatus() != $status->RelayStatus) {

                    $post->setDeliveryRelayStatus($status->RelayStatus);

                    if($status->RelayStatus == 'D') {
                        $post->setDeliveryStatus($status->DeliveryStatus)
                            ->setUndeliveryReason($status->UndeliveryReason);
                    }

                    $post->save();
                }
            }

            $client->_logout();
        }
    }

    /**
     * Send SMS Queue
     *
     * @return bool
     */
    public function sendAllSms()
    {
        if(Mage::helper('euromsg/sms')->isEnabled()) {
            $collection = Mage::getResourceModel('euromsg/sms_collection')
                ->addFieldToFilter('delivery_status', 'P');

            foreach($collection as $sms){
                $sms->_send();
            }
        }
    }
}
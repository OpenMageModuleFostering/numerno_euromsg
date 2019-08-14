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
        if (!Mage::helper('euromsg')->getConfigData('general/autosync', 'customer')) {
            return true;
        }

        $session   = Mage::getSingleton('adminhtml/session');
        $tableName = Mage::getStoreConfig('euromsg_customer/general/filename');

        if (!Mage::helper('euromsg')->validateTableName($tableName)) {
            $session->addError(
                Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.')
            );
        }

        $process = Mage::getModel('euromsg/process')
            ->setTableName($tableName)
            ->setType('member');

        try {
            //TODO: use constant
            $process->export(true);

            $session->addSuccess(
                Mage::helper('euromsg')->__('All member data successfully exported to euro.message platform.')
            );
        }
        catch(Exception $e) {
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
        if (!Mage::helper('euromsg')->getConfigData('general/autosync', 'catalog')) {
            return true;
        }

        $session        = Mage::getSingleton('adminhtml/session');
        $currentStoreId = Mage::app()->getStore()->getId();

        foreach (Mage::app()->getStores() as $store) {

            Mage::app()->setCurrentStore($store->getId());

            $tableName = Mage::helper('euromsg')->getConfigData('general/filename', 'catalog',  $store->getId());
            if (!Mage::helper('euromsg')->validateTableName($tableName)) {
                $session->addError(
                    Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.')
                );
            }

            $process = Mage::getModel('euromsg/process')
                ->setTableName($tableName)
                ->setStoreId($store->getId())
                ->setType('product');

            try {
                //TODO: use constant
                $process->export(true);

                $session->addSuccess(
                    Mage::helper('euromsg')->__('All product data successfully exported to euro.message platform.')
                );
            }
            catch(Exception $e) {
                $session->addError(
                    $e->getMessage()
                );
            }

        }
        Mage::app()->setCurrentStore($currentStoreId);

        return true;
    }

    /**
     * Sync SMS and Email Unsubscribers
     *
     * @return bool
     */
    public function syncUnsubscribers()
    {
        $client  = Mage::getSingleton('euromsg/platform');
        $date    = Mage::getModel('core/date');
        $offset  = $date->calculateOffset('Europe/Istanbul');
        $current = $date->gmtTimestamp() + $offset;

        $unsubscribers = $client->getUnsubscribers(date('Y-m-d H:i:s', $current - 600), date('Y-m-d H:i:s', $current));
        foreach($unsubscribers->EmUnsubscriberDetails as $unsubscriber){

            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($unsubscriber->Email);
            if (!$subscriber->getId()){
                continue;
            }

            $changedAt = $date->date('Y-m-d H:i:s', strtotime($unsubscriber->UnsubscribeTime) - $offset);
            switch ($unsubscriber->EmailPermit) {
                case 'Y':
                case 'L':
                    $subscriber->setSubscriberStatus($subscriber::STATUS_SUBSCRIBED)
                        ->setChangeStatusAt($changedAt)
                        ->save();
                    break;
                case 'N':
                    $subscriber->setSubscriberStatus($subscriber::STATUS_NOT_ACTIVE)
                        ->setChangeStatusAt($changedAt)
                        ->save();
                    break;
                case 'X':
                    $subscriber->setSubscriberStatus($subscriber::STATUS_UNSUBSCRIBED)
                        ->setChangeStatusAt($changedAt)
                        ->save();
                    break;

            }
        }
    }

    /**
     * Run Pending Export Jobs
     *
     * @return bool
     */
    public function runAllProcesses()
    {
        if (!Mage::helper('euromsg')->getConfigData('dwh/policy') == Numerno_Euromsg_Model_Process::POLICY_ASYNC) {
            return true;
        }

        $collection = Mage::getResourceModel('euromsg/process_collection')
            ->addFieldToFilter('status', 'pending');

        if (!Mage::helper('euromsg')->getConfigData('general/enabled', 'catalog')) {
            $collection->addFieldToFilter('type', array('neq' => 'product'));
        }

        if (!Mage::helper('euromsg')->getConfigData('general/enabled', 'customer')) {
            $collection->addFieldToFilter('type', array('neq' => 'member'));
        }

        foreach ($collection as $process) {
            $process->export(true);
        }

        return true;
    }

    /**
     * Clean Mail Logs
     *
     * @return bool
     */
    public function cleanMailLogs()
    {
        if (!Mage::helper('euromsg')->getConfigData('log/autosync', 'trx')) {
            return true;
        }

        $keepLogs     = (int) Mage::helper('euromsg')->getConfigData('log/keeplogs', 'trx');
        $deleteBefore = date('Y-m-d H:i:s', strtotime("-$keepLogs days"));

        $logs  = Mage::getResourceModel('euromsg/mail_log_collection');
        $logs->addFieldToSelect($logs->getResource()->getIdFieldName())
            ->addFieldToFilter('send_at', array('lt' => $deleteBefore));

        if ($logs->count()) {
            $logs->getConnection()->delete(
                $logs->getMainTable(),
                array($logs->getResource()->getIdFieldName() . ' IN (?)' => $logs->getAllIds())
            );
        }

        return true;
    }

    /**
     * Track Mail Delivery
     *
     * @return bool
     */
    public function trackMailDelivery()
    {
        if (!Mage::helper('euromsg')->getConfigData('general/enabled', 'trx')) {
            return true;
        }
        if (!Mage::helper('euromsg')->getConfigData('track_delivery/autosync', 'trx')) {
            return true;
        }

        $client  = Mage::getSingleton('euromsg/platform');
        $logs    = Mage::getResourceModel('euromsg/mail_log_collection')
            ->addFieldToFilter('post_id', array('notnull' => true))
            ->addFieldToFilter('delivery_relay_status', array(array('neq' => 'D'), array('null' => true)));

        $tracks = $client->trackEmails($logs->getColumnValues('post_id'));

        foreach ($tracks->EmPostResult as $trackData) {

            $post = $logs->getItemByColumnValue('post_id', $trackData->PostID);
            if ($post) {

                if ($post->getDeliveryRelayStatus() != $trackData->RelayStatus) {
                    $post->setDeliveryRelayStatus($trackData->RelayStatus);
                } else {
                    continue;
                }
                if ($trackData->RelayStatus == 'D') {
                    $post->setDeliveryStatus($trackData->DeliveryStatus)
                        ->setUndeliveryReason($trackData->UndeliveryReason);
                }
                $post->save();
            }
        }
    }

    /**
     * Send SMS Queue
     *
     * @return bool
     */
    public function sendSmsQueue()
    {
        if (!Mage::helper('euromsg')->getConfigData('general/enabled', 'sms')) {
            return true;
        }

        $collection = Mage::getResourceModel('euromsg/sms_collection')
            ->addFieldToFilter('delivery_status', 'P');

        foreach ($collection as $sms) {
            $sms->_send();
        }
    }

    /**
     * Track SMS Delivery Status
     *
     * @return bool
     */
    public function trackSmsDelivery()
    {
        if (!Mage::helper('euromsg')->getConfigData('general/enabled', 'sms')) {
            return true;
        }
        if (!Mage::helper('euromsg')->getConfigData('track_delivery/autosync', 'sms')) {
            return true;
        }

        $client  = Mage::getSingleton('euromsg/platform');
        $logs    = Mage::getResourceModel('euromsg/sms_collection')
            ->addFieldToFilter('packet_id', array('notnull' => true))
            ->addFieldToFilter('delivery_status', array(array('in' => array('P', 'W')), array('null' => true)));

        foreach ($logs as $sms) {
            $trackData = $client->trackSMS($sms->getPacketId(), $sms->getType());

            if ($sms->getDeliveryStatus() != $trackData->DeliveryResult) {
                $sms->setDeliveryStatus($trackData->DeliveryResult)
                    ->setDeliveryMessage($trackData->DeliveryDetail)
                    ->save();
            }
        }
    }

    /**
     * Clean SMS Logs
     *
     * @return bool
     */
    public function cleanSmsLogs()
    {
        if (!Mage::helper('euromsg')->getConfigData('log/autosync', 'sms')) {
            return true;
        }

        $keepLogs     = (int) Mage::helper('euromsg')->getConfigData('log/keeplogs', 'sms');
        $deleteBefore = date('Y-m-d H:i:s', strtotime("-$keepLogs days"));

        $logs  = Mage::getResourceModel('euromsg/sms_collection');
        $logs->addFieldToSelect($logs->getResource()->getIdFieldName())
            ->addFieldToFilter('begin_time', array('lt' => $deleteBefore));

        if ($logs->count()) {
            $logs->getConnection()->delete(
                $logs->getMainTable(),
                array($logs->getResource()->getIdFieldName() . ' IN (?)' => $logs->getAllIds())
            );
        }

        return true;
    }
}
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
 * Helper
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Customer Attribute Prefix
     */
    const CUSTOMER_ATTR_PREFIX = 'customer_';

    /**
     * Retrieve store config
     *
     * @param string
     *
     * @return string
     */
    public function getStoreConfig($path)
    {
        return Mage::getStoreConfig('euromsg' . $path);
    }

    /**
     * Retrieve euro.message Web Service Full URL
     *
     * @param string
     *
     * @return string
     */
    public function getWsUri()
    {
        return $this->getStoreConfig('/general/platform');
    }

    /**
     * Retrieve euro.message Web Service Login Credentials
     *
     * @return array
     */
    public function getWsCredentials()
    {
        $credentials = array(
            'Username' => $this->getStoreConfig('/general/ws_user'),
            'Password' => $this->getStoreConfig('/general/ws_pass')
        );

        return $credentials;
    }

    /**
     * Retrieve sFTP Credentials
     *
     * @return array
     */
    public function getSftpCredentials($target)
    {
        $credentials = array(
            'host' => $this->getStoreConfig("/$target/sftp_host"),
            'username' => $this->getStoreConfig("/$target/sftp_user"),
            'password' => $this->getStoreConfig("/$target/sftp_pass")
        );

        return $credentials;
    }

    /**
     * Prepare sFTP Connection
     *
     * @return Numerno_Euromsg_Model_Io_Sftp
     */
    public function getSftpConnection($target = 'dwh')
    {
        $connection = Mage::getModel('euromsg/io_sftp');
        $connection->open($this->getSftpCredentials($target));

        return $connection;
    }


    /**
     * Process Feedback ZIP File
     *
     * @param string $file
     * @return void
     */
    public function processFeedbackZip($file)
    {
        $zip = new ZipArchive();
        $zip->open($file);
        for( $i = 0; $i < $zip->numFiles; $i++ ){
            $stat = $zip->statIndex( $i );
            if($stat['size']!=0){
                if(strpos($stat['name'], '.csv')) {
                    $tmpDir  = sys_get_temp_dir();
                    $file    = $tmpDir . DS . $stat['name'];
                    $zip->extractTo($tmpDir, $stat['name']);

                    if (($handle = fopen($file, "r")) !== FALSE) {
                        $header = fgets($handle);
                        if(strpos($header, 'UNSUBCRIBE') || strpos($header, 'UNSUBSCRIBE')) {
                            $headers = explode(';', $header);
                            while (($row = fgetcsv($handle, false, ";")) !== FALSE) {
                                $data = array_combine($headers, $row);
                                if(isset($data['EMAIL_ADDRESS'])) {
                                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($data['EMAIL_ADDRESS']);
                                    if($subscriber->getId())
                                        $subscriber->unsubscribe();
                                }
                            }
                        }
                        fclose($handle);
                    }
                }
            }
        }
        $zip->close();
    }

    public function getReservedColumns() {

        $columns = array(
            'subscriber_email' => Numerno_Euromsg_Model_Export_Entity_Member::COL_EMAIL,
            'subscriber_id'    => Numerno_Euromsg_Model_Export_Entity_Member::COL_KEY_ID,
        );

        $sms = Mage::helper('euromsg/sms');
        if($sms->isEnabled() && $sms->getGsmAttribute()){
            $columns[self::CUSTOMER_ATTR_PREFIX . $sms->getGsmAttribute()] = Numerno_Euromsg_Model_Sms::COL_GSM_NO;
        }

        if(Mage::getStoreConfig('euromsg_customer/general/source') == 'newsletter_subscribers_customers') {
            $columns[self::CUSTOMER_ATTR_PREFIX . 'entity_id'] = Numerno_Euromsg_Model_Export_Entity_Member::COL_KEY_ID;
        }

        return $columns;
    }

    /**
     * Retrieve Customer Attribute Prefix
     *
     * @return string
     */
    public function getCustomerAttributePrefix()
    {
        return self::CUSTOMER_ATTR_PREFIX;
    }

    /**
     * Retrieve Preset Customer Attribute option array
     *
     * @return array
     */
    public function getPresetCustomerAttributes()
    {
        $_prefix = $this->getCustomerAttributePrefix();

        $attributes = array(
            '__empty'                 => Mage::helper('euromsg')->__('Select an attribute...'),
            'subscriber_id'           => Mage::helper('euromsg')->__('Subscriber ID'),
            'subscriber_email'        => Mage::helper('euromsg')->__('Subscriber Email'),
            $_prefix . 'entity_id'    => Mage::helper('euromsg')->__('Customer ID'),
            $_prefix . 'last_login'   => Mage::helper('euromsg')->__('Customer Last Web Login At'),
            $_prefix . 'last_order'   => Mage::helper('euromsg')->__('Customer Last Order Created At'),
            $_prefix . 'orders_total' => Mage::helper('euromsg')->__('Customer Total Amount of Orders'),
            $_prefix . 'fav_category' => Mage::helper('euromsg')->__('Customer Most Ordered Category'),
            $_prefix . 'group'        => Mage::helper('euromsg')->__('Customer Group (group name)')
        );

        return $attributes;
    }

    /**
     * Retrieve Disabled Customer Attributes
     *
     * @return array
     */
    public function getDisabledCustomerAttributes()
    {
        return array('confirmation', 'default_billing', 'default_shipping', 'disable_auto_group_change',
            'password_hash', 'reward_update_notification', 'reward_warning_notification', 'rp_token',
            'rp_token_created_at', 'store_id', 'website_id');

    }

    /**
     * Retrieve Preset Product Attributes option array
     *
     * @return array
     */
    public function getPresetProductAttributes()
    {
        return array(
            'entity_id'     => Mage::helper('euromsg')->__('Product ID (entity_id)'),
            '_url'          => Mage::helper('euromsg')->__('Product URL'),
            '_attribute_set'=> Mage::helper('euromsg')->__('Attribute Set'),
            '_type'         => Mage::helper('euromsg')->__('Product Type'),
            'qty'           => Mage::helper('euromsg')->__('Qty'),
            'is_in_stock'   => Mage::helper('euromsg')->__('Stock Status'),
            '_root_category'=> Mage::helper('euromsg')->__('Root Category'),
            '_category'     => Mage::helper('euromsg')->__('Category (first, with tree)')
        );
    }

    /**
     * Zip and upload files to euro.message Data Warehouse
     *
     * @param string $filename
     * @param array $files
     * @return bool
     */
    public function wrapFiles($filename, $files)
    {
        if(!is_array($files))
            return false;

        $destination = tempnam(sys_get_temp_dir(), $filename . '.zip');

        $zip = new ZipArchive();
        $zip->open($destination, ZipArchive::CREATE);
        foreach ($files as $name => $file) {
            $zip->addFile($file, $name);
        }
        $zip->close();
        foreach ($files as $file) {
            //unlink($file);
        }
        if(file_exists($destination)) {
            $connection = $this->getSftpConnection();
            $connection->write($filename . '.zip', file_get_contents($destination));
            $connection->close();
            //unlink($destination);
        }

        return true;
    }


}
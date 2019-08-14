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
     * @param string $path
     * @param string $section
     * $param int $store Store ID
     *
     * @return string
     */
    public function getConfigData($path, $section = null, $store = null)
    {
        if (!is_null($section)) {
            $section = '_' . $section;
        }

        return Mage::getStoreConfig("euromsg$section/$path");
    }

    /**
     * Validate Customer GSM Number string
     *
     * @param string $string
     *
     * @return bool
     */
    public function validateGsmNumber($string)
    {
        $validPrefixes = array('530', '531', '532', '533', '534', '535', '536', '537', '538', '539', '540', '541',
            '542', '543', '544', '545', '546', '547', '548', '549', '500', '501', '502', '503', '504', '505', '506',
            '507', '508', '509', '550', '551', '552', '553', '554', '555', '556', '557', '558', '559');

        $numeric = preg_replace("[^0-9]", "", $string) / 1;

        return preg_match('~(?=.*[0-9])^(90|0|)((' . implode('|', $validPrefixes) . ')(.*))[0-9]{7}$~', $numeric);
    }

    /**
     * Validate Data Warehouse Table Name
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function validateTableName($tableName)
    {
        return preg_match('~^[A-Za-z][\w]{1,254}$~', $tableName);
    }

    /**
     * Filter Customer GSM Number string
     *
     * @param string $string
     *
     * @return bool
     */
    public function filterGsmNumber($string)
    {
        if (!$this->validateGsmNumber($string)) {
            return false;
        }

        $numeric = preg_replace("[^0-9]", "", $string) / 1;
        if (strlen($numeric) == 10) {
            $numeric = '90' . $numeric;
        }

        return $numeric;
    }

    /**
     * Retrieve SMS Permission of Customer by ID
     *
     * @param int $customerId
     *
     * @return bool
     */
    public function getSMSPermit($customerId) {

        $enabled = $this->getConfigData('general/enabled', 'sms');
        if ($enabled) {
            $gsmNumberAttribute = $this->getConfigData('general/attribute', 'sms');
            if ($gsmNumberAttribute) {
                $smsPermitAttribute = Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE;
                $customerCollection = Mage::getResourceModel('customer/customer_collection')
                    ->addAttributeToFilter('entity_id', $customerId)
                    ->addAttributeToSelect($smsPermitAttribute)
                    ->addAttributeToSelect($gsmNumberAttribute);

                $customer = $customerCollection->getFirstItem();
                if ($customer->getData($smsPermitAttribute)){

                    return $this->validateGsmNumber($customer->getData($gsmNumberAttribute));
                }
            }
        }

        return false;
    }

    /**
     * Prepare sFTP Connection
     *
     * @return Numerno_Euromsg_Model_Io_Sftp
     */
    public function getSftpConnection()
    {
        $connection = new Varien_Io_Sftp();
        $connection->open(array(
            'host'     => $this->getConfigData("dwh/sftp_host"),
            'username' => $this->getConfigData("dwh/sftp_user"),
            'password' => $this->getConfigData("dwh/sftp_pass")
        ));

        return $connection;
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
        $prefix = self::CUSTOMER_ATTR_PREFIX;

        return array(
            '__empty'                 => Mage::helper('euromsg')->__('Select an attribute...'),
            'subscriber_id'           => Mage::helper('euromsg')->__('Subscriber ID'),
            'subscriber_email'        => Mage::helper('euromsg')->__('Subscriber Email'),
            $prefix . 'entity_id'     => Mage::helper('euromsg')->__('Customer ID'),
            $prefix . 'last_login'    => Mage::helper('euromsg')->__('Customer Last Web Login At'),
            $prefix . 'last_order'    => Mage::helper('euromsg')->__('Customer Last Order Created At'),
            $prefix . 'orders_total'  => Mage::helper('euromsg')->__('Customer Total Amount of Orders'),
            $prefix . 'fav_category'  => Mage::helper('euromsg')->__('Customer Most Ordered Category'),
            $prefix . 'group'         => Mage::helper('euromsg')->__('Customer Group (group name)')
        );
    }

    /**
     * Retrieve Disabled Customer Attributes
     *
     * @return array
     */
    public function getDisabledCustomerAttributes()
    {
        return array('email', 'confirmation', 'default_billing', 'default_shipping', 'disable_auto_group_change',
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
            'entity_id'      => Mage::helper('euromsg')->__('Product ID (entity_id)'),
            '_url'           => Mage::helper('euromsg')->__('Product URL'),
            '_attribute_set' => Mage::helper('euromsg')->__('Attribute Set'),
            '_type'          => Mage::helper('euromsg')->__('Product Type'),
            'qty'            => Mage::helper('euromsg')->__('Qty'),
            'is_in_stock'    => Mage::helper('euromsg')->__('Stock Status'),
            '_root_category' => Mage::helper('euromsg')->__('Root Category'),
            '_category'      => Mage::helper('euromsg')->__('Category (first, with tree)')
        );
    }

    protected function _getDataTypeByColumn($column)
    {
        if (preg_match("/(char|text)/", $column['DATA_TYPE'])) {

            $length = $column['LENGTH'];
            if (is_null($length) || $length > 255) {

                return 'string(1024)';
            } else {

                return "string($length)";
            }
        }
        elseif (preg_match("/(int|bit|blob|binary)/", $column['DATA_TYPE'])) {

            return 'int';
        }
        elseif (preg_match("/(float|double|decimal)/", $column['DATA_TYPE'])) {

            return 'float';
        }
        elseif (preg_match("/(date|time|year)/", $column['DATA_TYPE'])) {

            return 'datetime';
        }

        return 'string(1024)';
    }

    protected function _getDataTypeByAttribute($attribute)
    {
        if(in_array($attribute->getFrontendInput(), array('select', 'multiselect'))) {

            return 'int';
        }

        $backendType = $attribute->getBackendType();
        $types       = array(
            'varchar'  => 'string(255)',
            'text'     => 'string(1024)',
            'decimal'  => 'float',
            'int'      => 'int',
            'datetime' => 'datetime'
        );
        if (isset($types[$backendType])) {

            return $types[$backendType];
        }

        return 'string(1024)';
    }

    public function getProductEntityDataTypes($attributeCodes = array())
    {
        //preset attributes data types
        $dataTypes = array(
            'entity_id'      => 'int',
            '_url'           => 'string(1024)',
            '_attribute_set' => 'string(255)',
            '_type'          => 'string(255)',
            'qty'            => 'float',
            'is_in_stock'    => 'int',
            '_root_category' => 'string(255)',
            '_category'      => 'string(1024)'
        );

        $columns = Mage::getSingleton('core/resource')
            ->getConnection('read')
            ->describeTable(Mage::getSingleton('core/resource')->getTableName('catalog/product'));

        foreach($columns as $columnName => $column) {
            $dataTypes[self::CUSTOMER_ATTR_PREFIX . $columnName] = $this->_getDataTypeByColumn($column);
        }

        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(Mage::getSingleton('eav/config')->getEntityType('catalog_product'))
            ->setCodeFilter($attributeCodes);

        foreach ($attributes as $attribute) {
            $dataTypes[$attribute->getAttributeCode()] = $this->_getDataTypeByAttribute($attribute);
        }

        $overwriteTypes = array(
            'image'          => 'string(1024)',
            'smallimage'     => 'string(1024)',
            'thumbnail'      => 'string(1024)'
        );

        return array_merge($dataTypes, $overwriteTypes);
    }

    public function getCustomerEntityDataTypes($attributeCodes = array())
    {
        $prefix = self::CUSTOMER_ATTR_PREFIX;

        //preset attributes data types
        $dataTypes = array(
            'entity_id'               => 'int',
            'subscriber_id'           => 'int',
            'subscriber_email'        => null,
            $prefix . 'last_login'    => 'datetime',
            $prefix . 'last_order'    => 'datetime',
            $prefix . 'orders_total'  => 'float',
            $prefix . 'fav_category'  => 'string(255)',
            $prefix . 'group'         => 'string(32)'
        );

        $columns = Mage::getSingleton('core/resource')
            ->getConnection('read')
            ->describeTable(Mage::getSingleton('core/resource')->getTableName('customer/entity'));

        foreach($columns as $columnName => $column) {
            $dataTypes[self::CUSTOMER_ATTR_PREFIX . $columnName] = $this->_getDataTypeByColumn($column);
        }

        $attributes = Mage::getModel('customer/entity_attribute_collection')
            ->setCodeFilter($attributeCodes);

        foreach ($attributes as $attribute) {
            $dataTypes[$attribute->getAttributeCode()] = $this->_getDataTypeByAttribute($attribute);
        }

        return $dataTypes;
    }

    public function removeCustomerPrefix($data)
    {
        if (is_array($data)) {
            foreach($data as $key => $row) {
                $data[$key] = preg_replace('/^' . self::CUSTOMER_ATTR_PREFIX . '/', '', $row);
            }

            return $data;
        }

        return preg_replace('/^' . self::CUSTOMER_ATTR_PREFIX . '/', '', $data);
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
        if (!is_array($files)) {
            return false;
        }

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

        if (file_exists($destination)) {
            $connection = $this->getSftpConnection();
            $connection->write($filename . '.zip', file_get_contents($destination));
            $connection->close();

            //unlink($destination);
        }

        return true;
    }
}
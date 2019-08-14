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
 * Member Export Entity
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Export_Entity_Member extends Mage_Core_Model_Abstract
{
    /**
     * Permanent column names.
     */
    const COL_EMAIL_PERMIT  = 'EMAIL_PERMIT_STATUS';
    const COL_GSM_PERMIT    = 'GSM_PERMIT_STATUS';
    const COL_EMAIL         = 'EMAIL';
    const COL_KEY_ID        = 'KEY_ID';

    /**
     * Customer ID or Subscriber ID filter cache
     *
     * @var array
     */
    protected $_filter = array();

    /**
     * Attribute codes cache.
     *
     * @var array
     */
    protected $_attrCodes = array();

    /**
     * Attribute code to its column names.
     *
     * @var array
     */
    protected $_attributeColumnNames = array();

    /**
     * Customer attributes codes cache.
     *
     * @var array
     */
    protected $_customerAttrCodes = array();

    /**
     * Category Id and Name pairs
     *
     * @var array
     */
    protected $_categoryIdToName = array();

    /**
     * Resource model.
     *
     * @var Mage_Core_Model_Resource
     */
    protected $_resource;

    /**
     * DB connection.
     *
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection;

    /**
     * Helper
     *
     * @var Numerno_Euromsg_Helper_Data
     */
    protected $_helper;

    /**
     * Csv Adapter
     *
     * @var Numerno_Euromsg_Model_Export_Adapter_Csv
     */
    protected $_writer;

    /**
     * Export Filename
     *
     * @var string
     */
    protected $_filename;

    /**
     * File list to zip
     *
     * @var array
     */
    protected $_wrap = array();

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->_helper       = Mage::helper('euromsg');
        $this->_resource     = Mage::getSingleton('core/resource');
        $this->_connection   = $this->_resource->getConnection('read');
        $this->_writer       = Mage::getSingleton('euromsg/export_adapter_csv');
        $this->_initAttributes();
    }

    /**
     * Initialize attribute option values and types.
     *
     * @return Numerno_Euromsg_Model_Export_Entity_Member
     */
    protected function _initAttributes()
    {
        $_prefix = $this->_helper->getCustomerAttributePrefix();

        foreach ($this->getAttributeCollection() as $attribute) {
            $this->_attributeColumnNames[$attribute['attribute']] = $attribute['col_name']
                . (isset($attribute['datatype']) ? '[' . $attribute['datatype'] . ']' : '');
            $this->_attrCodes[] = $attribute['attribute'];

            if(!in_array($attribute['attribute'], $this->getPresetAttributes()) ) {
                if (substr($attribute['attribute'], 0, strlen($_prefix)) == $_prefix) {
                    $this->_customerAttrCodes[] = substr($attribute['attribute'], strlen($_prefix));
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve attribute collection to export
     *
     * @return array
     */
    protected function getAttributeCollection()
    {
        $exportAttributes = Mage::getStoreConfig('euromsg_customer/general/attributes');
        if (!is_array($exportAttributes)) {
            $exportAttributes = empty($exportAttributes) ? array() : unserialize($exportAttributes);
        }

        $_prefix = $this->_helper->getCustomerAttributePrefix();
        $exportAttributes['email_permit'] = array(
            'attribute' => 'email_permit',
            'col_name'  => self::COL_EMAIL_PERMIT
        );

        $codes   = array();
        foreach($exportAttributes as $exportAttribute) {
            if(isset($exportAttribute['attribute']))
                if (substr($exportAttribute['attribute'], 0, strlen($_prefix)) == $_prefix) {
                    $codes[] = substr($exportAttribute['attribute'], strlen($_prefix));
                }
                $codes[] = $exportAttribute['attribute'];
        }

        $dataTypes = array(
            'entity_id'               => 'int',
            'subscriber_id'           => 'int',
            'subscriber_email'        => null,
            $_prefix . 'last_login'   => 'datetime',
            $_prefix . 'last_order'   => 'datetime',
            $_prefix . 'orders_total' => 'float',
            $_prefix . 'fav_category' => 'string(255)',
            $_prefix . 'group'        => 'string(32)'
        );

        $staticColumns = $this->_connection->describeTable($this->_resource->getTableName('customer/entity'));
        foreach($staticColumns as $columnName => $column) {
            $dataType = $column['DATA_TYPE'];
            if(in_array($dataType, array('char', 'text')) || strpos($dataType, 'char')
                || strpos($dataType, 'text')){
                $length = $column['LENGTH'];
                if(is_null($length) || $length > 255) {
                    $dataTypes[$_prefix . $columnName] = 'string(1024)';
                }else{
                    $dataTypes[$_prefix . $columnName] = "string($length)";
                }
            }elseif(in_array($dataType, array('int', 'bit', 'blob', 'binary')) || strpos($dataType, 'int')
                || strpos($dataType, 'binary') || strpos($dataType, 'blob'))
                $dataTypes[$_prefix . $columnName] = 'int';
            elseif(in_array($dataType, array('float', 'double', 'decimal')) )
                $dataTypes[$_prefix . $columnName] = 'float';
            elseif(in_array($dataType, array('date', 'time', 'year', 'datetime', 'timestamp')) )
                $dataTypes[$_prefix . $columnName] = 'datetime';
        }

        $attributes = Mage::getModel('customer/entity_attribute_collection')
            ->setCodeFilter($codes);
        foreach($attributes as $attribute) {
            $backendType = $attribute->getBackendType();

            switch($backendType) {
                case 'varchar':
                    $dataTypes[$_prefix . $attribute->getAttributeCode()] = 'string(255)';
                    break;
                case 'text':
                    $dataTypes[$_prefix . $attribute->getAttributeCode()] = 'string(1024)';
                    break;
                case 'int':
                    if($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect')
                        $dataTypes[$_prefix . $attribute->getAttributeCode()] = 'string(255)';
                    else
                        $dataTypes[$_prefix . $attribute->getAttributeCode()] = 'int';
                    break;
                case 'decimal':
                    $dataTypes[$_prefix . $attribute->getAttributeCode()] = 'float';
                    break;
                case 'datetime':
                    $dataTypes[$_prefix . $attribute->getAttributeCode()] = 'datetime';
                    break;
            }
        }

        foreach($exportAttributes as $key => $exportAttribute) {
            if(isset($dataTypes[$exportAttribute['attribute']])) {
                $exportAttributes[$key]['datatype'] = $dataTypes[$exportAttribute['attribute']];
            }
        }

        $sms = Mage::helper('euromsg/sms');
        if($sms->isEnabled() && in_array($sms->getGsmAttribute(), $codes) ){
            $gsmPermitAttributeCode = $_prefix . Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE;
            $exportAttributes[$gsmPermitAttributeCode] = array(
                'attribute' => $gsmPermitAttributeCode,
                'col_name'  => self::COL_GSM_PERMIT
            );
        }

        return $exportAttributes;
    }

    /**
     * Retrieve preset attributes
     *
     * @return array
     */
    protected function getPresetAttributes()
    {
        $presetAttributes = $this->_helper->getPresetCustomerAttributes();
        unset($presetAttributes['NULL']);

        return array_keys($presetAttributes);
    }

    protected function _prepareCollection(){

        $_subscriberCollection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->showCustomerInfo($this->_customerAttrCodes);

        if(!$this->useUnsubscribed())
            $_subscriberCollection->useOnlySubscribed();

        if(Mage::getStoreConfig('euromsg_customer/general/source') == 'newsletter_subscribers_customers')
            $_subscriberCollection->useOnlyCustomers();

        foreach($this->_filter as $column => $ids) {
            $_subscriberCollection->addFieldToFilter('main_table.' . $column, array('in' => $ids));
        }

        return $_subscriberCollection;
    }

    /**
     * Prepare customer last login dates
     *
     * @param  array $productIds
     * @return array
     */
    protected function _prepareLastLogins($customerIds)
    {
        if (empty($customerIds) || !in_array('customer_last_login', $this->_attrCodes)) {
            return array();
        }

        $query = $this->_connection->select()
            ->from($this->_resource->getTableName('log/customer'), array('customer_id, MAX(login_at) as last_login'))
            ->where('customer_id IN (?)', $customerIds)
            ->group('customer_id');

        return $this->_connection->fetchPairs($query);
    }

    /**
     * Prepare customer last order dates
     *
     * @param  array $productIds
     * @return array
     */
    protected function _prepareLastOrders($customerIds)
    {
        if (empty($customerIds) || !in_array('customer_last_order', $this->_attrCodes)) {
            return array();
        }

        $query = $select = $this->_connection->select()
            ->from($this->_resource->getTableName('sales/order'), array('customer_id, MAX(created_at) as last_order'))
            ->where('customer_id IN (?)', $customerIds)
            ->group('customer_id');

        return $this->_connection->fetchPairs($query);
    }

    /**
     * Prepare customer orders totals
     *
     * @param  array $productIds
     * @return array
     */
    protected function _prepareOrdersTotal($customerIds)
    {
        if (empty($customerIds) || !in_array('customer_orders_total', $this->_attrCodes)) {
            return array();
        }

        $query = $this->_connection->select()
            ->from($this->_resource->getTableName('sales/order'), array('customer_id, SUM(grand_total) as orders_total'))
            ->where('customer_id IN (?)', $customerIds)
            ->where('state = ?', Mage_Sales_Model_Order::STATE_COMPLETE)
            ->group('customer_id');

        return $this->_connection->fetchPairs($query);
    }

    /**
     * Prepare customer most ordered categories
     *
     * @param  array $productIds
     * @return array
     */
    protected function _prepareMostOrderedCategories($customerIds)
    {
        if (empty($customerIds) || !in_array('customer_fav_category', $this->_attrCodes)) {
            return array();
        }

        $countsQuery = $this->_connection->select()
            ->from(array('e' => $this->_resource->getTableName('sales/order')), array('e.customer_id'))
            ->joinLeft(array('f' => $this->_resource->getTableName('sales/order_item')), 'e.entity_id = f.order_id',
                array())
            ->joinRight(array('g' => $this->_resource->getTableName('catalog/category_product')),
                'f.product_id = g.product_id', array('g.category_id', 'count' => 'COUNT(g.category_id)'))
            ->where('e.state = ?', Mage_Sales_Model_Order::STATE_COMPLETE)
            ->where('e.customer_id IN (?)', $customerIds)
            ->group(array('e.customer_id', 'g.category_id'))
            ->order(array('e.customer_id ASC', 'count DESC'));

        $query = $this->_connection->select()
            ->from(array('counts' => $countsQuery), array('customer_id', 'category_id'))
            ->group('customer_id');

        $mostOrderedCategories = $this->_connection->fetchPairs($query);

        $this->_categoryIdToName = Mage::getSingleton('catalog/category')->getCollection()
            ->addNameToResult()
            ->addIdFilter($mostOrderedCategories)
            ->exportToArray();

        return $mostOrderedCategories;
    }

    /**
     * Prepare preset attributes data
     *
     * @param  array $productIds
     * @return array
     */
    protected function _preparePresetAttributesData($_customerIds)
    {
        if (empty($_customerIds)) {
            return array();
        }

        $presetData = array();

        try {
            $presetData['customer_last_login'] = $this->_prepareLastLogins($_customerIds);
        } catch (Exception $e) {
            $presetData['customer_last_login'] = array();
            Mage::logException($e);
        }

        try {
            $presetData['customer_last_order'] = $this->_prepareLastOrders($_customerIds);
        } catch (Exception $e) {
            $presetData['customer_last_order'] = array();
            Mage::logException($e);
        }

        try {
            $presetData['customer_orders_total'] = $this->_prepareOrdersTotal($_customerIds);
        } catch (Exception $e) {
            $presetData['customer_orders_total'] = array();
            Mage::logException($e);
        }

        try {
            $presetData['customer_fav_category'] = $this->_prepareMostOrderedCategories($_customerIds);
        } catch (Exception $e) {
            $presetData['customer_fav_category'] = array();
            Mage::logException($e);
        }

        return $presetData;
    }

    /**
     * Set Export Filter
     *
     * @param array
     * @return Numerno_Euromsg_Model_Export_Entity_Member
     */
    public function filter($filter)
    {
        $this->_filter += $filter;

        return $this;
    }

    /**
     * Set Export Filename
     *
     * @param string
     * @return Numerno_Euromsg_Model_Export_Entity_Member
     */
    public function setFilename($filename)
    {

        $destination = tempnam(sys_get_temp_dir(), $filename . '.' . $this->_writer->getFileExtension());

        $this->_wrap[$filename . '.' . $this->_writer->getFileExtension()] = $destination;
        $this->_filename = $filename;
        $this->_writer->setDestination($destination);

        return $this;
    }

    /**
     * Retrieve export filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * Use unsubscribed data
     *
     * @return bool
     */
    public function useUnsubscribed(){

       return Mage::getStoreConfig('euromsg_customer/general/use_unsubscribed');
    }

    /**
     * Export process.
     *
     * @return string
     */
    public function export()
    {
        $colNames            = $this->_attributeColumnNames;
        $collection          = $this->_prepareCollection();

        if($collection->count()) {
            $customerIds         = $collection->getColumnValues('customer_id');
            $presetAttributeData = $this->_preparePresetAttributesData($customerIds);
            $categoryNames       = $this->_categoryIdToName;

            //Set header column names
            $this->_writer->setHeaderCols($colNames);

            //Write data rows
            foreach ($collection as $row) {

                $row->addData(
                    array('email_permit' => $row->isSubscribed() ? 'Y' : 'N')
                );

                if(in_array($this->_helper->getCustomerAttributePrefix() . 'entity_id', $this->_attrCodes) )
                    $row->addData(
                        array($this->_helper->getCustomerAttributePrefix() . 'entity_id' => $row->getCustomerId())
                    );

                if($row->getCustomerId()) {
                    $presetAttributes = array_intersect_key(array_flip($this->_attrCodes), $presetAttributeData);
                    foreach($presetAttributes as $presetAttribute => $null) {

                        $value = $presetAttributeData[$presetAttribute][$row->getCustomerId()];
                        if($presetAttribute == 'customer_fav_category') {
                            $row->addData(
                                array($presetAttribute => $categoryNames[$value]['name'])
                            );
                        } else {
                            $row->addData(
                                array($presetAttribute => $value)
                            );
                        }
                    }
                }

                $rowData = array_intersect_key($row->getData(), array_flip($this->_attrCodes));
                foreach($rowData as $attrCode => $value) {
                    $value = str_ireplace($this->_writer->getDelimiter(), '', $value);

                    if(is_numeric($value)) {
                        $value = round($value, 2);
                    }

                    if(Zend_Date::isDate($value, Zend_Date::ISO_8601)) {
                        $value = date("Y-m-d", strtotime($value));
                    }

                    if($attrCode == $this->_helper->getCustomerAttributePrefix() .
                        Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE) {
                        $value = $value ? 'Y' : 'N';
                    }

                    $rowData[$colNames[$attrCode]] = is_null($value) ? '' : $value;
                    unset($rowData[$attrCode]);
                }

                try{
                    $this->_writer->writeRow($rowData);
                }
                catch(Exception $e) {
                    Mage::logException($e);
                }

            }

            $notification = Mage::getStoreConfig('euromsg_customer/general/notify');

            if($notification) {
                $destination = tempnam(sys_get_temp_dir(),$this->getFilename() . '.xml');
                $this->_wrap[$this->getFilename() . '.xml'] = $destination;
                $xml = Mage::helper('core')->assocToXml(array('NOTIFICATION_EMAIL' => $notification), 'euro.message');
                file_put_contents($destination, explode("\n", $xml->asXML(), 2)[1]);
            }

            $this->_helper->wrapFiles($this->getFilename(), $this->_wrap);

        } else {
            Mage::throwException($this->_helper->__('There is no member data to export.'));
        }


    }
}
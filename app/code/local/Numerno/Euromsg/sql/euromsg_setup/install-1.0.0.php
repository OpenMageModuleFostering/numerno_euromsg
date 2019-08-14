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

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Add Customer Attribute
 */
$attributeCode = Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE;
$installer->addAttribute('customer', $attributeCode,  array(
    'type'     => 'int',
    'backend'  => 'customer/attribute_backend_data_boolean',
    'label'    => 'SMS Permit',
    'input'    => 'boolean',
    'visible'  => true,
    'required' => false,
    'default'  => 0,
    'unique'   => false
));

/**
 * Add Customer Attribute to Default Attribute Set and Attribute Group
 */
$setup            = new Mage_Eav_Model_Entity_Setup('core_setup');
$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$attribute        = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode);

$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    $attributeCode,
    '100'
);
$attribute
    ->setData('used_in_forms', array(
        'adminhtml_customer',
        'checkout_register',
        'customer_account_create',
        'customer_account_edit'
    ))
    ->setData('is_used_for_customer_segment', true)
    ->setData('is_system', 0)
    ->setData('is_user_defined', 1)
    ->setData('is_visible', 1)
    ->setData('sort_order', 100)
    ->save();

/**
 * Create table 'euromsg/process'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('euromsg/process'))
    ->addColumn('process_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Process Id')
    ->addColumn('table_name', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
        'nullable'  => false,
        'default'   => null,
    ), 'Data Warehouse Table Name')
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
        'nullable'  => false,
        'default'   => null,
    ), 'Source Type')
    ->addColumn('filter', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Filter')
    ->addColumn('version', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Table Version')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
        'nullable'  => false,
        'default'   => 'pending',
    ), 'Process Status')
    ->addColumn('error', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Process Error')
    ->addColumn('scheduled_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
    ), 'Scheduled At')
    ->addColumn('started_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
    ), 'Started At')
    ->addColumn('ended_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Ended At')
    ->setComment('euro.message Processes');
$installer->getConnection()->createTable($table);

/**
 * Create table 'euromsg/mail_log'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('euromsg/mail_log'))
    ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Process Id')
    ->addColumn('post_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Post Web Service ID')
    ->addColumn('send_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Scheduled At')
    ->addColumn('response_code', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Post Web Service Response Code')
    ->addColumn('response_message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Post Web Service Response Message')
    ->addColumn('response_message_detailed', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Post Web Service Response Message Detailed')
    ->addColumn('marked_spam', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
    ), 'Marked Spam')
    ->addColumn('mail_subject', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Email Subject')
    ->addColumn('mail_body', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Email Body')
    ->addColumn('mail_charset', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Email Character Set')
    ->addColumn('mail_to_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Email To Name')
    ->addColumn('mail_to_address', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Email To Address')
    ->addColumn('mail_type', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Email Type')
    ->addColumn('delivery_relay_status', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Delivery Relay Status')
    ->addColumn('delivery_status', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Delivery Status')
    ->addColumn('undelivery_reason', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Undelivery Reason')
    ->setComment('euro.message Mail Logs');
$installer->getConnection()->createTable($table);

/**
 * Create table 'euromsg/sms'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('euromsg/sms'))
    ->addColumn('sms_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'SMS Id')
    ->addColumn('packet_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        'nullable'  => true,
        'default'   => null,
    ), 'PostSms Web Service Packet ID')
    ->addColumn('begin_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Begin Time')
    ->addColumn('response_code', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable'  => true,
        'default'   => null,
    ), 'PostSms Web Service Response Code')
    ->addColumn('response_message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'PostSms Web Service Response Message')
    ->addColumn('response_message_detailed', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'PostSms Web Service Response Message Detailed')
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 16, array(
        'nullable'  => true,
        'default'   => null,
    ), 'SMS Type')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ), 'Customer Id')
    ->addColumn('gsm_number', Varien_Db_Ddl_Table::TYPE_TEXT, 16, array(
        'nullable'  => true,
        'default'   => null,
    ), 'GSM Number')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'SMS Message')
    ->addColumn('delivery_status', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Delivery Status')
    ->addColumn('delivery_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Delivery Time')
    ->addColumn('delivery_message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
        'default'   => null,
    ), 'Delivery Message')
    ->addIndex($installer->getIdxName('euromsg/sms', array('customer_id')),
        array('customer_id'))
    ->addForeignKey($installer->getFkName('euromsg/sms', 'customer_id', 'customer/entity', 'entity_id'),
        'customer_id', $installer->getTable('customer/entity'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('euro.message SMS Logs');
$installer->getConnection()->createTable($table);

$installer->endSetup();

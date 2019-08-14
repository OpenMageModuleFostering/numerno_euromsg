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
 * Sms Logs Grid
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Sms_Logs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('smslogsGrid');
        $this->setDefaultSort('sms_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $_config    = Mage::getSingleton('eav/config');
        $collection = Mage::getModel('euromsg/sms')->getCollection();
        $firstname  = $_config->getAttribute('customer', 'firstname');
        $lastname   = $_config->getAttribute('customer', 'lastname');

        $collection->getSelect()
            ->joinLeft(
                array('customer_' . $firstname->getAttributeCode() . '_table' => $firstname->getBackendTable()),
                'customer_' . $firstname->getAttributeCode() . '_table.entity_id = main_table.customer_id' .
                    ' AND customer_' . $firstname->getAttributeCode() . '_table.attribute_id = ' .
                    ((int) $firstname->getAttributeId()),
                array('firstname' => 'value')
            )
            ->joinLeft(
                array('customer_' . $lastname->getAttributeCode() . '_table' => $lastname->getBackendTable()),
                'customer_' . $lastname->getAttributeCode() . '_table.entity_id = main_table.customer_id' .
                    ' AND customer_' . $lastname->getAttributeCode() . '_table.attribute_id = ' .
                    ((int) $lastname->getAttributeId()),
                array('lastname' => 'value')
            )
            ->columns(new Zend_Db_Expr('CONCAT(`customer_' . $firstname->getAttributeCode() .
                '_table`.`value`, \' \', `customer_' . $lastname->getAttributeCode() .
                '_table`.`value`) AS customer_name'));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('sms_id', array(
            'header' => Mage::helper('euromsg')->__('ID #'),
            'index'  => 'sms_id'
        ));
        $this->addColumn('begin_time', array(
            'header' => Mage::helper('euromsg')->__('Send At'),
            'index'  => 'begin_time',
            'type' => 'datetime'
        ));
        $this->addColumn('type', array(
            'header' => Mage::helper('euromsg')->__('Type'),
            'index'  => 'type'
        ));
        $this->addColumn('customer_name', array(
            'header' => Mage::helper('euromsg')->__('Customer'),
            'index'  => 'customer_name',
            'renderer'  => 'Numerno_Euromsg_Block_Adminhtml_Sms_Renderer_Customer'
        ));
        $this->addColumn('gsm_number', array(
            'header' => Mage::helper('euromsg')->__('GSM Number'),
            'index'  => 'gsm_number'
        ));
        $this->addColumn('message', array(
            'header' => Mage::helper('euromsg')->__('Message'),
            'index'  => 'message'
        ));

        $delivery = Mage::helper('euromsg')->getConfigData('log/track_delivery', 'sms');
        if ($delivery) {
            $this->addColumn('delivery_status', array(
                'header'  => Mage::helper('euromsg')->__('Delivery Status'),
                'index'   => 'delivery_status',
                'type'    => 'options',
                'options' => Mage::getModel('euromsg/system_config_source_customer_deliveryStatus')->toArray()
            ));
        }

        return parent::_prepareColumns();
    }
}
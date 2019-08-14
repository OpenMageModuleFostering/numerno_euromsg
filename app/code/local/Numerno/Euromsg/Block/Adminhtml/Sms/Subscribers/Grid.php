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
 * Sms Subscribers Grid
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Sms_Subscribers_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('smssubscribersGrid');
        $this->setDefaultSort('customer_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $helper = Mage::helper('euromsg/sms');
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect((string) Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE);

        if($helper->getGsmAttribute())
            $collection->addAttributeToSelect((string) $helper->getGsmAttribute());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('euromsg/sms');

        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('customer')->__('ID'),
            'width'     => '50px',
            'index'     => 'entity_id',
            'type'  => 'number',
        ));
        if($helper->getGsmAttribute())
            $this->addColumn((string) $helper->getGsmAttribute(), array(
                'header' => Mage::helper('euromsg')->__('GSM Number'),
                'index'  => (string) $helper->getGsmAttribute()
            ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('customer')->__('Name'),
            'index'     => 'name'
        ));
        $this->addColumn('email', array(
            'header'    => Mage::helper('customer')->__('Email'),
            'width'     => '150',
            'index'     => 'email'
        ));
        $this->addColumn((string) Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE, array(
            'header'  => Mage::helper('euromsg')->__('Is Subscribed?'),
            'index'   => (string) Numerno_Euromsg_Model_Sms::SMS_PERMIT_ATTRIBUTE_CODE,
            'type'    => 'options',
            'options' => Mage::getModel('euromsg/system_config_source_sms_permit')->toArray()
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/customer/edit/', array('id'=>$row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('customer');

        $this->getMassactionBlock()->addItem('sms_subscribe', array(
            'label'    => Mage::helper('customer')->__('Subscribe'),
            'url'      => $this->getUrl('*/*/massSubscribe')
        ));

        $this->getMassactionBlock()->addItem('sms_unsubscribe', array(
            'label'    => Mage::helper('customer')->__('Unsubscribe'),
            'url'      => $this->getUrl('*/*/massUnsubscribe')
        ));

        return $this;
    }
}
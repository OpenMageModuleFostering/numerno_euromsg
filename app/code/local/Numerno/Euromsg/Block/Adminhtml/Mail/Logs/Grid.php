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
 * Mail Logs Grid
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Mail_Logs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('maillogsGrid');
        $this->setDefaultSort('log_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('euromsg/mail_log_collection');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('log_id', array(
            'header' => Mage::helper('euromsg')->__('ID #'),
            'index'  => 'log_id'
        ));
        $this->addColumn('send_at', array(
            'header' => Mage::helper('euromsg')->__('Send At'),
            'index'  => 'send_at',
            'type' => 'datetime'
        ));
        $this->addColumn('mail_to_name', array(
            'header' => Mage::helper('euromsg')->__('Name'),
            'index'  => 'mail_to_name'
        ));
        $this->addColumn('mail_to_address', array(
            'header' => Mage::helper('euromsg')->__('E-mail Address'),
            'index'  => 'mail_to_address'
        ));
        $this->addColumn('mail_subject', array(
            'header' => Mage::helper('euromsg')->__('Subject'),
            'index'  => 'mail_subject'
        ));
        $this->addColumn('marked_spam', array(
            'header'  => Mage::helper('euromsg')->__('Is Marked Spam?'),
            'index'   => 'marked_spam',
            'type'    => 'options',
            'options' => Mage::getModel('adminhtml/system_config_source_yesno')->toArray()
        ));

        $delivery = Mage::helper('euromsg')->getConfigData('log/track_delivery', 'trx');
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
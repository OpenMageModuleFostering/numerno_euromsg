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
 * Customer Attribute Form Field
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Form_Field_CustomerAttribute extends Mage_Core_Block_Html_Select
{
    /**
     * Attributes cache
     *
     * @var array
     */
    private $_attributes;

    /**
     * Retrieve allowed attributes
     *
     * @param int $storeId
     * @return array
     *
     */
    protected function _getAttributes()
    {
        if (is_null($this->_attributes)) {

            $this->_attributes  = Mage::helper('euromsg')->getPresetCustomerAttributes();
            $collection         = Mage::getModel('eav/entity_attribute')->getCollection();

            $collection->setEntityTypeFilter(Mage::getSingleton('eav/config')->getEntityType('customer'));
            $collection->addFieldToFilter(
                'attribute_code',
                array('nin' => Mage::helper('euromsg')->getDisabledCustomerAttributes())
            );

            foreach ($collection as $item) {
                $this->_attributes[Mage::helper('euromsg')->getCustomerAttributePrefix() . $item->getAttributeCode()] =
                    Mage::helper('euromsg')->__('Customer ') . $item->getFrontendLabel() . ' (' .
                    $item->getAttributeCode() . ')';
            }
        }

        return $this->_attributes;
    }

    /**
     * Set form element input name
     *
     * @param string $value
     * @return string
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getAttributes() as $id => $label) {
                $this->addOption($id, $label);
            }
        }

        return parent::_toHtml();
    }
}
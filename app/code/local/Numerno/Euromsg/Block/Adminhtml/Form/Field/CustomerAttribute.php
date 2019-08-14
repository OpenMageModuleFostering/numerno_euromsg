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
    protected function _getAttributes($storeId = null)
    {
        $_helper = Mage::helper('euromsg');

        if (is_null($this->_attributes)) {

            $this->_attributes = $_helper->getPresetCustomerAttributes();
            $_hideAttributes = $_helper->getDisabledCustomerAttributes();

            $collection = Mage::getModel('eav/entity_attribute')->getCollection();
            $collection->setEntityTypeFilter(Mage::getSingleton('eav/config')->getEntityType('customer'));
            $collection->addFieldToFilter('attribute_code', array('neq' => 'email'));

            foreach ($collection as $item) {
                if(!in_array($item->getAttributeCode(), $_hideAttributes))
                    $this->_attributes[$_helper->getCustomerAttributePrefix() . $item->getAttributeCode()] =
                        $_helper->__('Customer ') . $item->getFrontendLabel() . ' (' . $item->getAttributeCode() . ')';
            }
        }
        if (!is_null($storeId)) {
            return isset($this->_attributes[$storeId]) ? $this->_attributes[$storeId] : null;
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
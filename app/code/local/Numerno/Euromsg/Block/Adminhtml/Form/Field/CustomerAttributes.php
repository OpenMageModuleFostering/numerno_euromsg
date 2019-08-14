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
 * Customer Attributes Form Field
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Form_Field_CustomerAttributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    /**
     * Attribute renderer cache
     */
    protected $_attributeRenderer;

    /**
     * Retrieve attribute renderer
     */
    protected function _getAttributeRenderer()
    {
        if (!$this->_attributeRenderer) {
            $this->_attributeRenderer = $this->getLayout()->createBlock(
                'euromsg/adminhtml_form_field_customerAttribute', '',
                array('is_render_to_js_template' => true)
            );
            $this->_attributeRenderer->setClass('attribute_select');
            $this->_attributeRenderer->setExtraParams('style="width:200px"');
        }
        return $this->_attributeRenderer;
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('attribute', array(
            'label' => Mage::helper('euromsg')->__('Attribute'),
            'renderer' => $this->_getAttributeRenderer(),
        ));
        $this->addColumn('col_name', array(
            'label' => Mage::helper('euromsg')->__('Column Name'),
            'style' => 'width:100px',
        ));

        $this->_addAfter = true;
        $this->_addButtonLabel = Mage::helper('euromsg')->__('Add Attribute');
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getAttributeRenderer()->calcOptionHash($row->getData('attribute')),
            'selected="selected"'
        );
    }

    /**
     * Render cell template
     *
     * @param string
     */
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }

        return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
        ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
        (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
        (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
    }

    /**
     * Retrieve Html Form ID
     *
     * @return string
     */
    public function getHtmlId()
    {
        return '_emCustomerAttributes';
    }

    /**
     * Render Block Template
     *
     * @return string
     */
    public function _toHtml()
    {
        $reservedColumns = Mage::helper('euromsg')->getReservedColumns();
        $html = parent::_toHtml();
        $html = $html . '
        <script type="text/javascript">
            /*var emLockedAttributes = ["'.implode('","', array_keys($reservedColumns)).'"];
            var emLockedValues = '.json_encode($reservedColumns).';

            $$(".attribute_select").invoke(\'observe\', \'change\', function() {
                em_input = $(this).up(\'td\').next(\'td\').down(\'input[type=text]\');
                if(emLockedAttributes.indexOf(this.value) >= 0){
                    em_input.value    = emLockedValues[this.value];
                    em_input.disable();
                } else {
                    em_input.enable();
                }
            });

            document.observe(\'dom:loaded\', function() {
                $$(".attribute_select").each(function(select) {
                    em_input = $(select).up(\'td\').next(\'td\').down(\'input[type=text]\');
                    if(emLockedAttributes.indexOf(select.value) >= 0){
                        em_input.value    = emLockedValues[select.value];
                        em_input.disable();
                    }
                });
            });*/
        </script>';
        return $html;
    }
}
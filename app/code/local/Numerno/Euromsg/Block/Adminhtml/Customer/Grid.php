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
 * Customer Grid
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Block_Adminhtml_Customer_Grid extends Mage_Adminhtml_Block_Customer_Grid
{
    /*
     * Add export type
     *
     * @return Numerno_Euromsg_Block_Adminhtml_Customer_Grid
     */
    protected function _prepareColumns()
    {

        $this->addExportType('*/adminhtml_euromsg/export', 'euro.message');
        return parent::_prepareColumns();
    }

    /*
     * Add export type layout
     *
     * @return Numerno_Euromsg_Block_Adminhtml_Customer_Grid
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setChild('export_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'      => Mage::helper('adminhtml')->__('Export'),
                    'onclick'    => 'doExport()',
                    'class'      => 'task',
                    'after_html' => $this->getLayout()
                        ->createBlock('euromsg/adminhtml_grid_exportJs')
                        ->setData(array('object_name' => $this->getJsObjectName()))
                        ->toHtml()
                ))
        );
        return $this;
    }
}
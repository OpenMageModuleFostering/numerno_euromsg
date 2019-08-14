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
 * Newsletter Subscriber Collection
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Newsletter_Subscriber_Collection
    extends Mage_Newsletter_Model_Resource_Subscriber_Collection
{

    /**
     * Adds customer info to select
     *
     * @return Numerno_Euromsg_Model_Newsletter_Subscriber_Collection
     */
    public function showCustomerInfo($attributes = array('firstname', 'lastname', 'middlename'))
    {
        $adapter    = $this->getConnection();
        $customer   = Mage::getModel('customer/customer');
        $resource   = Mage::getSingleton('core/resource');

        foreach($attributes as $attribute_code) {

            $attribute = $customer->getAttribute($attribute_code);

            if ($attribute->getBackend()->isStatic()) {
                $this->getSelect()
                    ->joinLeft(
                        array('customer_' . $attribute_code . '_table' => $attribute->getBackendTable()),
                        $adapter->quoteInto('customer_' . $attribute_code . '_table.entity_id=main_table.customer_id'),
                        array('customer_' . $attribute_code => $attribute_code)
                    );
            } elseif ($attribute->usesSource()) {
                $this->getSelect()
                    ->joinLeft(
                        array('customer_' . $attribute_code . '_table' => $attribute->getBackendTable()),
                        $adapter->quoteInto('customer_' . $attribute_code . '_table.entity_id=main_table.customer_id
                        AND customer_' . $attribute_code . '_table.attribute_id = ?', (int)$attribute->getAttributeId()
                        ),
                        array('customer_' . $attribute_code . '_option_id' => 'value')
                    )->joinLeft(
                        array(
                            'customer_' . $attribute_code
                                . '_value_table' => $resource->getTableName('eav/attribute_option_value')),
                            $adapter->quoteInto('customer_' . $attribute_code . '_value_table.option_id=customer_'
                                . $attribute_code . '_table.value AND customer_' . $attribute_code
                                . '_value_table.store_id = ?',
                            (int)$customer->getStoreId()
                        ),
                        array('customer_' . $attribute_code => 'value')
                    );
            } else {
                $this->getSelect()
                    ->joinLeft(
                        array('customer_' . $attribute_code . '_table' => $attribute->getBackendTable()),
                        $adapter->quoteInto('customer_' . $attribute_code . '_table.entity_id=main_table.customer_id
                        AND customer_' . $attribute_code . '_table.attribute_id = ?', (int)$attribute->getAttributeId()
                        ),
                        array('customer_' . $attribute_code => 'value')
                    );
            }
        }

        return $this;
    }

}
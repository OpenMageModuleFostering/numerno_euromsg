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
 * Euromsg Controller
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Adminhtml_EuromsgController extends Mage_Adminhtml_Controller_Action
{
    public function syncNowAction()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $sync = $this->getRequest()->getPost('sync');

        switch($sync) {
            case 'member':
                $tableName = Mage::getStoreConfig('euromsg_customer/general/filename');

                $validatorTableName = new Zend_Validate_Regex(array('pattern' => '/^[A-Za-z][A-Za-z_0-9]{1,254}$/'));
                if (!$validatorTableName->isValid($tableName)) {
                    $session->addError(
                        Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only '
                            . 'letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a'
                            . ' letter.')
                    );
                }
                $process = Mage::getModel('euromsg/process')
                    ->setTableName($tableName)
                    ->setType('member');
                try {
                    //export process
                    $process->export();

                    $session->addSuccess(
                        Mage::helper('euromsg')->__('All member data successfully exported to euro.message platform.')
                    );
                }catch(Exception $e) {
                    $session->addError(
                        $e->getMessage()
                    );
                }
                break;
            case 'product':
                $tableName = Mage::getStoreConfig('euromsg_catalog/general/filename');
                $validatorTableName = new Zend_Validate_Regex(array('pattern' => '/^[A-Za-z][A-Za-z_0-9]{1,254}$/'));
                if (!$validatorTableName->isValid($tableName)) {
                    $session->addError(
                        Mage::helper('euromsg')->__('Default data warehouse table name is invalid. Please use only '
                            . 'letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a'
                            . ' letter.')
                    );
                }
                $process = Mage::getModel('euromsg/process')
                    ->setTableName($tableName)
                    ->setType('product');
                try {
                    //export process
                    $process->export();

                    $session->addSuccess(
                        Mage::helper('euromsg')->__('All product data successfully exported to euro.message platform.')
                    );
                }catch(Exception $e) {
                    $session->addError(
                        $e->getMessage()
                    );
                }
                break;
            default:
                $session->addError(
                    Mage::helper('euromsg')->__('Cannot process the request.')
                );
                break;
        }
    }

    public function exportAction()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $params = $this->getRequest()->getParams();

        //validate dwh table name
        if (isset($params['dwhname'])) {
            $validatorTableName = new Zend_Validate_Regex(array('pattern' => '/^[A-Za-z][A-Za-z_0-9]{1,254}$/'));
            if (!$validatorTableName->isValid($params['dwhname'])) {
                $session->addError(
                    Mage::helper('euromsg')->__('Data warehouse table name is invalid. Please use only letters (a-z), '
                        . 'numbers (0-9) or underscore(_) in this field, first character should be a letter.')
                );
                return;
            }
        }

        $process = Mage::getModel('euromsg/process')
            ->setTableName($params['dwhname'])
            ->setType('member');

        if(isset($params['internal_customer'])) {
            $process->addFilter('customer_id', explode(',', $params['internal_customer']));
        }

        if(isset($params['internal_subscriber'])) {
            $process->addFilter('subscriber_id', explode(',', $params['internal_subscriber']));
        }

        try {
            //export process
            $process->export();
            $session->addSuccess(
                Mage::helper('euromsg')->__('%s table successfully exported to euro.message platform.',
                    $params['dwhname'])
            );
        }catch(Exception $e) {
            $session->addError(
                $e->getMessage()
            );
        }


    }

}
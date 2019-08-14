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
 * Process
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Process extends Mage_Core_Model_Abstract
{

    const POLICY_SYNC  = 'sync';
    const POLICY_ASYNC = 'async';

    protected $_eventPrefix = 'euromsg_process';
    protected $_eventObject = 'process';

    /**
     * Entity filters cache
     * @var array $_filters
     */
    protected $_filters = array();

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('euromsg/process');
    }

    /**
     * Prepare table version for process
     *
     * @return void
     */
    protected function _prepareVersion()
    {
        //TODO: Get last version or optimize method
        $tableName = $this->getTableName();
        $versionInc = 0;

        do {
            $versionInc++;
            $version = date("Ymd") . str_pad($versionInc, 3, '0', STR_PAD_LEFT);
        } while (!$this->_getResource()->isVersionUnique($tableName, $version));

        $this->setVersion($version);
    }

    /*
     * Check if process is incremental
     *
     * @return bool
     */
    public function isIncremental()
    {
        if ($this->getVersion()) {

            return true;
        }

        return !$this->_getResource()->isTableNameUnique($this->getTableName());

    }

    /*
     * Add filter to entity collection
     *
     * @param string $attribute
     * @param array $value
     * @return Numerno_Euromsg_Model_Process
     */
    public function addFilter($attribute, $value)
    {
        $filters = array();

        if ($this->getFilter()) {
            $filters = unserialize($this->getFilter());
        }

        $filters[] = array($attribute => $value);

        $this->setFilter(serialize($filters));

        return $this;
    }

    /*
     * Create a filename
     *
     * @return string
     */
    public function getFilename()
    {
        if ($this->getVersion()) {

            return $this->getTableName() . '_' . $this->getVersion() . '_inc';
        } else {

            return $this->getTableName();
        }
    }

    /*
     * Export
     *
     * @return Numerno_Euromsg_Model_Process
     */
    protected function _export()
    {
        if (!$this->getType()) {
            Mage::throwException('Export type need to be set before export process.');
        }

        if (!($entity = Mage::getModel('euromsg/export_entity_' . $this->getType()))) {
            Mage::throwException('Unknown entity type.');
        }

        $_date = Mage::getModel('core/date');
        if ($this->getScheduledAt() > $_date->date('Y-m-d H:i:s')) {

            return $this;
        }

        try {

            $entity->setFilename($this->getFilename());

            if($this->getFilter()) {
                $filters = unserialize(($this->getFilter()));

                foreach($filters as $filter) {
                    $entity->filter($filter);
                }
            }

            $this
                ->setStatus('processing')
                ->setStartedAt($_date->date('Y-m-d H:i:s'))
                ->save();

            $entity->export();

            $this
                ->setStatus('success')
                ->setEndedAt($_date->date('Y-m-d H:i:s'))
                ->save();

        }
        catch(Exception $e) {
            $this
                ->setStatus('error')
                ->setError($e->getMessage())
                ->save();

            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /*
     * Process export action
     */
    public function export($overridePolicy = false)
    {
        $_date = Mage::getModel('core/date');
        $policy = Mage::helper('euromsg')->getConfigData('dwh/policy');

        if (!$this->getTableName()) {
            Mage::throwException('Table name can not be null.');
        }

        if ($this->getType() != 'product' && $this->isIncremental()) {
            $this->_prepareVersion();
        }

        if ($policy == self::POLICY_SYNC || $overridePolicy) {

            return $this->_export();
        } else {
            $this->setStatus('pending')
                ->setScheduledAt($_date->date('Y-m-d H:i:s'))
                ->save();
        }

        return $this;

    }
}
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
 * CSV Adapter
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Export_Adapter_Csv extends Mage_ImportExport_Model_Export_Adapter_Csv
{
    /**
     * Field delimiter.
     *
     * @var string
     */
    protected $_delimiter = '|';

    /**
     * Get field delimiter.
     *
     * @var string
     */
    public function getDelimiter()
    {
        return $this->_delimiter;
    }

    /**
     * Set Destionation
     *
     * @param string
     *
     * @return Numerno_Euromsg_Model_Export_Adapter_Csv
     */
    public function setDestination($destination)
    {
        if (!is_string($destination)) {
            Mage::throwException(Mage::helper('euromsg')->__('Destination file path must be a string'));
        }

        $pathinfo = pathinfo($destination);
        if (empty($pathinfo['dirname']) || !is_writable($pathinfo['dirname'])) {
            Mage::throwException(Mage::helper('euromsg')->__('Destination directory is not writable'));
        }

        if (is_file($destination) && !is_writable($destination)) {
            Mage::throwException(Mage::helper('euromsg')->__('Destination file is not writable'));
        }

        $this->_destination = $destination;

        $this->_init();

        return $this;
    }

    /**
     * Return file extension for export.
     *
     * @return string
     */
    public function getFileExtension()
    {
        return 'txt';
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @throws Exception
     * @return Mage_ImportExport_Model_Export_Adapter_Abstract
     */
    public function writeRow(array $rowData)
    {
        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }

        /**
         * Security enchancement for CSV data processing by Excel-like applications.
         * @see https://bugzilla.mozilla.org/show_bug.cgi?id=1054702
         */
        $data = array_merge($this->_headerCols, array_intersect_key($rowData, $this->_headerCols));
        foreach ($data as $key => $value) {
            if (substr($value, 0, 1) === '=') {
                $data[$key] = ' ' . $value;
            }
        }

        fputs($this->_fileHandler, implode($data, $this->_delimiter) . "\r\n");

        return $this;
    }

}
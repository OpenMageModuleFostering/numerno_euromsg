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
 * Cron Schedule Backend Model
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_System_Config_Backend_Cron extends Mage_Core_Model_Config_Data
{
    /**
     * Cron settings after save
     *
     * @return Mage_Adminhtml_Model_System_Config_Backend_Log_Cron
     */
    protected function _afterSave()
    {
        $autoSync  = $this->getFieldsetDataValue('autosync');
        $frequency = $this->getFieldsetDataValue('frequency');

        $frequencyHourly    = Numerno_Euromsg_Model_System_Config_Source_Cron_Frequency::CRON_HOURLY;
        if ($autoSync) {
            if (!$frequency) {
                $cronExprString = '*/5 * * * *';

            }elseif ($frequency == $frequencyHourly) {

                $hour = $this->getFieldsetDataValue('hour');
                if ($hour && is_int(24/$hour)) {
                    $cronExprString = "0 */$hour * * *";
                }

            } else {
                $time               = $this->getFieldsetDataValue('time');
                $frequencyWeekly    = Numerno_Euromsg_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
                $frequencyMonthly   = Numerno_Euromsg_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;

                $cronExprArray = array(
                    intval($time[1]),                                   # Minute
                    intval($time[0]),                                   # Hour
                    ($frequency == $frequencyMonthly) ? '1' : '*',      # Day of the Month
                    '*',                                                # Month of the Year
                    ($frequency == $frequencyWeekly) ? '1' : '*',       # Day of the Week
                );
                $cronExprString = implode(' ', $cronExprArray);
            }
        }

        $fieldConfig  = $this->getData('field_config');
        $jobName      = $fieldConfig->job;
        $cronSchedule = "crontab/jobs/$jobName/schedule/cron_expr";
        $cronModel    = "crontab/jobs/$jobName/run/model";

        try {
            Mage::getModel('core/config_data')
                ->load($cronSchedule, 'path')
                ->setValue($cronExprString)
                ->setPath($cronSchedule)
                ->save();

            Mage::getModel('core/config_data')
                ->load($cronModel, 'path')
                ->setValue((string) Mage::getConfig()->getNode($cronModel))
                ->setPath($cronModel)
                ->save();
        }
        catch (Exception $e) {
            Mage::throwException(Mage::helper('euromsg')->__('Unable to save the cron expression.'));
        }

    }
}


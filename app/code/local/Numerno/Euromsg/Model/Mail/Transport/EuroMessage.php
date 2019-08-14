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
 * euro.message MTA (Mail Transport Agent)
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Mail_Transport_EuroMessage extends Zend_Mail_Transport_Sendmail
{
    /**
     * Send a mail using this transport
     *
     * @param  Zend_Mail $mail
     * @access public
     * @return void
     * @throws Zend_Mail_Transport_Exception if mail is empty
     */
    public function send(Zend_Mail $mail)
    {
        try {
            $client  = Mage::getModel('euromsg/platform');
            $helper  = Mage::helper('euromsg');
            $logging = $helper->getConfigData('log/enabled', 'trx');

            //TODO: TIA
            if($mail->getCharset())
                $client->setCharset($mail->getCharset());

            $mailType = isset($mail->template) ? $mail->template : 'general';
            $headers  = $mail->getHeaders();
            $headersToCheck = array('To');

            //TODO: CL
            foreach($headersToCheck as $header) {
                if(isset($headers[$header])) {
                    unset($headers[$header]['append']);
                    $recipients = $headers[$header];
                    foreach($recipients as $key => $recipient) {

                        //SEND
                        $result = $client->sendEmail(
                            $recipient,
                            $headers['Subject'][0],
                            $mail->getBodyHtml()->getRawContent(),
                            $mailType
                        );

                        $log = Mage::getModel('euromsg/mail_log')
                            ->setPostId($result->PostID)
                            ->setSendAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'))
                            ->setResponseCode($result->Code)
                            ->setResponseMessage($result->Message)
                            ->setResponseMessageDetailed($result->DetailedMessage)
                            ->setMarkedSpam($result->MarkedSpam);

                        if ($logging) {
                            $log->setMailSubject(mb_decode_mimeheader($headers['Subject'][0]))
                                ->setMailBody($mail->getBodyHtml()->getRawContent())
                                ->setMailCharset($client->getCharset())
                                ->setMailToName(mb_decode_mimeheader($recipient['name']))
                                ->setMailToAddress($recipient['email'])
                                ->setMailType($mailType);
                        }

                        $log->save();
                    }
                }
            }

        } catch(Exception $e) {
            //TODO: TIA
            Mage::logException($e);
        }


    }

    /**
     * Temporary error handler for PHP native mail().
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @param array  $errcontext
     * @return true
     */
    public function _handleMailErrors($errno, $errstr, $errfile = null, $errline = null, array $errcontext = null)
    {
        $this->_errstr = $errstr;
        return true;
    }

}

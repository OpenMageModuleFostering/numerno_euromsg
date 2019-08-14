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
 * Platform
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Platform extends Numerno_Euromsg_Model_Platform_Abstract
{

    /**
     * Constructor
     *
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($wsdl = null, $options = null)
    {

        try {
            $_wsUri = $this->getHelper()->getWsUri();
            $_uri = Zend_Uri::factory($_wsUri . 'auth.asmx?WSDL');

            $wsdl = $_uri->getUri();

        }catch(Exception $e){
            Mage::throwException($this->getHelper()->__('Invalid euro.message web service URL: %s', $_wsUri));
        }

        parent::__construct($wsdl, $options);
    }

    /**
     * Login to euro.message Web Service
     */
    public function _login(){

        $_response = $this->Login($this->getHelper()->getWsCredentials());
        if($_response->Code == '00')
            $this->setServiceTicket($_response->ServiceTicket);
        else
            Mage::throwException($this->getHelper()->__('Cannot login to euro.message web service: %s', $_response->Message));

        return $this;
    }

    /**
     * Logout from euro.message Web Service
     */
    public function _logout(){

        if($this->isLoggedIn()) {
            $this->Logout(array('ServiceTicket' => $this->getServiceTicket()));
            $this->setServiceTicket(null);
        }

        return $this;
    }

    /**
     * Check if logged in
     */
    public function isLoggedIn(){

        if($this->getServiceTicket())
            return true;

        return false;
    }

    /**
     * Call post web services
     */
    public function getPostService(){

        if($this->isLoggedIn())
            return $this->_getPostService();
        else
            Mage::throwException($this->getHelper()->__('Call login() first to use Post Web Service.'));

        return false;
    }

    protected function _getPostService(){

        $_service = Mage::getModel('euromsg/platform_post')
            ->setServiceTicket($this->getServiceTicket());

        return $_service;
    }

    /**
     * Call sms web services
     */
    public function getSmsService(){

        if($this->isLoggedIn())
            return $this->_getSmsService();
        else
            Mage::throwException($this->getHelper()->__('Call login() first to use Sms Web Service.'));

        return false;
    }

    protected function _getSmsService(){

        $_service = Mage::getModel('euromsg/platform_postSms')
            ->setServiceTicket($this->getServiceTicket());

        return $_service;
    }

    /**
     * Call sms web services
     */
    public function getReportService(){

        if($this->isLoggedIn())
            return $this->_getReportService();
        else
            Mage::throwException($this->getHelper()->__('Call login() first to use Report Web Service.'));

        return false;
    }

    protected function _getReportService(){

        $_service = Mage::getModel('euromsg/platform_report')
            ->setServiceTicket($this->getServiceTicket());

        return $_service;
    }

    /**
     * Call sms web services
     */
    public function getMemberService(){

        if($this->isLoggedIn())
            return $this->_getMemberService();
        else
            Mage::throwException($this->getHelper()->__('Call login() first to use Member Web Service.'));

        return false;
    }

    protected function _getMemberService(){

        $_service = Mage::getModel('euromsg/platform_member')
            ->setServiceTicket($this->getServiceTicket());

        return $_service;
    }
}
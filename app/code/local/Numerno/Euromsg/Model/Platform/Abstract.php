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
 * Platform Abstract Class
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Platform_Abstract extends Zend_Soap_Client_DotNet
{

    protected $_serviceTicket = null;
    protected $_charset = 'UTF-8';

    /**
     * Constructor
     *
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($wsdl = null, $options = null)
    {
        if(!is_array($options))
            $options = array();

        $options['encoding'] = $this->_charset;
        $options['soap_version'] = SOAP_1_2;

        parent::__construct($wsdl, $options);
    }

    public function getHelper()
    {

        return Mage::helper('euromsg');
    }

    public function setServiceTicket($token)
    {

        $this->_serviceTicket = $token;

        return $this;
    }

    public function getServiceTicket()
    {

        return $this->_serviceTicket;
    }

    public function setCharset($charset)
    {

        return $this->_charset = $charset;
    }

    public function getCharset()
    {

        return $this->_charset;
    }

    public function getResponseXml()
    {
        $response = str_ireplace('soap:', '', $this->getLastResponse()); //make readable for SoapXML
        $responseXml = simplexml_load_string($response);

        if(isset($responseXml->Body))
            return $responseXml->Body;

        return $responseXml;
    }
}
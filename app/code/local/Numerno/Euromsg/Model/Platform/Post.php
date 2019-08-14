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
 * Post Service
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Platform_Post extends Numerno_Euromsg_Model_Platform_Abstract
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
            $_uri = Zend_Uri::factory($_wsUri . 'post.asmx?WSDL');

            $wsdl = $_uri->getUri();

        }catch(Exception $e){
            Mage::throwException($this->getHelper()->__('Invalid euro.message web service URL: %s', $_wsUri));
        }

        parent::__construct($wsdl, $options);
    }

    public function getPostHelper(){

        return Mage::helper('euromsg/post');
    }

    public function post($recipient, $subject, $body, $type = 'general'){

        $_params = array(
            'ServiceTicket' => $this->getServiceTicket(),
            'Subject' => $subject,
            'HtmlBody' => $body,
            'Charset' => $this->_charset,
            'ToName' => $recipient['name'],
            'ToEmailAddress' => $recipient['email'],
            'PostType' => $type
        );
        $postParams = $this->getPostHelper()->getPostParams();

        $params = array_merge($_params, $postParams);

        return $this->PostHtmlWithType($params);

    }

    public function check($postId){

        $params = array(
            'ServiceTicket' => $this->getServiceTicket(),
            'PostID' => $postId
        );

        return $this->GetPostResult($params);

    }

}
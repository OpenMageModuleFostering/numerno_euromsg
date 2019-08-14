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
 * Platform
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Platform extends Zend_Soap_Client_DotNet
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
        $this->_init();
    }

    /**
     * Initializer
     *
     * @param string $service
     * @param array $options
     */
    protected function _init($service = 'auth', $options = null)
    {
        try {
            $webServiceUrl = $this->_getHelper()->getConfigData('general/platform');
            $_uri = Zend_Uri::factory($webServiceUrl . $service . '.asmx?WSDL');
            $wsdl = $_uri->getUri();

            if(!is_array($options))
                $options = array();

            $options['encoding'] = $this->_charset;
            $options['soap_version'] = SOAP_1_2;

            parent::__construct($wsdl, $options);

        }catch(Exception $e)
        {
            Mage::throwException($this->_getHelper()->__('Invalid euro.message web service: %s', $service));
        }

        return $this;
    }

    protected function _getHelper()
    {
        return Mage::helper('euromsg');
    }

    protected function _setServiceTicket($token)
    {
        $this->_serviceTicket = $token;

        return $this;
    }

    protected function _getServiceTicket()
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

    protected function _getResponseXml()
    {
        $response = str_ireplace('soap:', '', $this->getLastResponse()); //make readable for SoapXML
        $responseXml = simplexml_load_string($response);

        if (isset($responseXml->Body)) {

            return $responseXml->Body;
        }

        return $responseXml;
    }

    /**
     * Check if logged in
     */
    private function _isLoggedIn()
    {
        if($this->_getServiceTicket())
            return true;

        return false;
    }

    public function _getMemberDemographics($customerId, &$data)
    {
        $helper     = $this->_getHelper();
        $attributes = unserialize($helper->getConfigData('general/attributes', 'customer'));
        $attributeCodes = array();
        foreach ($attributes as $attribute) {
            $attributeCodes[] = $helper->removeCustomerPrefix($attribute['attribute']);
        }
        $customer   = Mage::getResourceModel('customer/customer_collection')
            ->addAttributeToSelect($attributeCodes)
            ->addAttributeToFilter('entity_id', array('eq' => $customerId));

        if($customer->count()) {
            $prefix     = $helper->getCustomerAttributePrefix();
            $preset     = array_keys($helper->getPresetCustomerAttributes());

            $customer = $customer->getFirstItem();
            foreach ($attributes as $key => $attribute) {

                $attributeCode = preg_replace('/^' . $prefix . '/', '', $attribute['attribute']);
                if($attributeCode == 'entity_id' || !in_array($key, $preset) ) {

                    $attribute = $customer->getResource()->getAttribute($helper->removeCustomerPrefix($attributeCode));
                    if ($attribute->usesSource()) {
                        $value = $attribute->getFrontend()->getValue($customer);
                    } else {
                        $value = $customer->getData($helper->removeCustomerPrefix($attributeCode));
                    }

                    if(Zend_Date::isDate($value, Zend_Date::ISO_8601)) {
                        $value = date("Ymd", strtotime($value));
                    }

                    $data[] = array(
                        'Key'   => $attributes[$key]['col_name'],
                        'Value' => $value
                    );
                }
            }
        }
    }

    /**
     * Login to euro.message Web Service
     */
    private function _login()
    {
        $helper = $this->_getHelper();
        $params = array(
            'Username' => $helper->getConfigData('general/ws_user'),
            'Password' => $helper->getConfigData('general/ws_pass')
        );
        $result  = $this->Login($params);

        if($result->Code == '00')
            $this->_setServiceTicket($result->ServiceTicket);
        else
            Mage::throwException($this->_getHelper()->__('Cannot login to euro.message web service: %s', $result->Message));

        return $this;
    }
    /**
     * Call web services
     */
    public function connect($_serviceName)
    {
        if(!$this->_isLoggedIn())
            $this->_login();

        if($this->_isLoggedIn())
            return $this->_init($_serviceName);
        else
            Mage::throwException($this->_getHelper()->__('Can not connect to %s Web Service. Login failed.', $_serviceName));

        return $this;
    }

    /**
     * Logout from euro.message Web Service
     */
    public function disconnect()
    {
        if($this->_isLoggedIn()) {
            $this->_init('auth');
            $this->Logout(array('ServiceTicket' => $this->_getServiceTicket()));
            $this->_setServiceTicket(null);
        }

        return $this;
    }

    public function sendEmail($recipient, $subject, $body, $type = 'general')
    {
        $this->connect('post');

        $helper = $this->_getHelper();
        $params  = array(
            'ServiceTicket'  => $this->_getServiceTicket(),
            'Subject'        => $subject,
            'HtmlBody'       => $body,
            'Charset'        => $this->_charset,
            'ToName'         => $recipient['name'],
            'ToEmailAddress' => $recipient['email'],
            'PostType'       => $type,
            'FromName'       => $helper->getConfigData('general/from_name', 'trx'),
            'FromAddress'    => $helper->getConfigData('general/from_addr', 'trx'),
            'ReplyAddress'   => $helper->getConfigData('general/reply_addr', 'trx')
        );
        $result  = $this->PostHtmlWithType($params);

        $this->disconnect();

        return $result;
    }

    public function trackEmail($postId)
    {
        $this->connect('post');

        $params = array(
            'ServiceTicket' => $this->_getServiceTicket(),
            'PostID' => $postId
        );
        $result  = $this->GetPostResult($params);

        $this->disconnect();

        return $result;
    }

    public function trackEmails($postIds)
    {
        $this->connect('post');

        $params = array(
            'serviceTicket' => $this->_getServiceTicket(),
            'postIds' => $postIds
        );
        $this->GetPostBulkResultByPostId($params);
        $response = $this->_getResponseXml();

        $this->disconnect();

        if(isset($response->GetPostBulkResultByPostIdResponse)) {
            return $response->GetPostBulkResultByPostIdResponse->bulkResult;
        }

        return false;
    }

    public function sendSMS($message, $recipient, $begin)
    {
        $this->connect('postsms');

        $helper = $this->_getHelper();
        $method = $helper->getConfigData('general/method', 'sms');

        switch($method){
            case 'single-shot':

                $params = array(
                    'ServiceTicket'     => $this->_getServiceTicket(),
                    'Originator'        => $helper->getConfigData('general/originator', 'sms'),
                    'NumberMessagePair' => array(
                        'Key'   => $message,
                        'Value' => $recipient
                    ),
                    'BeginTime'         => $begin
                );

                $this->SingleShotSms($params);
                $response = $this->_getResponseXml();
                $this->disconnect();

                if(isset($response->SingleShotSmsResponse)) {
                    $result = $response->SingleShotSmsResponse->SendPersonalSmsResult;
                    $result->Type = 'singleshot';

                    return $result;
                }
                break;

            case 'standard':

                $params = array(
                    'ServiceTicket'     => $this->_getServiceTicket(),
                    'Originator'        => $helper->getConfigData('general/originator', 'sms'),
                    'NumberMessagePair' => array(
                        'Key'   => $message,
                        'Value' => $recipient
                    ),
                    'BeginTime'         => $begin
                );

                $this->SendPersonalSms($params);
                $response = $this->_getResponseXml();
                $this->disconnect();

                if(isset($response->SendPersonalSmsResponse)) {
                    $result = $response->SendPersonalSmsResponse->SendPersonalSmsResult;
                    $result->Type = 'standard';

                    return $result;
                }
                break;

            default:
                Mage::throwException($this->_getHelper()->__('Platform::sendSMS: Unrecognized method.'));
        }

        return false;
    }

    public function trackSMS($packetId, $type = 'standard')
    {
        $this->connect('postsms');

        $params = array(
            'ServiceTicket'     => $this->_getServiceTicket(),
            'PacketId'          => $packetId
        );

        if ($type == 'standard') {

            $this->ReportSmsWithPacketId($params);
            $response = $this->_getResponseXml();

            $this->disconnect();

            if(isset($response->ReportSmsWithPacketIdResponse)) {
                return $response
                    ->ReportSmsWithPacketIdResponse
                    ->ReportSmsWithPacketIdResult
                    ->DeliveryResults
                    ->EmSmsDeliveryResult;
            }
        }

        if ($type == 'singleshot') {

            $this->ReportSingleShotSms($params);
            $response = $this->_getResponseXml();

            $this->disconnect();

            if(isset($response->ReportSingleShotSmsResponse)) {
                return $response
                    ->ReportSingleShotSmsResponse
                    ->ReportSingleShotSmsResult
                    ->DeliveryResults
                    ->EmSmsDeliveryResult;
            }
        }

        return false;
    }

    public function getUnsubscribers($begin, $end)
    {
        $this->connect('report');

        $params = array(
            'ServiceTicket' => $this->_getServiceTicket(),
            'BeginDate'     => $begin,
            'EndDate'       => $end
        );

        $this->GetUnsubscribeReportBetweenTwoDates($params);
        $response = $this->_getResponseXml();

        $this->disconnect();

        if(isset($response->GetUnsubscribeReportBetweenTwoDatesResponse ))
            return $response->GetUnsubscribeReportBetweenTwoDatesResponse->Unsubscribers;

        return false;

    }

    public function getOptouts($begin, $end)
    {
        $this->connect('report');

        $params = array(
            'ServiceTicket' => $this->_getServiceTicket(),
            'BeginDate'     => $begin,
            'EndDate'       => $end
        );

        $this->GetSmsOptoutReportBetweenTwoDates($params);
        $response = $this->_getResponseXml();

        $this->disconnect();

        if(isset($response->GetSmsOptoutReportBetweenTwoDatesResponse ))
            return $response->GetSmsOptoutReportBetweenTwoDatesResponse->Unsubscribers;

        return false;

    }

    public function updateMember($email)
    {
        $subscriber = Mage::getModel('newsletter/subscriber')
            ->loadByEmail($email);

        if($subscriber->getId()){

            $this->connect('member');

            $data = array(
                array(
                    'Key'   => Numerno_Euromsg_Model_Export_Entity_Member::COL_EMAIL_PERMIT,
                    'Value' => $subscriber->isSubscribed() ? 'Y' : 'N'
                )
            );

            if($subscriber->getCustomerId())
                $this->_getMemberDemographics($subscriber->getCustomerId(), $data);

            $params = array(
                'ServiceTicket'  => $this->_getServiceTicket(),
                'Key'            => Numerno_Euromsg_Model_Export_Entity_Member::COL_EMAIL,
                'Value'          => $subscriber->getEmail(),
                'DemograficData' => $data,
                'ForceInsert'    => true
            );

            $this->UpdateMemberDemography($params);
            $response = $this->_getResponseXml();

            $this->disconnect();

            if(isset($response->UpdateMemberDemographyResponse))
                return true;
        }else
            Mage::throwException($this->_getHelper()->__('Platform::updateMember: Email not found.'));

        return false;
    }

    public function getMember($email)
    {
        $this->connect('member');

        $params = array(
            'ServiceTicket' => $this->_getServiceTicket(),
            'Key'           => Numerno_Euromsg_Model_Export_Entity_Member::COL_EMAIL,
            'Value'         => $email,
        );

        $this->QueryMemberDemography($params);
        $response = $this->_getResponseXml();

        $this->disconnect();

        if(isset($response->QueryMemberDemographyResponse))
            return $response->QueryMemberDemographyResponse;

        return false;
    }
}
<?php
/**
 * ExtendedClient.php
 *
 * @package    Alshaya
 * @module     Cybersource
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\Cybersource\Controller\Sop;

use Magento\Framework;
use Magento\Framework\Controller\ResultFactory;
use Cybersource_CybsClient;
use Cybersource_CybsSoapClient;

/**
 * Class ExtendedClient
 * @description  ExtendedClient uis a SOAP Client class 
 *               
 */
 
 class ExtendedClient extends \SoapClient{

    protected $_properties;
    function __construct($wsdl, $options = null) {
        $this->_properties = $options;
        parent::__construct($wsdl, $options);
    }

    // This section inserts the UsernameToken information in the outgoing SOAP message.
    function __doRequest($request, $location, $action, $version, $one_way = NULL) {
     
     $user = $this->_properties['merchant_id'];
     $password = $this->_properties['transaction_key'];
         
     $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$user</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$password</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";

     $requestDOM = new \DOMDocument('1.0');
     $soapHeaderDOM = new \DOMDocument('1.0');
     try 
     {
        $requestDOM->loadXML($request);
        $soapHeaderDOM->loadXML($soapHeader);
        $node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
        $requestDOM->firstChild->insertBefore($node, $requestDOM->firstChild->firstChild);
        $request = $requestDOM->saveXML();
        //printf( "Modified Request:\n*$request*\n" );
     } catch (DOMException $e){
         //die( 'Error adding UsernameToken: ' . $e->code);
		 return $e;
     }

     return parent::__doRequest($request, $location, $action, $version, null);
   }
}

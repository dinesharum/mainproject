<?php
/**
 * Knetresponse.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Knetpayment\Controller\Standard;

use Magento\Framework\App\RequestInterface;
use Knet_com_aciworldwide_commerce_gateway_plugins_e24PaymentPipe;

/**
 * Class Knetresponse
 * @description  Knetresponse is used for getting the knet response and processing it
 *
 */
class Knetresponse extends \Magento\Framework\App\Action\Action {

     /**
     * Logging instance
     * @var \Alshaya\Knetpayment\Logger\Logger
     */
    protected $_logger;
    protected $_isError;

    /**
     * @param \Magento\Framework\App\Action\Context $context
         * @param \YourNamespace\YourModule\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
                \Alshaya\Knetpayment\Logger\Logger $logger
    ){
                $this->_logger = $logger;
        parent::__construct($context);
    }


         /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /*** Checkout Success & Failure URL's ****/
        $successRedirectUrl=$this->_url->getUrl('checkout/confirmation/index');
        $failureRedirectUrl=$this->_url->getUrl('checkout/confirmation/failure');

        $dataArray = array();
        $helperObj = $this->_objectManager->get('Alshaya\Knetpayment\Helper\Data');
        $modelObj = $this->_objectManager->create('Alshaya\Knetpayment\Model\Item');

        //Loggin Parameters from Knet response 
        $logParams = array();
        $logParams['logger'] = $helperObj->getConfig($helperObj::LOGGER);
        $this->_logger->debug('Knet response starts ...',$logParams);

        $paymentID = '';
        $result = '';
        $auth = '';
        $ref =  '';
        $tranid = '';
        $trackid = '';
        $postdate = '';
        
        if(count($_POST) > 0 )
        {
            $response['response'] = $_POST;
            $this->_logger->debug(print_r($response,true),$logParams);

            $paymentID = isset($_POST['paymentid']) ? $_POST['paymentid'] : null;
            $result = isset($_POST['result']) ? $_POST['result'] : null;
            $tranid = isset($_POST['tranid']) ? $_POST['tranid'] : null; 
            $auth = isset($_POST['auth']) ? $_POST['auth'] : null;
            $ref =  isset($_POST['ref']) ? $_POST['ref'] : null;
            $trackid = isset($_POST['trackid']) ? $_POST['trackid'] : null; 
            $postdate = isset($_POST['postdate']) ? $_POST['postdate'] : null; 

        }
        else
        {
            $this->_logger->debug('knet response is empty. payment order is failed.',$logParams);
            $displayMsg = $helperObj->getErrorMsg($result);
            $this->messageManager->addError(__($displayMsg));
            return $this->resultRedirectFactory->create()->setPath('checkout/confirmation/failure');
        }
    
        
        
        //Start updating order based on the response
        $smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
        if($trackid)
        {
        
             if ($paymentID && $result == 'CAPTURED') {            

            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($trackid);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setStatus('accepted');
            $order->save();
            $orderPayment = $order->getPayment();
            $orderPayment->setAdditionalInformation($response);  
            $orderPayment->save();
            $smsObj->createSms($order);  

            // creating the transaction record for this order
            $paymentData = array();
            $paymentData['id'] = $tranid;
            $paymentData['parentTxnId'] = NULL;
            $paymentData['is_closed'] = 0;
            $paymentData['txnType'] = "CAPTURE";
            $paymentData['paymentid'] = $paymentID;
            $paymentData['result'] = $result;
            $paymentData['tranid'] = $tranid;
            $paymentData['auth'] = $auth;
            $paymentData['ref'] = $ref;
            $paymentData['trackid'] = $trackid;
            $paymentData['postdate'] = $postdate;
            if($tranid){
                $helperObj->createTransaction($order,$paymentData);
            }

            $order->addStatusHistoryComment(__('changed status to accepted.',false))->setIsCustomerNotified(true)->save();
            echo "REDIRECT=$successRedirectUrl";   die();
                }else{
                
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($trackid);
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setStatus('payment_gateway_declined');
            $order->save();

            $orderPayment = $order->getPayment();
            $orderPayment->setAdditionalInformation($response);
            $orderPayment->save();
            $smsObj->createSms($order);

            // creating the transaction record for this order
            $paymentData = array();
            $paymentData['id'] = $tranid;
            $paymentData['parentTxnId'] = NULL;
            $paymentData['is_closed'] = 1;
            $paymentData['txnType'] = "ORDER";
            $paymentData['paymentid'] = $paymentID;
            $paymentData['result'] = $result;
            $paymentData['tranid'] = $tranid;
            $paymentData['auth'] = $auth;
            $paymentData['ref'] = $ref;
            $paymentData['trackid'] = $trackid;
            $paymentData['postdate'] = $postdate;
            if($tranid){
                $helperObj->createTransaction($order,$paymentData);
            }

            $displayMsg = $helperObj->getErrorMsg($result);
            $order->addStatusHistoryComment(__('changed status to accepted.',false))->setIsCustomerNotified(true)->save();
            echo "REDIRECT=$failureRedirectUrl"."?errMsg=$displayMsg";   die();
            }

     }else{
            
                $this->_logger->debug('trackid is empty from knet response. Order is failed.',$logParams);
                $displayMsg = $helperObj->getErrorMsg($result);
                $this->messageManager->addError(__($displayMsg));
                return $this->resultRedirectFactory->create()->setPath('checkout/confirmation/failure');

        } 

    }

}

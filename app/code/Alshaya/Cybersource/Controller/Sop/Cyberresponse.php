<?php
/**
 * Cyberresponse.php
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
 * Class Cyberresponse
 * @description  Cyberresponse used for handling the Cybersource response
 *
 */
class Cyberresponse extends \Magento\Framework\App\Action\Action
{
	 
    protected $_logger;
    protected $_logHandler;

    /**
     * @param \Magento\Framework\App\Action\Context $context
	 * 
	 */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
	){
		parent::__construct($context); 
    }
    
    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {		
    	
		$response = $this->getRequest()->getParams(); 
		
		if(is_array($response))
		{	
			$logObj = $this->_objectManager->get('Psr\Log\LoggerInterface');
			$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
			$helperObj = $this->_objectManager->create('Alshaya\Cybersource\Helper\Data');
			$logObj->critical(print_r($response,true));
			
			if(isset($response['req_reference_number']) && isset($response['decision']))
			{
				$orderId = $response['req_reference_number'];
				$order_logger = $helperObj->orderLogger($orderId);
				$order_logger->info(print_r($response,true));
				$order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
				$orderPayment = $order->getPayment();
				if(isset($response['transaction_id'])){
						$orderPayment->setCcTransId($response['transaction_id']);
					}
				if(isset($response['card_type'])){
						$orderPayment->setCcType($response['card_type']);
				}
				if(isset($response['req_merchant_defined_data1'])){
						 $baseStoreURL = $response['req_merchant_defined_data1'];
				}
				$resp_ai['request'] = $orderPayment->getAdditionalInformation();
				$resp_ai['auth_response'] =  $response;
				$orderPayment->setAdditionalInformation($resp_ai);
				$orderPayment->save();
				$order_logger->info(print_r($resp_ai,true));
				$cyberMsg = $helperObj->getCyberSourceMessage($response['reason_code']);
				$order_logger->info('Cybersource Message:'.$cyberMsg);


				// creating the transaction record for this order
	            $paymentData = array();
	            $paymentData = $response;
	            $transactionModelId = isset($response['transaction_id']) ? $response['transaction_id'] : null;
	            				
				if($response['decision'] == 'ACCEPT')
				{	
					$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
					$order->setStatus('accepted');
					$order->save();
					 $order->addStatusHistoryComment(
					__('order Status changed to Accepted.',false)
					)->setIsCustomerNotified(true)
					->save();
					$smsObj->createSms($order);

					$paymentData['id'] = $transactionModelId;
		            $paymentData['parentTxnId'] = NULL;
		            $paymentData['is_closed'] = 0;
		            $paymentData['txnType'] = "AUTHORIZATION";
		           
		            if($paymentData['id']){
		                $helperObj->createTransaction($order,$paymentData);
		            }

					$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
					$resultRedirect->setUrl($baseStoreURL . 'checkout/confirmation/index/');
					return $resultRedirect;
				}
				else if($response['decision'] == 'REVIEW')
				{	
					$order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
					$order->setStatus('reffered');
					$order->save();
					$order->addStatusHistoryComment(
					__('changed status to reffered.',false)
					)->setIsCustomerNotified(true)
					->save();
					$smsObj->createSms($order);

					$paymentData['id'] = $transactionModelId;
		            $paymentData['parentTxnId'] = NULL;
		            $paymentData['is_closed'] = 0;
		            $paymentData['txnType'] = "ORDER";
		           
		            if($paymentData['id']){
		                $helperObj->createTransaction($order,$paymentData);
		            }

					$this->messageManager->addNotice(__($cyberMsg));
					$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
					$resultRedirect->setUrl($baseStoreURL . 'checkout/confirmation/index/');
					return $resultRedirect;
					
				}
				else if($response['decision'] == 'DECLINE' && ($response['reason_code'] == 481 || $response['reason_code'] == 480) )
				{	
					$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
					$order->setStatus('auth_reversal_dm');
					$order->save();
					$order->addStatusHistoryComment(
					__('changed status to auth_reversal_dm.',false)
					)->setIsCustomerNotified(true)
					->save();
					$smsObj->createSms($order);

					$paymentData['id'] = $transactionModelId;
		            $paymentData['parentTxnId'] = NULL;
		            $paymentData['is_closed'] = 0;
		            $paymentData['txnType'] = "ORDER";
		           
		            if($paymentData['id']){
		                $helperObj->createTransaction($order,$paymentData);
		            }

					$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
					$resultRedirect->setUrl($baseStoreURL . "checkout/confirmation/failure?errMsg=$cyberMsg");
					return $resultRedirect;					
				}
				else
				{
					$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
					$order->setStatus('payment_gateway_declined');
					$order->save();
					$order->addStatusHistoryComment(
					__('changed status to Payment Gateway Declined.',false)
					)->setIsCustomerNotified(true)
					->save();
					$smsObj->createSms($order);

					$paymentData['id'] = $transactionModelId;
		            $paymentData['parentTxnId'] = NULL;
		            $paymentData['is_closed'] = 1;
		            $paymentData['txnType'] = "ORDER";
		           
		            if($paymentData['id']){
		                $helperObj->createTransaction($order,$paymentData);
		            }

					$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
					$resultRedirect->setUrl($baseStoreURL . "checkout/confirmation/failure?errMsg=$cyberMsg");
					return $resultRedirect;
				}				  
			}
			return $this->resultRedirectFactory->create()->setPath('checkout/confirmation/failure');
		}
		else
		{
			$logObj->critical('Cybersource Error: response is not an array.');
			return $this->resultRedirectFactory->create()->setPath('checkout/confirmation/failure');
		}              
    }
}

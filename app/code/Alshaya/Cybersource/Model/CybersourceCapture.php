<?php
/**
 * CybersourceCapture.php
 *
 * @package    Alshaya
 * @module     Cybersource
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Cybersource\Model;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */
use Magento\Framework\ObjectManagerInterface;



/**
 * Class CybersourceCapture
 * @description  Performing Cybersource Payment Settlement
 */
class CybersourceCapture extends \Magento\Framework\Model\AbstractModel
{

	// Defining the required variable for the cron class
	CONST CLIENTLIBRARY = 'PHP';
    protected $_datetime;
    protected $_logger;
    protected $_objectManager;
	protected $transportBuilder;
    protected $inlineTranslation;
	protected $timezone;
	protected $_helper;
  
	/**
     * @var WriteInterface
     */
    protected $directory;
	
    /**
     * @var int
     */
    protected $_lineLength = 0;

    /**
     * @var string
     */
    protected $_delimiter = ',';

    /**
     * @var string
     */
    protected $_enclosure = '"';

  
	
	/** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;
	

     /**
     * @var AreaList
     */
    protected $_areaList;

    /**
     * @var Request\Http
     */
    protected $_request;
    
	/**
     * @description initializing the construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
		\Alshaya\Cybersource\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
       	\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
		\Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\App\Request\Http  $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager
	) 
	{
        $this->_logger = $logger;
		$this->_helper = $helper;
        $this->_objectManager = $objectManager;
        $this->transportBuilder = $transportBuilder;
        $this->appState = $appState;
        $this->inlineTranslation = $inlineTranslation;
        $this->_datetime = $dateTime;
		$this->timezone = $timezone;
        $this->_areaList = $areaList;
        $this->_request = $request;
		$this->_storeManager = $storeManager;
    }
		
	public function capturePayment($orderId)
	{
		try {				
				if(!empty(trim($orderId)))
				{	
					$orderId = preg_replace('/\s+/','',$orderId);
					$order_logger = $this->_helper->orderLogger($orderId);					
					$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
					$is_errorFlag = false;
					$order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
								
					$orderPayment = $order->getPayment();
					
					$auth_resp_arr = $orderPayment->getAdditionalInformation();
					$order_logger->info('Getting additional information field data:');
					$order_logger->info(print_r($auth_resp_arr,true));
										
					$properties = $this->_helper->getProperties();
					$soapClient = new \Alshaya\Cybersource\Controller\Sop\ExtendedClient($properties['wsdl'], $properties);	
					

					
					$request['merchantID'] = $properties['merchant_id']; 
					if(isset($auth_resp_arr['auth_response']['req_reference_number']))
					{
						$request['merchantReferenceCode'] = $auth_resp_arr['auth_response']['req_reference_number'];
					}
					else
					{
						$request['merchantReferenceCode'] = '';
						$order_logger->info("merchantReferenceCode is set to empty from the req_reference_number of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
							
					// To help us troubleshoot any problems that you may encounter,
					// please include the following information about your PHP application.
					$request['clientLibrary'] = self::CLIENTLIBRARY;
					$request['clientLibraryVersion'] = phpversion();
					$request['clientEnvironment'] = php_uname();

					$ccCaptureService['run'] = "true";
					if(isset($auth_resp_arr['auth_response']['transaction_id']))
					{				
						$ccCaptureService['authRequestID'] = $auth_resp_arr['auth_response']['transaction_id'];
					}
					else
					{
						$ccCaptureService['authRequestID'] = '';
						$order_logger->info("authRequestID is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					$request['ccCaptureService']= $ccCaptureService;
					
					if(isset($auth_resp_arr['auth_response']['req_currency']))
					{
						$purchaseTotals['currency'] = $auth_resp_arr['auth_response']['req_currency'];				
					}
					else
					{
						$purchaseTotals['currency'] = '';
						$order_logger->info("currency is set empty from the req_currency of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					
					if(isset($auth_resp_arr['auth_response']['req_amount']))
					{
						$orderAmount = number_format($auth_resp_arr['auth_response']['req_amount'],3);
						$orderAmount = preg_replace('/,/','',$orderAmount);
						$purchaseTotals['grandTotalAmount'] = $orderAmount;			
					}
					else
					{
						$purchaseTotals['grandTotalAmount']  = '';
						$order_logger->info("grandTotalAmount is set empty from the req_amount of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					$request['purchaseTotals'] = $purchaseTotals;
										
					if($is_errorFlag)
					{	
						$order_logger->info('is_errorFlag is true '.$is_errorFlag);
						$order_logger->info('Capture Service - Inadequate data to build request.');
						return 'EXECUTION_FAILED';
					}
					
					$order_logger->info(print_r($request,true));				
					$captureResponse = $soapClient->runTransaction($request);
					$captureResponseArr = json_decode(json_encode($captureResponse), true);
					$order_logger->info(print_r($captureResponseArr,true));
										 
					$cyberMsg = $this->_helper->getCyberSourceMessage($captureResponseArr['reasonCode']);
					$order_logger->info('Cybersource Message:'.$cyberMsg);
					$order_logger->info('Order ID:'.$orderId);

					// creating the transaction record for this order
	            	$paymentData = array();
	            	$paymentData = $captureResponseArr;
	            	$transactionModelId = isset($captureResponseArr['requestID']) ? $captureResponseArr['requestID'] : null;

										
					$resp_ai['request'] = $auth_resp_arr['request']; 
					$resp_ai['auth_response'] =  $auth_resp_arr['auth_response'];
					
					// Force fully failing settlement in the below line by setting decision to DECLINE
					//$captureResponseArr['decision'] == 'DECLINE';
					
					if(is_array($captureResponseArr)){
						if($captureResponseArr['decision'] == 'ACCEPT'){
							
							$order_logger->info('INSIDE IF:'.$captureResponseArr['decision']);
							
							$resp_ai['capture_response']= $captureResponseArr;
							$orderPayment->setAdditionalInformation($resp_ai);
							$orderPayment->save();
							
							$order_logger->info('After order payment save with response array');
							
							$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
							$order->setStatus('payment_settled');
							$order->save();
							
							$order_logger->info('After order status save as payment settled');
							
							$order->addStatusHistoryComment(
							__('changed status to Payment Settled.',false)
							)->setIsCustomerNotified(true)
							->save();
							
							$order_logger->info('After order status history save for success.');
							
							$smsObj->createSms($order);
							
							$order_logger->info('After order sms process.');

							$paymentData['id'] = $transactionModelId;
				            $paymentData['parentTxnId'] = $auth_resp_arr['auth_response']['transaction_id'];
				            $paymentData['is_closed'] = 0;
				            $paymentData['txnType'] = "CAPTURE";
							
							$order_logger->info('Before transaction begins.');
				           
				            if($paymentData['id']){
								$order_logger->info('Inside transaction if condition.');
								    $this->_helper->createTransaction($order,$paymentData);
								$order_logger->info('After createTransaction function processed.');
				            }

							$order_logger->info('Cybersource Settlement decision for '.$orderId.':'.$captureResponseArr['decision']);
							return true;						
						}
						else if(($captureResponseArr['decision'] == 'REJECT') && ($captureResponseArr['reasonCode'] == 243))
						{
							
							$order_logger->info('Cybersource Error: Payment settlement is already successfully done for this order Id:'.$orderId);
							return false;
						}						
						else{
							$order_logger->info('INSIDE ELSE:'.$captureResponseArr['decision']);
							
							$resp_ai['capture_response']= $captureResponseArr;
							$orderPayment->setAdditionalInformation($resp_ai);
							$orderPayment->save();
							//$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
							$order->cancel();
							$order->setStatus('settlement_failed');
							$order->save();
							
							$order_logger->info('After order status save as settlement failed.');
							
							$order->addStatusHistoryComment(
							__('changed status to Settled Failed.',false)
							)->setIsCustomerNotified(true)
							->save();
							
							$order_logger->info('After order status history save for failure.');
							
							$smsObj->createSms($order); 
							$order_logger->info('Cybersource Settlement decision for '.$orderId.':'.$captureResponseArr['decision']);
							return false;
						}
					}
				}else{
					$this->_logger->critical('orderId is empty.');
					return false;
				}
			}catch (\SoapFault $fault) { 
				
				$this->_logger->critical( $fault->faultcode);
				$this->_logger->critical($fault->faultstring);
				return 'EXECUTION_FAILED';
			}
			catch(Exception $e)
			{
				$this->_logger->critical($e->getMessage());
				return 'EXECUTION_FAILED';
			}
	
	}    	
}

<?php
/**
 * CybersourceAuthReversal.php
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
 * Class CybersourceAuthReversal
 * @description  Performing Cybersource Authorization Reversal.
 */
class CybersourceAuthReversal extends \Magento\Framework\Model\AbstractModel
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
		
	public function authReversal($orderId,$status=null)
	{
		try {				
				if(!empty(trim($orderId)))
				{	
					$orderId = preg_replace('/\s+/','',$orderId);
					$order_logger = $this->_helper->orderLogger($orderId);
					$is_errorFlag = false;
					$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
					$order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
					$orderPayment = $order->getPayment();
					$properties = $this->_helper->getProperties();
					/**** getting the auth response ****/
					$auth_resp_arr = $orderPayment->getAdditionalInformation();
					$order_logger->info('Getting additional information field data:');
					$order_logger->info(print_r($auth_resp_arr,true));
					
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

									 
					$ccAuthReversalService['run'] = "true";
					if(isset($auth_resp_arr['auth_response']['transaction_id']))
					{						
						$ccAuthReversalService['authRequestID'] = $auth_resp_arr['auth_response']['transaction_id'];
					}
					else
					{
						$ccAuthReversalService['authRequestID'] = '';
						$order_logger->info("authRequestID is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					
					if(isset($auth_resp_arr['auth_response']['request_token']))
					{						
						$ccAuthReversalService['authRequestToken'] = $auth_resp_arr['auth_response']['request_token'];
					}
					else
					{
						$ccAuthReversalService['authRequestToken'] = '';
						$order_logger->info("authRequestToken is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					
					$request['ccAuthReversalService']= $ccAuthReversalService;
					
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
						$this->_logger->critical('is_errorFlag is true '.$is_errorFlag);
						$this->_logger->critical('Auth Reversal - Inadequate data to build request.');
						return 'EXECUTION_FAILED';
					}
					
					$order_logger->info(print_r($request,true));
													
					$authreversalResponse = $soapClient->runTransaction($request);
					$authreversalResponseArr = json_decode(json_encode($authreversalResponse), true);
					
					$order_logger->info(print_r($authreversalResponseArr,true));
									
					$cyberMsg = $this->_helper->getCyberSourceMessage($authreversalResponseArr['reasonCode']);
					$order_logger->info('Cybersource Message:'.$cyberMsg);
					
					/**** rebuilding additional Information ****/
					$resp_ai['request'] = $auth_resp_arr['request'];
					$resp_ai['auth_response'] =  $auth_resp_arr['auth_response'];									
					if(is_array($authreversalResponseArr)){
						if($authreversalResponseArr['decision'] == 'ACCEPT'){
							$resp_ai['reversel_response']= $authreversalResponseArr;
							$orderPayment->setAdditionalInformation($resp_ai);
							$orderPayment->save();
							$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
							
							if($status == 'stock_shortage')
							{								 
								$order->setStatus('cancelled_stock_short'); 
								$order->addStatusHistoryComment(
								__('changed status to Cancelled stock shortage.',false)
								)->setIsCustomerNotified(true)
								->save();
							}
							else if($status == 'decision_manager_declined')
							{
								$order->setStatus('payment_gateway_declined_dm'); 
								$order->addStatusHistoryComment(
								__('changed status to Cancelled Decision Manager Declined',false)
								)->setIsCustomerNotified(true)
								->save();
							}
							else
							{								
								$order->setStatus('customer_cancellation_complete');
								$order->addStatusHistoryComment(
								__('changed status to Customer cancellation complete.',false)
								)->setIsCustomerNotified(true)
								->save();
							}
							$order->save(); 
							
							$smsObj->createSms($order);
							$order_logger->info('Cybersource Auth Reversal decision:'.$authreversalResponseArr['decision']);
							return true;
							
						}
						else if(($authreversalResponseArr['decision'] == 'REJECT') && ($authreversalResponseArr['reasonCode'] == 243))
						{
							$order->addStatusHistoryComment(
								__('Auth reversal is already successfully done for this order.',false)
								)->setIsCustomerNotified(true)
								->save();
							$order_logger->info('Cybersource Error: Auth reversal is already successfully done for this order Id:'.$orderId);
							return false;
						}
						else{
							$resp_ai['reversel_response']= $authreversalResponseArr;
							$orderPayment->setAdditionalInformation($resp_ai);
							$orderPayment->save();
							$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
							if($status == 'stock_shortage')
							{
								$order->setStatus('auth_reversal_failing_stock_shor');
								$order->addStatusHistoryComment(
								__('changed status to auth_reversal_failing_stock_shor.',false)
								)->setIsCustomerNotified(true)
								->save();
							}
							else if($status == 'decision_manager_declined')
							{
								$order->setStatus('failing_auth_reversal_dm'); 
								$order->addStatusHistoryComment(
								__('changed status to failing_auth_reversal_dm',false)
								)->setIsCustomerNotified(true)
								->save();
							}
							else
							{
								$order->setStatus('auth_reversal_failing');
								$order->addStatusHistoryComment(
								__('changed status to auth_reversal_failing.',false)
								)->setIsCustomerNotified(true)
								->save();
							}							
							$order->save();
							$smsObj->createSms($order);
							$order_logger->info('Cybersource Auth Reversal decision:'.$authreversalResponseArr['decision']);
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
	
	
	public function authReversalCustomerCancel($orderId,$status=null)
	{
		try {				
				if(!empty(trim($orderId)))
				{	
					$orderId = preg_replace('/\s+/','',$orderId);
					$order_logger = $this->_helper->orderLogger($orderId);
					$is_errorFlag = false;
					$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
					$order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
					$orderPayment = $order->getPayment();
					$properties = $this->_helper->getProperties();
					/**** getting the auth response ****/
					$auth_resp_arr = $orderPayment->getAdditionalInformation();
					
					$order_logger->info('Getting additional information field data:');
					$order_logger->info(print_r($auth_resp_arr,true));
									
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

									 
					$ccAuthReversalService['run'] = "true";
					if(isset($auth_resp_arr['auth_response']['transaction_id']))
					{						
						$ccAuthReversalService['authRequestID'] = $auth_resp_arr['auth_response']['transaction_id'];
					}
					else
					{
						$ccAuthReversalService['authRequestID'] = '';
						$order_logger->info("authRequestID is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					
					if(isset($auth_resp_arr['auth_response']['request_token']))
					{						
						$ccAuthReversalService['authRequestToken'] = $auth_resp_arr['auth_response']['request_token'];
					}
					else
					{
						$ccAuthReversalService['authRequestToken'] = '';
						$order_logger->info("authRequestToken is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
						$is_errorFlag = true;
					}
					
					$request['ccAuthReversalService']= $ccAuthReversalService;
					
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
						$order_logger->info('Auth Reversal - Inadequate data to build request.');
						return 'EXECUTION_FAILED';
					}
					
					$order_logger->info(print_r($request,true));
													
					$authreversalResponse = $soapClient->runTransaction($request);
					$authreversalResponseArr = json_decode(json_encode($authreversalResponse), true);
					
					$order_logger->info(print_r($authreversalResponseArr,true));
									
					$cyberMsg = $this->_helper->getCyberSourceMessage($authreversalResponseArr['reasonCode']);
					$order_logger->info('Cybersource Message:'.$cyberMsg);
					
					/**** rebuilding additional Information ****/
					$resp_ai['request'] = $auth_resp_arr['request'];
					$resp_ai['auth_response'] =  $auth_resp_arr['auth_response'];									
					if(is_array($authreversalResponseArr)){
						if($authreversalResponseArr['decision'] == 'ACCEPT'){
							$resp_ai['reversel_response']= $authreversalResponseArr;
							$orderPayment->setAdditionalInformation($resp_ai);
							$orderPayment->save();
							$smsObj->createSms($order);
							$order_logger->info('Cybersource Auth Reversal Decision:'.$authreversalResponseArr['decision']);
							return 'ACCEPT';
							
						}
						else if(($authreversalResponseArr['decision'] == 'REJECT') && ($authreversalResponseArr['reasonCode'] == 243))
						{
							$order->addStatusHistoryComment(
								__('Auth reversal is already successfully done for this order.',false)
								)->setIsCustomerNotified(true)
								->save();
							$order_logger->info('Cybersource Error: Auth reversal is already successfully done for this order Id:'.$orderId);
							return false;
						}
						else{
							$resp_ai['reversel_response']= $authreversalResponseArr;
							$orderPayment->setAdditionalInformation($resp_ai);
							$orderPayment->save();
							$smsObj->createSms($order);
							$order_logger->info('Cybersource Auth Reversal decision:'.$authreversalResponseArr['decision']);
							return 'FAILED';
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

<?php
/**
 * CybersourcePartialRefund.php
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
 * Class CybersourcePartialRefund
 * @description  Performing Complete and Partial Refund
 */
class CybersourcePartialRefund extends \Magento\Framework\Model\AbstractModel
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
	protected $_Resources;
    
	/**
     * @description initializing the construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Csv $csv
     * @param File $file
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
		\Magento\Framework\App\ResourceConnection $resources,
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
		$this->_Resources=$resources;
    }
		
	public function partialRefund($orderId,$refundArray,$creditMemoId=null,$status=null)
	{
		try {				
				if(!empty(trim($orderId)))
				{	
					$orderId = preg_replace('/\s+/','',$orderId);
					$creditMemoId = preg_replace('/\s+/','',$creditMemoId);
					$order_logger = $this->_helper->orderLogger($orderId);
					$order_logger->info("OrderId:".$orderId);
					$order_logger->info("creditMemoId:".$creditMemoId);
					$order_logger->info("Status:".$status);
					$order_logger->info(print_r($refundArray,true));
					
					$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
					$is_partial_refund = false;
					$is_errorFlag = false;
					$order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
					
					if(!empty($creditMemoId))
					{
						$creditMemoObj = $this->_objectManager->get('Magento\Sales\Model\Order\Creditmemo');
						$creditMemoObj->load($creditMemoId);
						$refundamount=$creditMemoObj->getGrandTotal();
						$order_logger->info("refundamount:".$refundamount);
						$refundamount = number_format($refundamount,3);
						$refundamount = preg_replace('/,/','',$refundamount);
						$purchaseTotals = [];
						
						$purchaseTotals['grandTotalAmount'] = $refundamount;											
						$properties = $this->_helper->getProperties();				
						$orderPayment = $order->getPayment();
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
						
						$ccCreditService['run'] = "true";
						if(isset($auth_resp_arr['capture_response']['requestID']))
						{	
						 $ccCreditService['captureRequestID'] = $auth_resp_arr['capture_response']['requestID'];
						}
						else
						{
							$ccCreditService['captureRequestID'] = '';
							$order_logger->info("captureRequestID is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
							$is_errorFlag = true;
						} 
						$request['ccCreditService']= $ccCreditService; 
						
					
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
					
						$request['purchaseTotals'] = $purchaseTotals;
						
																
						if($is_errorFlag)
						{						
							$order_logger->info('is_errorFlag is true '.$is_errorFlag);
							$order_logger->info('Refund Service - Inadequate data to build refund request.');
							return 'EXECUTION_FAILED';
						}
												
						$order_logger->info(print_r($request,true));				
						$refundResponse = $soapClient->runTransaction($request);
						$refundResponseArr = json_decode(json_encode($refundResponse), true);
						$order_logger->info(print_r($refundResponseArr,true));					
					
						$cyberMsg = $this->_helper->getCyberSourceMessage($refundResponseArr['reasonCode']);
						$order_logger->info('Cybersource Message:'.$cyberMsg);

						// creating the transaction record for this order
		            	$paymentData = array();
		            	$paymentData = $refundResponseArr;
		            	$transactionModelId = isset($refundResponseArr['requestID']) ? $refundResponseArr['requestID'] : null;

						
						$resp_ai['request'] = $auth_resp_arr['request'];
						$resp_ai['auth_response'] =  $auth_resp_arr['auth_response'];
						$resp_ai['capture_response'] = $auth_resp_arr['capture_response'];	
					
						
						$order_logger->info('Cybersource Message status:'.$status);
						if(is_array($refundResponseArr)){
							if($refundResponseArr['decision'] == 'ACCEPT'){
								$resp_ai['refund_response']= $refundResponseArr;
								$orderPayment->setAdditionalInformation($resp_ai);
								$orderPayment->save();
								
								if($status == 'partial_return'){
										
										$paymentData['is_closed'] = 0;
										$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
										$order->setStatus('partial_refund'); 
										$order->addStatusHistoryComment(
										__('changed status to partial refund.',false)
										)->setIsCustomerNotified(true)
										->save();

								}else if($status == 'failed_delivery'){
									
									$paymentData['is_closed'] = 0;
									$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
									$order->setStatus('cancelled_failed_delivery'); 
									$order->addStatusHistoryComment(
									__('changed status to cancelled_failed_delivery.',false)
									)->setIsCustomerNotified(true)
									->save();

								}else if($status == 'order_expired'){
									
									$paymentData['is_closed'] = 0;
									$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
									$order->setStatus('expiry_cancellation_complete'); 
									$order->addStatusHistoryComment(
									__('changed status to expiry_cancellation_complete.',false)
									)->setIsCustomerNotified(true)
									->save();
									
								}else{

										$paymentData['is_closed'] = 1;
										$order->setState(\Magento\Sales\Model\Order::STATE_CLOSED);
										$order->setStatus('complete_refund');
										$order->addStatusHistoryComment(
										__('changed status to complete_refund.',false)
										)->setIsCustomerNotified(true)
										->save();

								}
								
								$order->save();
								$connection= $this->_Resources->getConnection();
								$creditememostatus = "UPDATE sales_creditmemo SET state = '".\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED."' WHERE entity_id = '".$creditMemoObj->getId()."'";
								$connection->query($creditememostatus);
							
								$creditememostatus = "UPDATE sales_creditmemo_grid SET state = '".\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED."' WHERE entity_id = '".$creditMemoObj->getId()."'";
								$connection->query($creditememostatus);
								
								// you cannot set state data by magento becuase it will added up refunded amount each time when you set state to refunded									
									//$creditMemoObj->setData('state', \Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
									//$creditMemoObj->save();
								$smsObj->createSms($order);	

								$paymentData['id'] = $transactionModelId;
					            $paymentData['parentTxnId'] = $auth_resp_arr['auth_response']['transaction_id'];
					            $paymentData['txnType'] = "REFUND";
					           
					            if($paymentData['id']){
					                $this->_helper->createTransaction($order,$paymentData);
					            }
						
								$order_logger->info('Cybersource Settlement decision:'.$refundResponseArr['decision']);
								return true;						
							}else{
								
								
								$resp_ai['refund_response']= $refundResponseArr;
								$orderPayment->setAdditionalInformation($resp_ai);
								$orderPayment->save();
								$order_logger->info('Cybersource Message status else 1:'.$status);
								if($status == 'partial_return'){
									$order_logger->info('Cybersource Message status else 2:'.$status);
										$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
										$order->setStatus('partial_refund_failing'); 
										$order->addStatusHistoryComment(
										__('changed status to partial refund failing.',false)
										)->setIsCustomerNotified(true)
										->save();
								}else if($status == 'failed_delivery'){
									$order_logger->info('Cybersource Message status else 3:'.$status);
									$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
									$order->setStatus('fd_refund_failing'); 
									$order->addStatusHistoryComment(
									__('changed status to FD refund failing.',false)
									)->setIsCustomerNotified(true)
									->save();
								}else if($status == 'order_expired'){
									$order_logger->info('Cybersource Message status else 4:'.$status);
									$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
									$order->setStatus('expiry_refund_failing'); 
									$order->addStatusHistoryComment(
									__('changed status to expiry refund failing.',false)
									)->setIsCustomerNotified(true)
									->save();
									
								}else{
										$order_logger->info('Cybersource Message status else 5:'.$status);
										$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
										$order->setStatus('complete_refund_failing');
										$order->addStatusHistoryComment(
										__('changed status to complete refund failing.',false)
										)->setIsCustomerNotified(true)
										->save();
									}
								//$order->save();
								
								$smsObj->createSms($order);
								$order_logger->info('Cybersource refund decision:'.$refundResponseArr['decision']);
								return false;
							}
						}
					}else{
						$this->_logger->critical('creditmemo is empty.');
						return false;}						
					}else{
						$this->_logger->critical('orderId is empty.');
						return false;
					}
			}
			catch (\SoapFault $fault) { 
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
	

	public function failedRefund($orderId,$status=null)
	{
		try
		{
			if(!empty(trim($orderId)))
			{
				$orderId = preg_replace('/\s+/','',$orderId);
				$order_logger = $this->_helper->orderLogger($orderId);	
				$order_logger->info("OrderId:".$orderId);
				
				$is_partial_refund = false;
				$is_errorFlag = false;
				$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
				$fin24mObj = $this->_objectManager->get('Alshaya\OrderFulfilment\Model\Financial24M');
				$order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
				//$orderDetails = $order->getData();
				//$order_logger->info(print_r($orderDetails,true));
				$entityId = $order->getEntityId();
				$cmResource = $this->_objectManager->create('Magento\Sales\Model\Order\Creditmemo');
        		$cm_result = $cmResource->getCollection()
				->addAttributeToSelect(
		                    'entity_id'
		         ) 
				->addFieldToFilter(
		                    'order_id',
		                     $entityId
		        )->load();  
			
				$cmRefundArr  = array();
				$cmRefundAmount = 0;
				$cmRefundedAmount = 0;
				$orderTotalAmount = $order->getGrandTotal();
				$orderShippingAmount = $order->getShippingAmount();
				$orderTotalWithoutShipping = $orderTotalAmount - $orderShippingAmount;
				$cmPendingCount = 0;
								
					foreach($cm_result as $cmId)
					{
							$order_logger->info("creditMemoId:".$cmId['entity_id']);
							$creditMemoId = $cmId['entity_id'];
							$creditMemoObj1 = $this->_objectManager->get('Magento\Sales\Model\Order\Creditmemo');
							$creditMemoObj1->load($cmId['entity_id']);
										
								if($creditMemoObj1->getState() == 1)
								{
									$cmRefundAmount = $cmRefundAmount + $creditMemoObj1->getGrandTotal();
									$cmRefundArr[] = $cmId['entity_id'];
									$cmPendingCount++;
								}
								else if($creditMemoObj1->getState() == 2)
								{
									$cmRefundedAmount = $cmRefundedAmount + $creditMemoObj1->getGrandTotal();
									$order_logger->info("creditMemoId:".$cmId['entity_id']." is already refunded. So skipping this credit memo from processing.");
									continue;
								}
					}

					$allCMToatlAmount = $cmRefundedAmount + $cmRefundAmount;
					
					$order_logger->info("orderTotalAmount:".$orderTotalAmount);
					$order_logger->info("orderShippingAmount:".$orderShippingAmount);
					$order_logger->info("orderTotalWithoutShipping:".$orderTotalWithoutShipping);				
					$order_logger->info("cmRefundedAmount:".$cmRefundedAmount);
					$order_logger->info("cmRefundAmount:".$cmRefundAmount);
					$order_logger->info("allCMToatlAmount:".$allCMToatlAmount);
					
					if( ($allCMToatlAmount == $orderTotalAmount) || ($allCMToatlAmount == $orderTotalWithoutShipping) )
					{   
						$is_partial_refund = false; 
					}
					else{	$is_partial_refund = true;	}
					
					$order_logger->info("is_partial_refund:".$is_partial_refund);
					$order_logger->info("cmPendingCount:".$cmPendingCount);
						
					if(count($cmRefundArr) > 0 )
					{
							foreach($cmRefundArr as $cmId)
							{
								$order_logger->info("creditMemoId:".$cmId);
								$creditMemoId = $cmId;
								$creditMemoObj2 = $this->_objectManager->get('Magento\Sales\Model\Order\Creditmemo');
								$creditMemoObj2->load($cmId);
														
						
								if($creditMemoObj2->getState() == 1)
								{
									$cmRefund = $creditMemoObj2->getGrandTotal();
									$order_logger->info("cmRefund:".$cmRefund);
									$cmRefund = number_format($cmRefund,3);
									$cmRefund = preg_replace('/,/','',$cmRefund);
						
									//Building Cybersource Request to refund
									$properties = $this->_helper->getProperties();				
									$orderPayment = $order->getPayment();
									$paymentType = $orderPayment->getMethod();
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
							
									$ccCreditService['run'] = "true";
									if(isset($auth_resp_arr['capture_response']['requestID']))
									{	
									 $ccCreditService['captureRequestID'] = $auth_resp_arr['capture_response']['requestID'];
									}
									else
									{
										$ccCreditService['captureRequestID'] = '';
										$order_logger->info("captureRequestID is set empty from the transaction_id of additional information field of sale_order_payment table for orderId ".$orderId);
										$is_errorFlag = true;
									} 
									$request['ccCreditService']= $ccCreditService; 
													
								
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
								
									if(isset($refundTotal))
									{
										$purchaseTotals['grandTotalAmount'] = $cmRefund;								
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
										$order_logger->info('Refund Service - Inadequate data to build refund request.');
										return 'EXECUTION_FAILED';
									}
								
										
									$order_logger->info(print_r($request,true));					
									$refundResponse = $soapClient->runTransaction($request);
									$refundResponseArr = json_decode(json_encode($refundResponse), true);
									$order_logger->info(print_r($refundResponseArr,true));						
								
									$cyberMsg = $this->_helper->getCyberSourceMessage($refundResponseArr['reasonCode']);
									$order_logger->info('Cybersource Message:'.$cyberMsg);
									

												
									$resp_ai['request'] = $auth_resp_arr['request'];
									$resp_ai['auth_response'] =  $auth_resp_arr['auth_response'];
									$resp_ai['capture_response'] = $auth_resp_arr['capture_response'];	

									
									$refundResponseArr['decision'] = 'ACCEPT';
								
									if(is_array($refundResponseArr))
									{
										if($refundResponseArr['decision'] == 'ACCEPT')
										{
											$resp_ai['refund_response']= $refundResponseArr;
											$orderPayment->setAdditionalInformation($resp_ai);
											$orderPayment->save();
											
											$order_logger->info('Cybersource credit memo ID:'.$creditMemoObj2->getId());
											$order_logger->info('Cybersource credit memo ID:'.$creditMemoObj2->getId());
											$order_logger->info('Cybersource credit memo before:'.$creditMemoObj2->getState());
											
											
											$connection= $this->_Resources->getConnection();
											$creditememostatus = "UPDATE sales_creditmemo SET state = '".\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED."' WHERE entity_id = '".$creditMemoObj->getId()."'";
											$connection->query($creditememostatus);
										
											$creditememostatus = "UPDATE sales_creditmemo_grid SET state = '".\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED."' WHERE entity_id = '".$creditMemoObj->getId()."'";
											$connection->query($creditememostatus);
											
											// you cannot set state data by magento becuase it will added up refunded amount each time when you set state to refunded
								
											/* $creditMemoObj2->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
											$creditMemoObj2->save(); */
											$fin24mObj->create24M($orderId,$paymentType,$creditMemoId);
											
											$order_logger->info('Cybersource credit memo after:'.$creditMemoObj2->getState());
											$order_logger->info('Cybersource credit memo after:'.$creditMemoObj2->getId());
											$order_logger->info('Cybersource Refund decision:'.$refundResponseArr['decision']);
											
											$order_logger->info("cmPendingCount:".$cmPendingCount);
											
											if($cmPendingCount == 1)
											{
												
												if($is_partial_refund)
												{
													$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
													$order->setStatus('partial_refund'); 
													$order->addStatusHistoryComment(
														__('changed status to partial refund.',false)
														)->setIsCustomerNotified(true)
														->save();
													$order_logger->info('Cybersource order status: partial_refund');
												}
												else if($status == 'fd_refund_failing')
												{
													$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
													$order->setStatus('cancelled_failed_delivery'); 
													$order->addStatusHistoryComment(
													__('changed status to cancelled_failed_delivery.',false)
													)->setIsCustomerNotified(true)
													->save();
													$order_logger->info('Cybersource order status: cancelled_failed_delivery');
												}
												else if($status == 'expiry_refund_failing')
												{											
													$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
													$order->setStatus('expiry_cancellation_complete'); 
													$order->addStatusHistoryComment(
													__('changed status to expiry_cancellation_complete.',false)
													)->setIsCustomerNotified(true)
													->save();
													$order_logger->info('Cybersource order status: expiry_cancellation_complete');
												} 
												else 
												{									
													$order->setState(\Magento\Sales\Model\Order::STATE_CLOSED);
													$order->setStatus('complete_refund');
													$order->addStatusHistoryComment(
														__('changed status to complete_refund.',false)
														)->setIsCustomerNotified(true)
														->save();
													$order_logger->info('Cybersource order status: complete_refund');
												}
										
												$order->save();
												$smsObj->createSms($order);							
											}
											
											if($cmPendingCount > 1) {$cmPendingCount--;}
										
													 				
										}
										else
										{
											$resp_ai['refund_response']= $refundResponseArr;
											$orderPayment->setAdditionalInformation($resp_ai);
											$orderPayment->save();
										
											if($is_partial_refund){
													
													$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
													$order->setStatus('partial_refund_failing'); 
													$order->addStatusHistoryComment(
													__('changed status to partial refund failing.',false)
													)->setIsCustomerNotified(true)
													->save();
											}else if($status == 'fd_refund_failing'){
													
												$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
												$order->setStatus('fd_refund_failing'); 
												$order->addStatusHistoryComment(
												__('changed status to FD refund failing.',false)
												)->setIsCustomerNotified(true)
												->save();
											}else if($status == 'expiry_refund_failing'){
													
												$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
												$order->setStatus('expiry_refund_failing'); 
												$order->addStatusHistoryComment(
												__('changed status to expiry refund failing.',false)
												)->setIsCustomerNotified(true)
												->save();
												
											}else{
													$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
													$order->setStatus('complete_refund_failing');
													$order->addStatusHistoryComment(
													__('changed status to complete refund failing.',false)
													)->setIsCustomerNotified(true)
													->save();
												}
											$order->save();
											$smsObj->createSms($order);
											$order_logger->info('Cybersource Settlement decision:'.$refundResponseArr['decision']);
											
										}
									}
									
								}
								else{
									$order_logger->info("creditMemoId:".$cmId['entity_id']." is already refunded. So skipping this credit memo from processing.");
								}
							}
					}else{
						$order_logger->info("No pending credit memo to process for this order.");
					}			
			}
			else
			{
					$this->_logger->critical('orderId is empty.');
					return false;
			}		
		}
		catch(\SoapFault $fault){ 
				$this->_logger->critical($fault->faultcode);
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

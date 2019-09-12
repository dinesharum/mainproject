<?php
/**
 * Data.php
 *
 * @package    Alshaya
 * @module     Cybersource
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Cybersource\Helper;
/**
 * Class Data
 * @description  Helper Data class to get the system configuration for Knet payment gateway
 * 				 
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /* Defining the Constants */
    CONST SECTIONGROUPNAME  =  'payment/cybersource';
    CONST MERCHANTID      	=  'merchant_id';
    CONST LOGGER            =  'logger';
	CONST AUTOCANCELTIMEOUT =  'autocancel_timeout';
   
	      
   /**
     * Logging instance 
     * @var \Alshaya\Cybrequest\Logger\Logger
     */
    protected $_logger;
    protected $_logHandler;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterfac
     */
    protected $_scopeConfig;
	
	 /** @var  \Magento\Framework\UrlInterface */
    protected $_urlinterface ;

     /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;
    protected $_random;
	protected $_objectManager;
	protected $_locationHelper;
	protected $_logDirectory;
    public $timeout = 10;

	 /** @var  \Psr\Log\LoggerInterface */
    protected $_psrLogger;

	/** @var  \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    protected $_transactionBuilder;

    /** @var  \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress */
    protected $_remoteAddress;

    /**
     * Config country code per website
     *
     * @var array
     */
    protected $_config = [];
	
	/**
     * Cybersource Reason Code
     * @var array
     */
    
    protected $customerSession;
    
	protected $_cybersourceResponseCodeMap = array(
        0 => 'Unknown error.',
        100 => 'Transaction was successful.',
        101 => 'The request is missing one or more required fields.',
        102 => 'One or more fields in the request contains invalid data.',
        110 => 'Only a partial amount was approved.',
        150 => 'Error: General system failure.',
        151 => 'Error: The request was received but there was a server timeout. This error does not include timeouts between the client and the server.',
        152 => 'Error: The request was received, but a service did not finish running in time.',
        200 => 'The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the Address Verification System (AVS) check.',
        201 => 'The issuing bank has questions about the request. You do not receive an authorization code programmatically, but you might receive one verbally by calling the processor.',
        202 => 'Expired card. You might also receive this if the expiration date you provided does not match the date the issuing bank has on file.',
        203 => 'General decline of the card. No other information was provided by the issuing bank.',
        204 => 'Insufficient funds in the account.',
        205 => 'Stolen or lost card.',
        207 => 'Issuing bank unavailable.',
        208 => 'Inactive card or card not authorized for card-not-present transactions.',
        209 => 'American Express Card Identification Digits (CID) did not match.',
        210 => 'The card has reached the credit limit.',
        211 => 'Invalid CVN.',
        221 => 'The customer matched an entry on the processor’s negative file.',
        230 => 'The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the CVN check.',
        231 => 'Invalid account number.',
        232 => 'The card type is not accepted by the payment processor.',
        233 => 'General decline by the processor.',
        234 => 'There is a problem with the information in your CyberSource account.',
        235 => 'The requested capture amount exceeds the originally authorized amount.',
        236 => 'Processor failure.',
        237 => 'The authorization has already been reversed.',
        238 => 'The authorization has already been captured.',
        239 => 'The requested transaction amount must match the previous transaction amount.',
        240 => 'The card type sent is invalid or does not correlate with the credit card number.',
        241 => 'The request ID is invalid.',
        242 => 'You requested a capture, but there is no corresponding, unused authorization record. Occurs if there was not a previously successful authorization request or if the previously successful authorization has already been used by another capture request.',
        243 => 'The transaction has already been settled or reversed.',
        246 => 'One of the following: The capture or credit is not voidable because the capture or credit information has already been submitted to your processor. - or - You requested a void for a type of transaction that cannot be voided.',
        247 => 'You requested a credit for a capture that was previously voided.',
        250 => 'Error: The request was received, but there was a timeout at the payment processor.',
        254 => 'Error: Standalone credits are not allowed',
        400 => 'The fraud screen threshold has been exceeded by this order',
        475 => 'The customer is enrolled in Payer Authentication. Authenticate the cardholder before continuing with the transaction.',
        476 => 'The customer cannot be authenticated.',
        480 => 'The order has been rejected by Decision Manager',
        481 => 'The order has been rejected by Decision Manager'
    );
	
	protected $_customerRepositoryInterface;
	protected $datetime;
     /**
     * @description default Construct
     *
     * @param null
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $psrLogger,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
		\Magento\Framework\Math\Random $random,
		\Alshaya\Location\Helper\Data $locationHelper,
		\Magento\Framework\Filesystem $filesystem,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\UrlInterface $urlinterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
    	\Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
    	\Magento\Customer\Model\Session $customerSession,
    	\Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ){
        parent::__construct($context);
        $this->_psrLogger = $psrLogger;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_remoteAddress = $remoteAddress;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->_random = $random;
        $this->_logDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->_locationHelper = $locationHelper;
		$this->_urlinterface = $urlinterface;
        $this->_objectManager = $objectManager;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->datetime = $dateTime->gmtDate();
        $this->customerSession = $customerSession;
	}
    
	/**
     * Get base url for store
     *
     * @param bool|string $fromStore Include/Exclude from_store parameter from URL
     * @return string     
     */
    public function getBaseStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
	
	/**
     * Get base url for store
     *
     * @param bool|string $fromStore Include/Exclude from_store parameter from URL
     * @return string     
     */
    public function getSpecificBaseStoreUrl($storeId=null)
    {
        return $this->_storeManager->getStore($storeId)->getBaseUrl();
    }
    
	 /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
	
	 /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
	
	 /**
     * Get Store code
     *
     * @return string
     */
    public function getAllStoreId()
    {
		$storeCodeArr = array();
		$stores = $this->_storeManager->getStores(false,false);
		foreach($stores as $storeDatas)
	    {			
			$currencyCode = $this->_storeManager->getStore($storeDatas->getStoreId())->getCurrentCurrency()->getCode();
			if (preg_match("/ar/i", $storeDatas->getCode() )){
				$locale = 'ar-xn';
			}else
			{
				$locale = 'en-us';
			}			
	    	$storeCodeArr[$currencyCode."-".$locale] = $storeDatas->getStoreId();
	    }
        return $storeCodeArr;
    }
	
	 /**
     * Get Store code
     *
     * @return string
     */
    public function getRedirectStoreId($currency='KWD',$locale='en-us')
    {		
		$allStoreCodes = $this->getAllStoreId();
		if(isset($allStoreCodes[$currency."-".$locale]))
		{
			return $allStoreCodes[$currency."-".$locale];
		}		
		return $this->getStoreId(); 
    }
    
	
	/**
     * Retrieve Current Language Code
     *
     * @return string|URL
     */
    public function getLanguageCode()
    {
        $langCode = 'en-us';
        $currentURL = $this->getBaseStoreUrl();
        if (preg_match("/\_ar|\-ar/i", $currentURL )){
            $langCode = 'ar-xn';
        } 
        return $langCode; // Give the current url of recently viewed page
    }


    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getProperties($reqtype=null)
    {
    	
		$properties = [];
		
		$properties['merchant_id'] = $this->getConfig('merchant_id_alshaya');
        $properties['transaction_key'] = $this->getConfig('transaction_key_alshaya'); 
		$properties['secret_key'] = $this->getConfig('secret_key_alshaya'); 
        $properties['profile_id'] = $this->getConfig('profile_id_alshaya');
        $properties['access_key'] = $this->getConfig('access_key_alshaya'); 

		
		if($this->getConfig('sandbox_flag') == 1){
			$properties['wsdl'] = $this->getConfig('wsdl_test_mode');	
			$properties['cgi_url'] = $this->getConfig('cgi_url_test_mode');
			$properties['transaction_url'] = $this->getConfig('transaction_url_test_mode');			
		}else{
			
			$properties['wsdl'] = $this->getConfig('wsdl');
			$properties['cgi_url'] = $this->getConfig('cgi_url');
			$properties['transaction_url'] = $this->getConfig('transaction_url');
		}
		
		if($reqtype == 'authorization'){
			$properties['cgi_url']=$properties['transaction_url'];
			$properties['unsigned_field_names']='card_type,card_number,card_expiry_date,card_cvn';
			$properties['transaction_type']='authorization';
		}else{			
			$properties['cgi_url']=$properties['cgi_url'];
			$properties['unsigned_field_names']='card_type,card_number,card_expiry_date,card_cvn';
			$properties['transaction_type']='create_payment_token';
		}
		
		return $properties;
	}
	
	
    public function sign($params,$type=null) {
		
		$properties = $this->getProperties($type);
		$SECRET_KEY = $properties['secret_key'];	 //$this->getConfig('secret_key');
		$paramData=$this->buildDataToSign($params);
	  	return $this->signData($paramData, $SECRET_KEY);
	}

    public function signData($data, $secretKey) {
		return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
	}
    
    public function buildDataToSign($params) {
			$signedFieldNames = explode(",",$params["signed_field_names"]);
			foreach ($signedFieldNames as $field) {
			   $dataToSign[] = $field . "=" . $params[$field];
			}
			return $this->commaSeparate($dataToSign);
	}

    public function commaSeparate ($dataToSign) {
		return implode(",",$dataToSign);
	}

    public function getConfig($field, $storeId = null)
    {
        $store = $this->_storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        
         if (!isset($this->_config[$websiteId])) {
            $this->_config[$websiteId] = $this->_scopeConfig->getValue(
                self::SECTIONGROUPNAME,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }
        return isset($this->_config[$websiteId][$field]) ? (string)$this->_config[$websiteId][$field] : null;
    }

   public function getCardTypeode($code=false)
    {
			
/* 		001: Visa
		002: MasterCard
		003: American Express
		004: Discover
		005: Diners Club
		006: Carte Blanche
		007: JCB
		014: EnRoute
		021: JAL
		024: Maestro UK Domestic
		031: Delta
		033: Visa Electron
		034: Dankort
		036: Carte Bleue
		037: Carta Si
		042: Maestro International
		043: GE Money UK card
		050: Hipercard (sale only)
		054: Elo 
 */
		$cards=[
			'AE'=>'003',
			'VI'=>'001',
			'MC'=>'002',
			'DI'=>'004',
			'DN'=>'005',
			'JCB'=>'007',
			'MD'=>'024',
			'MI'=>'042'
		];
			
			return $cards[$code];
	}
	
	public function orderLogger($orderId)
	{
			$logpath = $this->_logDirectory->getAbsolutePath('log/orders/'); 
			if(!$this->_logDirectory->isExist($this->_logDirectory->getRelativePath('log/orders/')))
			{
                //if the folder not exit it will create one dummy file 
                $jsonpath =  $this->_logDirectory->getRelativePath('log/orders/');
                $filePath = $jsonpath.'test'.'.log';
                $this->_logDirectory->writeFile($filePath, 'test file created');
            } 

            $filename = 'import_'.$orderId.'.log';
            $filepath = $logpath . $filename;
            $writer = new \Zend\Log\Writer\Stream($filepath);
        	$order_logger = new \Zend\Log\Logger();
        	$order_logger->addWriter($writer);
		
		return $order_logger;
	}


	
	/**
	 @param $type authorization, create_payment_token
	 
	 **/
		
	public function submitForm($orderDetObj=null,$type='create_payment_token')
    {
		
		try{          

	        $request_param['order_data'] = $orderDetObj->getData();		
			
			$order_logger = $this->orderLogger($orderDetObj->getIncrementId());
			
	        $billingAddress = $orderDetObj->getBillingAddress();
	        $shippingAddress = $orderDetObj->getShippingAddress();
	        
	        $resp_ai =  array();
			$orderPayment = $orderDetObj->getPayment();
			$resp_ai['payment_method_info'] = $orderPayment->getAdditionalInformation();
			
	        $bcity = $this->_locationHelper->getLocationName($billingAddress);
	        $scity = $this->_locationHelper->getLocationName($shippingAddress);
			//setting the order currency to USD forcefully as test cards will work only for USD.
			//The below line must be commented out or removed if moved to production
			//$orderDetObj->setOrderCurrencyCode('USD');
			$orderAmount = number_format($orderDetObj->getGrandTotal(),3);
			$orderAmount = preg_replace('/,/','',$orderAmount);
			
			$properties = $this->getProperties($type);
			$signed_fields=[];
			
			$signed_fields['access_key'] = $properties['access_key'];
			$signed_fields['profile_id'] =  $properties['profile_id'];
			$signed_fields['transaction_uuid'] = uniqid(); 
			$signed_fields['signed_field_names'] = 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,payment_method,bill_to_forename,bill_to_surname,bill_to_email,bill_to_phone,bill_to_address_line1,bill_to_address_city,bill_to_address_country,ship_to_forename,ship_to_surname,ship_to_phone,ship_to_address_line1,ship_to_address_city,ship_to_address_country,merchant_defined_data1,customer_ip_address,device_fingerprint_id';
			if($properties['transaction_type'] == 'create_payment_token'){
				$signed_fields['signed_field_names'] = $signed_fields['signed_field_names'];
			}
			
			$signed_fields['unsigned_field_names'] = $properties['unsigned_field_names'];
			$signed_fields['signed_date_time'] = gmdate("Y-m-d\TH:i:s\Z");
			$signed_fields['locale'] = $this->getLanguageCode();
			$signed_fields['transaction_type'] = $type;
			$signed_fields['reference_number'] = $orderDetObj->getIncrementId();
			$signed_fields['amount'] = $orderAmount;
			$signed_fields['currency'] = $orderDetObj->getOrderCurrencyCode();
			$signed_fields['payment_method'] = 'card';
			/*** Billing Details ***/
			$signed_fields['bill_to_forename'] = $billingAddress->getFirstname();
			$signed_fields['bill_to_surname'] = $billingAddress->getLastname();
			$signed_fields['bill_to_email'] = $billingAddress->getEmail();
			$signed_fields['bill_to_phone'] = $billingAddress->getPrefixmMobileno();
			$signed_fields['bill_to_address_line1'] = $billingAddress->getStreetAddress();  
			$signed_fields['bill_to_address_city'] = $bcity;
			//$signed_fields['bill_to_address_state'] = 'SA'; //$billingAddress->getPrefixmMobileno();
			$signed_fields['bill_to_address_country'] = $billingAddress->getCountryId();
			$signed_fields['merchant_defined_data1'] = $this->getBaseStoreUrl();
			/*** Shipping Details ***/
			$signed_fields['ship_to_forename'] = $shippingAddress->getFirstname();
			$signed_fields['ship_to_surname'] = $shippingAddress->getLastname();
			$signed_fields['ship_to_phone'] = $shippingAddress->getPrefixmMobileno();
			$signed_fields['ship_to_address_line1'] = $shippingAddress->getStreetAddress();  
			$signed_fields['ship_to_address_city'] = $scity;
			$signed_fields['ship_to_address_country'] = $shippingAddress->getCountryId();
			
			//$signed_fields['merchant_defined_data1'] = $this->getBaseStoreUrl();
			$signed_fields['customer_ip_address'] = $this->rm_visitor_ip();
			//if we get CheckoutSession through DI, then in CLI comment for paymentsettelment we are getting error as "Area code must be set before starting a session."
            $signed_fields['device_fingerprint_id'] = $this->_objectManager->get('\Magento\Checkout\Model\Session')->getSessionId();
			
            
            /******* Merchant Defined Data **********/  
            $signed_fields['merchant_defined_data2'] = $this->customerSession->getChannelName(); //channel of sale    
			$custRegisterId = '';
			$custRegistered = '';
			if($orderDetObj->getCustomerId()){
				$custRegisterId = $orderDetObj->getCustomerId();
				$custRegistered = "Yes"; //customer register  
			} else {
				$custRegistered = "No";
			}
            $signed_fields['merchant_defined_data3'] = $custRegisterId;
            $signed_fields['merchant_defined_data4'] = $orderDetObj->getCustomerEmail(); //customer id
            
            $accountAge = 0;
            $noOfDaysLastOrder = 0;
            $orderCount = 0;
            if($custRegisterId){
            	$customerData = $this->_customerRepositoryInterface->getById($custRegisterId);   
            	$accountAge = $this->getAccountAge($customerData->getCreatedAt());
            	
            	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            	$order = $objectManager->get('\Magento\Sales\Model\Order');
            	$order_information = $order->getCollection()->addFieldToFilter('customer_id',$custRegisterId);
            	$orderCount = $order_information->count();
            	$lastOrderData = $order_information->setOrder('increment_id','DESC')->setPageSize(1);				
				foreach($lastOrderData as $data){
					$lastOrderDate = $data->getCreatedAt();	
					$noOfDaysLastOrder = $this->getAccountAge($lastOrderDate);	
				}         
            }
            
            $signed_fields['merchant_defined_data5'] = $accountAge; //Account Age
            
            $ordCarrierInfo = json_decode($orderDetObj->getCarrierInfo());
            $shipMode = $ordCarrierInfo->ord_type_name;
            $shipTimeFrame = $ordCarrierInfo->crs_name;    
            $shippingMethod = $ordCarrierInfo->dop_name;            
            $signed_fields['merchant_defined_data6'] = $shippingMethod; //shipping method 
            $signed_fields['merchant_defined_data7'] = $shipTimeFrame; //ship time frame
            $signed_fields['merchant_defined_data8'] = $shipMode; //shipping mode
            $signed_fields['merchant_defined_data9'] = $orderCount; //Number of previous orders 
            $signed_fields['merchant_defined_data10'] = $noOfDaysLastOrder; //Number of days since last purchase
            $signed_fields['merchant_defined_data11'] = ""; //blank
            $signed_fields['merchant_defined_data12'] = $orderDetObj->getQtyOrdered(); //Tot Qty Ordered
            $signed_fields['merchant_defined_data13'] = "";
            
            $orderType = $orderDetObj->getOrderType();
            if($orderType == 'ship_to_store'){
            	$signed_fields['merchant_defined_data14'] = ""; //blank
            } else {
            	$billingAddress = $orderDetObj->getShippingAddress()->getData();
            	$shippingAddress = $orderDetObj->getBillingAddress()->getData();
                $billshipCheck =$this->checkBillShipSame($billingAddress,$shippingAddress);
            	$signed_fields['merchant_defined_data14'] = $billshipCheck; //bill & ship same => No | bill &ship diff => Yes
            }            
            
            $signed_fields['merchant_defined_data15'] = ""; //Number of Refund - blank
            $signed_fields['merchant_defined_data16'] = ""; //product brand
            $signed_fields['merchant_defined_data17'] = ""; //product category            
            $promoCodeUsed =  $orderDetObj->getCouponCode();
            if($promoCodeUsed){
            	$promoValue = "Yes";
            } else {
            	$promoValue = "No";
            }
            $signed_fields['merchant_defined_data18'] = $promoValue; //promocode             
            $signed_fields['merchant_defined_data19'] = ""; //Promotion code if applied - blank
            $signed_fields['merchant_defined_data20'] = ""; //Gift Cards used - blank
            $signed_fields['merchant_defined_data21'] = ""; //Number of Gift Cards used - blank
            
            
            
		    /******* Logging the Signed fields data **********/
			$logger = $signed_fields;
			unset($logger['access_key']);
			unset($logger['profile_id']);
			unset($logger['transaction_uuid']);

			$resp_ai['request_data_sent'] = $logger;
			$orderPayment->setAdditionalInformation($resp_ai);
			$orderPayment->save();
			
			$order_logger->info(print_r($logger,1));
			
			$unsigned_fields['signature']=$this->sign($signed_fields, $type);
			$unsigned_fields['cgi_url'] = $properties['cgi_url'];
			
			$fieldslist=array_merge($signed_fields,$unsigned_fields);

			return $fieldslist;
		}catch (\Exception $e) {
            $order_logger->info($e);
        }
    }
	
	/**
	* 	
	* @var $reasonCode Cybersource Error Code
	*/	
	public function getCyberSourceMessage($reasonCode)
    {
        if (array_key_exists($reasonCode, $this->_cybersourceResponseCodeMap)) {
            return $this->_cybersourceResponseCodeMap[$reasonCode];
        } else {
            return $this->_cybersourceResponseCodeMap[0];
        }
    }

    /** @return string */
	public function rm_visitor_ip(){
		return $this->_remoteAddress->getRemoteAddress();
	}


	/**
     * Create transaction record in sales payment transaction table
     *
     * @param object $order
     * @param array  $paymentData
     *
     * @return int $transaction_Id
     */
    public function createTransaction($order = null, $paymentData = array())
    {
        try 
        {
			// logging detailed info
			$orderId = $order->getIncrementId();
            $order_logger = $this->orderLogger($orderId);
            $order_logger->info(print_r($paymentData,1));
			
            $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER;
            switch ($paymentData['txnType']) {
                            case "ORDER":
                                 $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER;
                                 break;
                            case "AUTHORIZATION":
                                 $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                                 break;
                            case "CAPTURE":
                                 $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                                 break;
                            case "VOID":
                                 $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID;
                                 break;
                            case "REFUND":
                                 $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;
                                 break;
                            default:
                                $txn_type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER;
                }
                        
                //Getting the payment object
                $payment = $order->getPayment();
				
				// logging detailed info
				$order_logger->info($orderId); 
                $order_logger->info($txn_type);  
                         
                //get the object of builder class
                $trans = $this->_transactionBuilder;
                $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build($txn_type);
                
                $transaction->setIsClosed($paymentData['is_closed']);
                $transaction->setParentTxnId($paymentData['parentTxnId']);
                
                return  $transaction->save()->getTransactionId();
        }
        catch (Exception $e)
        {
            $this->_psrLogger->critical($e->getMessage());
        }
    }
    
    
    /**
     * calculating account age
     *
     * @param user register $regDate
     *
     * @return int $accAge
     */    
    public function getAccountAge($regDate){
    	    	
    	$diff = abs(strtotime($this->datetime) - strtotime($regDate));
    	
    	$accountAge = floor($diff/ (60*60*24));
    	
    	return  $accountAge;
    }
	
    /**
     * checking billing and shipping same or not
     *
     * @param billing address
     * @param shipping address
     *
     * @return string Yes|No
     */
    public function checkBillShipSame($billingAddress,$shippingAddress){
    
    	$excludeKeys = array('entity_id', 'customer_address_id', 'quote_address_id', 'region_id', 'customer_id', 'address_type');	
		$billingAddressFiltered = array_diff_key($billingAddress, array_flip($excludeKeys));
		$shippingAddressFiltered = array_diff_key($shippingAddress, array_flip($excludeKeys));
		$addressDiff = array_diff($billingAddressFiltered, $shippingAddressFiltered);
		if( $addressDiff ) { // billing and shipping addresses are different
			return "Yes";
		} else {
			return "No";
		}
		
    }
    
    /*
     *device detection 
     * 
     */
    public function detectDevice($userAgent){
    	$detectDevice = '';
    	$devicesTypes = array(
    			"Ecom" => array("msie 10", "msie 9", "msie 8", "windows.*firefox", "windows.*chrome", "x11.*chrome", "x11.*firefox", "macintosh.*chrome", "macintosh.*firefox", "opera"),
    			"mobile"   => array("tablet", "android", "ipad", "tablet.*firefox"),
    			"mobile"   => array("mobile ", "android.*mobile", "iphone", "ipod", "opera mobi", "opera mini"),
    			"mobile"      => array("googlebot", "mediapartners-google", "adsbot-google", "duckduckbot", "msnbot", "bingbot", "ask", "facebook", "yahoo", "addthis")
    	);
    	foreach($devicesTypes as $deviceType => $devices) {
    		foreach($devices as $device) {
    			if(preg_match("/" . $device . "/i", $userAgent)) {
    				$deviceName = $deviceType;
    			}
    		}
    	}
    	$detectDevice = ucfirst($deviceName);
    	$this->customerSession->setChannelName($detectDevice);    	
    }
}


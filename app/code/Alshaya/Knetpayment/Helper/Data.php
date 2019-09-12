<?php
/**
 * Data.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Knetpayment\Helper;

/**
 * Class Data
 * @description  Helper Data class to get the system configuration for Knet payment gateway
 *               
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /* Defining the Constants */
    CONST SECTIONGROUPNAME  =  'payment/knetpayment';
    CONST RESOURCEPATH      =  'resource_path';
    CONST ALIAS             =  'alias';
    CONST ACTION            =  'action';
    CONST LANGUAGECODE      =  'language_code';
    CONST CURRENCYCODE      =  'currency_code';
    CONST AUTOCANCELTIMEOUT =  'autocancel_timeout';
    CONST BASEURL           =  'base_url';
    CONST RESPONSEURL       =  'response_url';
    CONST ERRORURL          =  'error_url';
    CONST LOGGER            =  'logger';    
    CONST ACTIONVALUE       =   1; // action value must always be set to 1 


    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterfac
     */
    protected $_scopeConfig;

     /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;
    
     /** @var  \Magento\Framework\UrlInterface */
    protected $_urlinterface ;

    /** @var  \Psr\Log\LoggerInterface */
    protected $_logger;

    /** @var  \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    protected $_transactionBuilder;

    
    /**
     * Config country code per website
     *
     * @var array
     */
    protected $_config = [];
     
     /**
     * @description default Construct
     *
     * @param null
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlinterface
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->_urlinterface = $urlinterface;
    }

    

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getConfig($field, $storeId = null)
    {
        $store = $this->_storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        
         if (!isset($this->_config[$websiteId])) {
            $this->_config[$websiteId] = $this->scopeConfig->getValue(
                self::SECTIONGROUPNAME,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return isset($this->_config[$websiteId][$field]) ? (string)$this->_config[$websiteId][$field] : null;
    }
    
    
    /**
     * Retrieve Current Language Code
     *
     * @return string|URL
     */
    public function getLanguageCode()
    {
        $langCode = 'ENG';
       
        $currentURL = $this->_urlinterface->getCurrentUrl();
        if (preg_match("/\bkwt_ar|kwt-ar\b/i", $currentURL )){
            $langCode = 'ARA';
        } 
        return $langCode; // Give the current url of recently viewed page
    }

     /**
     * Retrieve Current Language Code
     *
     * @return string|URL
     */
    public function getSiteLanguageCode()
    {
        $store = $this->_storeManager->getStore();
        $code= $store->getCode();
        $langCode = 'ENG';
        $pattern  =  "/\b".$code."\b/i";
        $currentURL = $this->_urlinterface->getCurrentUrl();
        
        if (preg_match($pattern, $currentURL) && preg_match("/\bar\b/i", $code)){
            $langCode = 'ARA';
        } 
      
        return $langCode; // Give the current url of recently viewed page
    }



    
     /**
     * Retrieve error msg from payment response code
     *
     * @param string $errorCode
     *
     * @return string $errorMsg
     */
    public function getErrorMsg($errorCode){

            $errorMsg = '';
            switch ($errorCode) {
                case "NOT CAPTURED":
                    $errorMsg = "Transaction unsuccessful. Please try again.";
                    break;
                case "HOST TIMEOUT":
                    $errorMsg = "Transaction not authorized. Please try again.";
                    break;
                case "DENIDED BY RISK":
                    $errorMsg = "Transaction declined due to risk. Please contact customer service for assistance.";
                    break;
                case "CANCELED":
                     $errorMsg = "Transaction cancelled. Please contact customer service for assistance.";
                    break;
                case "VOIDED":
                    $errorMsg = "Transaction voided. Please contact customer service for assistance.";
                    break;
                case "SESSINV":
                    $errorMsg = "Invalid Payment Session.";
                    break;
                case "CAPTURED":
                    $errorMsg = "Transaction was approved.";
                    break;
                default:
                     $errorMsg = "Transaction unsuccessful. Please try again.";
        }

        return $errorMsg;
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
            $this->_logger->critical($e->getMessage());
        }
    }
}


<?php
/**
 * Knetrequest.php
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

use Magento\Framework;
use Magento\Framework\Controller\ResultFactory;
use Knet_com_aciworldwide_commerce_gateway_plugins_e24PaymentPipe;

/**
 * Class Knetrequest
 * @description  Knetrequest used for building the knet request URL and 
 *               redirecting to the Knetpayment gateway
 */
class Knetrequest  extends \Magento\Framework\App\Action\Action
{
     /**
     * Logging instance
     * @var \Alshaya\Knetpayment\Logger\Logger
     */
    protected $_logger;
    protected $_logHandler;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \YourNamespace\YourModule\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Alshaya\Knetpayment\Logger\Logger $logger,
        \Alshaya\Knetpayment\Logger\Handler $handler
    ) {
        $this->_logger = $logger;
        $this->_logHandler = $handler;
        parent::__construct($context);
    }
    

    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $mObj = $this->_objectManager->create('Alshaya\Knetpayment\Model\Item');
        $helperObj = $this->_objectManager->get('Alshaya\Knetpayment\Helper\Data');
        $storeMngObj = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');          
        $onePageObj = $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage');
        $session = $onePageObj->getCheckout();
        if (!$this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator')->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        
        $helperObj->getSiteLanguageCode();
    
        
        $request_param=[];
        $order = $this->_objectManager->create('Magento\Sales\Model\Order');
        $entityOrderId = $session->getLastOrderId();
        $orderDetObj = $order->load($entityOrderId);
        $request_param['order_data'] = $orderDetObj->getData();
        $incrOrderId = $orderDetObj->getData('increment_id');
        
         
        $logParams = array();
        $logParams['logger'] = $helperObj->getConfig($helperObj::LOGGER);
        $logParams['file'] = $incrOrderId.'.log';
        
        $this->_logger->debug('###############################################################################',$logParams);
        $this->_logger->debug('Knet Request Starts', $logParams);
        

        /**
        *   Getting required data from the order object 
        */
        $orderId =  $orderDetObj->getData('increment_id');
        $grand_total = number_format($orderDetObj->getData('grand_total'), 3, '.', '');
        $subtotal = number_format($orderDetObj->getData('subtotal'), 3, '.', '');
        $customer_id =  $orderDetObj->getData('customer_id');
        $billing_address_id =  $orderDetObj->getData('billing_address_id');
        $store_currency_code =  $orderDetObj->getData('store_currency_code');
        $total_qty_ordered = $orderDetObj->getData('total_qty_ordered');

        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);        
        
        /**
        *   Setting the configuration array with the response data
        */
        $config_data ='';
        
        $config_data['resource_path'] = $helperObj->getConfig($helperObj::RESOURCEPATH);
        $config_data['alias'] = $helperObj->getConfig($helperObj::ALIAS);
        $config_data['action'] = $helperObj::ACTIONVALUE;
        $config_data['language_code'] = $helperObj->getSiteLanguageCode();
        $config_data['currency_code'] = $helperObj->getConfig($helperObj::CURRENCYCODE);
      
        $config_data['response_url'] = $this->_url->getUrl('knetpayment/standard/knetresponse',['_secure' => true]);
        $config_data['error_url'] = $this->_url->getUrl('knetpayment/standard/kneterror',['_secure' => true]);
                    
        /**
        *   Setting the configuration for the knet and its resource files
        */
        $basePath = BP."/lib/internal/Knet";
        $resourcePath = $basePath.$config_data['resource_path'];
       
        $alias =  $config_data['alias'];
        $action = $config_data['action'];
        $langId = $config_data['language_code'];
        $currencyCode = $config_data['currency_code'];
        $responseUrl = $config_data['response_url'];
        $errorUrl = $config_data['error_url'];
  
        $Pipe = new Knet_com_aciworldwide_commerce_gateway_plugins_e24PaymentPipe();
        
        $Pipe->setAction($action); 
        $Pipe->setCurrency($currencyCode);
        $Pipe->setLanguage($langId); 
        $Pipe->setResponseURL($responseUrl); 
        $Pipe->setErrorURL($errorUrl);
        
        $Pipe->setResourcePath($resourcePath);
        $Pipe->setAlias($alias);
        $Pipe->setTrackId($orderId);
        $Pipe->setAmt($grand_total);
        $Pipe->setUdf1("$subtotal");
        $Pipe->setUdf2("$customer_id");
        $Pipe->setUdf3("$billing_address_id");
        $Pipe->setUdf4("$store_currency_code");
        $Pipe->setUdf5("ptlf $orderId");

        $request_param['Pipe']=$Pipe;

        $orderPayment = $order->getPayment();
        $orderPayment->setAmountAuthorized($grand_total);
        $orderPayment->save();

        /**
        *   Checking the paymentId & redirecting to the knet payment gateway or to the checkout failure page.
        */
        $this->_logger->debug(print_r($request_param,true),$logParams);
        if($Pipe->performPaymentInitialization()!=$Pipe->SUCCESS) { 
               
                $this->_logger->debug('Payment URL Error',$logParams);
                $this->_logger->debug(print_r($Pipe->getDebugMsg(),true),$logParams);
                $this->messageManager->addError(__('Transaction unsuccessful. Please try again.'));
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->setStatus('payment_gateway_declined');
                $order->save();
                return $this->resultRedirectFactory->create()->setPath('checkout/confirmation/failure');
                
        } else { 
                       
            $Pipe->getDebugMsg();
            
            $paymentUrl = $Pipe->getPaymentPage()."&PaymentID=".$Pipe->getPaymentId();
            $this->_logger->debug(print_r($Pipe->getDebugMsg(),true),$logParams);
            $this->_logger->debug('Payment URL =>'.$paymentUrl,$logParams);
            $this->getResponse()->setRedirect($paymentUrl);
            
        }
    }
}

<?php
/**
 * Cron.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Knetpayment\Model;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */
use Magento\Framework\ObjectManagerInterface;



/**
 * Class Cron
 * @description  Defining the methods which will generate the reports and e-mail Notifications.
 */
class Knetcron extends \Magento\Framework\Model\AbstractModel
{

    // Defining the required variable for the cron class
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
     * @var Sms
     */
    protected $_sms;
    
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
        \Alshaya\Knetpayment\Logger\Logger $logger,
        \Alshaya\Knetpayment\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\App\Request\Http  $request,
        \Alshaya\Sms\Model\Sms $sms,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) 
    {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_objectManager = $objectManager;
        $this->transportBuilder = $transportBuilder;
        $this->appState = $appState;
        //$appState->setAreaCode('frontend');
        $this->inlineTranslation = $inlineTranslation;
        $this->_datetime = $dateTime;
        $this->timezone = $timezone;
        $this->_areaList = $areaList;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->_sms = $sms;
    }
        

    /**
     * @description creates the report and sends the e-mail Alert 
     *
     * @return object
     */
    public function processKnetOrders()
    {  
                
        $timeout = $this->_helper->getConfig($this->_helper::AUTOCANCELTIMEOUT);
        
        //Loggin Parameters from Knet response 
        $logParams = array();
        $logParams['logger'] = $this->_helper->getConfig($this->_helper::LOGGER);
        $this->_logger->debug('Knet Autocancel cron starts ...',$logParams);
        $this->_logger->debug('Auto Cancel Timeout Configured:'.$timeout,$logParams);

        if(empty($timeout))
        {
            $timeout = 0;
            $this->_logger->debug("Autocancel timeout configuration value is empty. Please set it properly.");
            exit;
        }
        
        $default_date = $this->_datetime->date(); 
        $this->_logger->debug(print_r($default_date,true),$logParams);
        $time = strtotime($default_date);
        $startTime = date("Y-m-d H:i:s", strtotime('-'.($timeout).' minutes', $time));
        $endTime = date("Y-m-d H:i:s", strtotime('-'.($timeout).' minutes', $time));      
        
        $this->_logger->debug("current date time:".$time,$logParams);
        $this->_logger->debug("start date time:".$startTime,$logParams);
        
        $sales_order = $this->_objectManager->create('Magento\Sales\Model\Order');
        $statusArr = array('pending_payment'); 
        
        $sales_result = $sales_order->getCollection()
        ->addAttributeToSelect(
                    'increment_id'
         ) 
         ->addAttributeToSelect(
                    'state'
         )
         ->addAttributeToSelect(
                    'status'
         )
         ->addAttributeToSelect(
                    'created_at'
        )
        ->addFieldToFilter(
                    'status',
                     $statusArr
        )->addFieldToFilter(
                    'created_at',
                    array('lt' => $startTime)
        );

        /*->addFieldToFilter(
                    'created_at',
                     array('lt' => $endTime)
        )*/
        
        $sales_result->getSelect()->join(
        array('payment' => 'sales_order_payment'),
        'main_table.entity_id=payment.parent_id AND method="knetpayment"', 
        array('method as payment_method')
        );

        $this->_logger->debug("Below is DB result ",$logParams);
       
        
        foreach($sales_result as $sales_data)
        {
            $this->_logger->debug("Order ID:".$sales_data['increment_id'],$logParams); 
            $increment_order_id = $sales_data['increment_id'];
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_order_id);
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setStatus('auto_cancelled');
            $order->save();
            $order->addStatusHistoryComment(
                                __('changed status to auto cancelled.',false)
                                )->setIsCustomerNotified(true)
                                ->save();
            $this->_logger->debug("Auto cancel order:".$increment_order_id,$logParams);
            $this->_sms->createSms($order);
        }
            
    }
 

    /**
     * @description prepares the mail  
     *
     * @param $fileAttachment file path along with file name to attach the file with mail
     * @param $errorCod mail error code
     * @param $errorMsg mail error message
     * @return string
     */
    function sendMail($increment_order_id){
                        
            $templateParams['increment_order_id']= $increment_order_id;
            $store = $this->_storeManager->getStore();
            $storeId= $store->getId();
                                   
            $customVariableObj = $this->_objectManager->create('Magento\Variable\Model\Variable');
            $configuredRecipients = $customVariableObj->loadByCode('auto_cancel_email_notification')->getValue('text');
            $configuredRecipients ='';
            echo "\n";
            echo $configuredRecipients;
            
            if (!strlen($configuredRecipients)) {
                 $recipients = array(
                  'customlowstock@gmail.com'                  
                ); 
                
            } else {
                $recipients = explode(",", $configuredRecipients);
            }

            $fromaddress = $customVariableObj->loadByCode('default_from_address')->getValue('text');             

            try {       
                    var_dump($recipients);
                 
                    foreach($recipients as $email)
                    {   
                        if(!empty($email) && !empty($fromaddress))
                        {
                            $transport = $this->transportBuilder->setTemplateIdentifier('knet_autocancel_email_template')
                            ->setTemplateOptions(['area' => 'frontend' , 'store' => $storeId])
                            ->setTemplateVars($templateParams)
                            ->setFrom(array('email'=>$fromaddress, 'name'=>'Contact Centre team'))
                            ->addTo($email,'Contact Centre team')
                            ->getTransport();
                            $transport->sendMessage();
                        }
                        
                    }
                } catch (Exception $e) {
                    $this->_logger->addDebug($e);
                    $this->_logger->addDebug($e->getMessage());
            }
            $this->_logger->addDebug("Mail Sent Successfully.");
    }

    
}

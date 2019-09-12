<?php
/**
 * CybersourceCustCancelFailed.php
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
 * Class CybersourceCustCancelFailed
 * @description  process the failed Auth reversal
 */
class CybersourceCustCancelFailedCron extends \Magento\Framework\Model\AbstractModel
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
    public function custAuthReversalFailedJob()
    {       
        
        $sales_order = $this->_objectManager->create('Magento\Sales\Model\Order');
        $statusArr = array('auth_reversal_failing','auth_reversal_failing_stock_shor','auth_reversal_dm','failing_auth_reversal_dm'); 
        
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
        );  
                
        $sales_result->getSelect()->join(
        array('payment' => 'sales_order_payment'),
        'main_table.entity_id=payment.parent_id AND method="cybersource"', 
        array('method as payment_method')
        );
        
        foreach($sales_result as $sales_data)
        {
            $increment_order_id = $sales_data['increment_id'];
            $start_date = $sales_data['created_at'];
            $status = $sales_data['status'];
                       
            $time = strtotime($start_date);
            $end_date = date("Y-m-d H:i:s", strtotime('+5 hours', $time));
            $current_date = date("Y-m-d H:i:s",strtotime("now"));
           
            $end_time = strtotime('+5 hours', $time);
            $current_time = strtotime("now");
            
                      
            if($end_time <= $current_time)
            {
                $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_order_id);
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                if($status == "auth_reversal_failing_stock_shor"){
                    $order->setStatus('auth_reversal_failed_stock_short');
                }
                else if($status == "auth_reversal_dm" || $status == "failing_auth_reversal_dm"){
                    $order->setStatus('failed_auth_reversal_dm');
                }
                else{
                    $order->setStatus('auth_reversal_failed');
                } 
                $order->save();
                $this->_logger->critical("Auth Reversal failed after 3days for the order:".$increment_order_id);
                $this->_sms->createSms($order);
            }
            else
            { 
                $cybAuthRevObj = $this->_objectManager->create('Alshaya\Cybersource\Model\CybersourceAuthReversal');
                if($status == "auth_reversal_failing_stock_shor"){
                    $ss_status = "stock_shortage";
                    $return = $cybAuthRevObj->authReversal($increment_order_id,$ss_status);
                    $this->_logger->critical("Auth Reversal response return:".$return);
                }
                else if($status == "auth_reversal_dm"){
                    $ss_status = "decision_manager_declined";
                    $return = $cybAuthRevObj->authReversal($increment_order_id,$ss_status);
                    $this->_logger->critical("Auth Reversal response return:".$return);
                }
                else if($status == "failing_auth_reversal_dm"){
                    $ss_status = "decision_manager_declined";
                    $return = $cybAuthRevObj->authReversal($increment_order_id,$ss_status);
                    $this->_logger->critical("Auth Reversal response return:".$return);
                }
                else{
                    $return = $cybAuthRevObj->authReversal($increment_order_id);
                    $this->_logger->critical("Auth Reversal response return:".$return);
                }
            }
        }
    }   
}

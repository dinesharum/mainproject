<?php
/**
 * CybersourceRefundFailedCron.php
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
class CybersourceRefundFailedCron extends \Magento\Framework\Model\AbstractModel
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
    public function refundCronJob()
    {       
        
        $sales_order = $this->_objectManager->create('Magento\Sales\Model\Order');
        $statusArr = array('fd_refund_failing','expiry_refund_failing','partial_refund_failing','complete_refund_failing'); 
        
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
                if($status == "fd_refund_failing"){
                    $order->setStatus('fd_refund_failed');
                }
                else if($status == "expiry_refund_failing"){
                    $order->setStatus('expiry_refund_failed');
                }
                else if($status == "partial_refund_failing"){
                    $order->setStatus('partial_refund_failed');
                }
                else{
                    $order->setStatus('complete_refund_failed');
                    $order->setData('status','complete_refund_failed');
                } 
                $order->save();
                $this->_logger->critical("refund failed after 3days for the order:".$increment_order_id);
                $this->_sms->createSms($order);
            }
            else
            { 
                $cybPartialRefundObj = $this->_objectManager->create('Alshaya\Cybersource\Model\CybersourcePartialRefund');
                
                if($status == "fd_refund_failing"){
                    
                    $return = $cybPartialRefundObj->failedRefund($increment_order_id,$status);
                }
                else if($status == "expiry_refund_failing"){
                    
                    $return = $cybPartialRefundObj->failedRefund($increment_order_id,$status);
                }
                else if($status == "partial_refund_failing"){
                    
                    $return = $cybPartialRefundObj->failedRefund($increment_order_id,$status);
                }
                else{
                    
                    $return = $cybPartialRefundObj->failedRefund($increment_order_id,$status);
                }               
                
            }   
            
        }
    }   
}

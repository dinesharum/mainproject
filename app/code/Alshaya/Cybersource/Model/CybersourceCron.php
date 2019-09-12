<?php
/**
 * CybersourceCron.php
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
 * Class Cron
 * @description  Defining the methods which will generate the reports and e-mail Notifications.
 */
class CybersourceCron extends \Magento\Framework\Model\AbstractModel
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
    public function processCybersourceOrders()
    {   
        $timeout = $this->_helper->getConfig($this->_helper::AUTOCANCELTIMEOUT);
        if(empty($timeout))
        {
            $timeout = 0;
            $this->_logger->addDebug("Autocancel timeout configuration value is empty. Please set it properly.");
            exit;
        }
        
        $default_date = $this->_datetime->date(); 
        $time = strtotime($default_date);
        $startTime = date("Y-m-d H:i:s", strtotime('-'.($timeout).' minutes', $time));
        $endTime = date("Y-m-d H:i:s", strtotime('-'.($timeout).' minutes', $time));      
        
               
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
        
              
        $sales_result->getSelect()->join(
        array('payment' => 'sales_order_payment'),
        'main_table.entity_id=payment.parent_id AND method="cybersource"', 
        array('method as payment_method')
        );
        
        foreach($sales_result as $sales_data)
        {
            $increment_order_id = $sales_data['increment_id'];
            $order_logger = $this->_helper->orderLogger($increment_order_id);
            $order_logger->info("Cybersource Autocancel cron starts ...");
            $order_logger->info("Time out:".$timeout);
            $order_logger->info("Order ID:".$increment_order_id);
            $order_logger->info(print_r($default_date,true));
            $order_logger->info("current date time:".$time);
            $order_logger->info("start date time:".$startTime);
            
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_order_id);
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setStatus('auto_cancelled');
            $order->save();
            $order->addStatusHistoryComment(
                                __('changed status to auto cancelled.',false)
                                )->setIsCustomerNotified(true)
                                ->save(); 
            $order_logger->info("Auto cancel order:".$increment_order_id);
            $this->_sms->createSms($order);
        }
    }   
}

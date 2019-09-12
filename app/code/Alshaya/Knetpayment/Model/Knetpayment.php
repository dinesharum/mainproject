<?php
/**
 * Item.php
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
use Magento\Framework;
use Magento\Framework\Controller\ResultFactory;

/**
 * Pay In Store payment method model
 */
class Knetpayment extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'knetpayment';

    /**
     * Availability option
     *
     * @var bool
     */
	 
    protected $_urlBuilder;
    protected $_ilogger;
	
    protected $_isOffline = true;
	
	/** 
	 * is Initialize
	 * 
	 */
	
	protected $_isInitializeNeeded = true;
	
	 /**
     * Constructor
     *
     * @return void
     */
    

	public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder, 
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
       parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
		
		$this->_urlBuilder = $urlBuilder;
		$this->_ilogger = $context->getLogger();
	}

	 public function initialize($paymentAction, $stateObject){
		
		$stateObject->setData("state",\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
		$stateObject->setData("status",\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
		$stateObject->setData("is_notified",0);
		
		return $this;				
	 }
	 public function getOrderPlaceRedirectUrl()
    {		
		return "knetpayment/standard/knetrequest";
	}
	 public function getConfigData($field, $storeId = null)
    {
		
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . $this->getCode() . '/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }
	
}

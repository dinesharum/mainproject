<?php
/**
 * After.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\CustomLowStock\Plugin;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */
use Magento\Framework\App\Action\Context;
use Alshaya\CustomLowStock\Model\ItemFactory;
use Magento\Store\Model\StoreManager;
use DateTimeZone;
use DateTime;
use DateInterval;
use Traversable;
use Zend\Stdlib\ArrayUtils;
/**
 * Class After
 * @description  After class gets the stock item information from the catalog inventory
 */
class After
{
	// Defining the required variable for the After class
	protected $_modelItemFactory;
	protected $_modelProductFactory;
	protected $_datetime;
	protected $_storeManager;
	protected $timezone;
	protected $_date;
	protected $_scopeConfig;
	protected $_storeManagerInterface;
	
	/**
     * @description initializing the construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory
	 * @param \Magento\Catalog\Model\ProductFactory $modelProductFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
	public function __construct(
        \Psr\Log\LoggerInterface $logger,
		\Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory,
        \Magento\Catalog\Model\ProductFactory $modelProductFactory,
		\Magento\Store\Model\StoreManager $storeManager,
		\Magento\Framework\Stdlib\DateTime\Timezone $timezone,
		\Magento\Framework\Stdlib\DateTime $date,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) 
	{
        $this->_logger = $logger;
        $this->_modelItemFactory = $modelItemFactory;
        $this->_modelProductFactory = $modelProductFactory;
        $this->_datetime = $dateTime;
		$this->_storeManager = $storeManager;
		$this->timezone = $timezone;
		$this->_scopeConfig = $scopeConfig;
		$this->_date = $date;
		$this->_storeManagerInterface = $storeManagerInterface;
	}

	/**
     * @description captures the information of stock items which are below the set threshold level 
     *
	 * @param $stockItemRepository Stock Item Repository
     * @param $stockItem stock item object
     */	
	public function afterSave($stockItemRepository, $stockItem)
	{		
			$product = $this->_modelProductFactory->create();
			$productId = $stockItem->getProductId();
        	$proddetails = $product->load($productId);
			$countryId = 0;
			
        	$customProductSku = $proddetails->getSku();
        	$customStyleCode  = $proddetails->getStyleCodeOrg();
        	$customProductName = $proddetails->getName();
        	$customProductSize = $proddetails->getAttributeText('size');
        	$customTimestamp = $this->_datetime->gmtDate();
			$customThreshold = $stockItem->getNotifyStockQty();
        	$currentStockQty = $stockItem->getQty();
			$countryId = $stockItem->getWebsiteId();
			$storeId = $stockItem->getStoreId();
			$lowstockDate = $stockItem->getLowStockDate();
			$websites = $this->_storeManager->getWebsites();
			$countryName = '';
			foreach ($websites as $website) 
			{
				if($website->getId() == $countryId){
					$countryName = $website->getName();
				}
			}
			$default_tz_date = $this->timezone->date();
			$format_date = $this->timezone->formatDateTime($default_tz_date,null,null,'en_US',null,'Y-MM-d HH:mm:ss');
			
			$this->_logger->addDebug("Format Date :".$format_date );
			$this->_logger->addDebug("Store ID :".$storeId );
			$this->_logger->addDebug("Low Stock Date :".$lowstockDate);
            $this->_logger->addDebug("Product ID:".$proddetails->getId());
            $this->_logger->addDebug('customThreshold : =>'.$customThreshold);
	        $this->_logger->addDebug('currentStockQty : =>'.$currentStockQty);
			$this->_logger->addDebug('CountryName : =>'.$countryName);


	        if($currentStockQty <= $customThreshold){

	        	$model = $this->_modelItemFactory->create();
	        	$model->setSku($customProductSku);
		        $model->setStyleCode($customStyleCode);
		        $model->setProductName($customProductName);
		        $model->setSize($customProductSize);
		        $model->setTimestamp($format_date);
		        $model->setThreshold($customThreshold);
				$model->setCountry($countryName);
		        $model->save();
 	            $this->_logger->addDebug('Data Saved in the Custom table');
	        }
			else
			{
				$this->_logger->addDebug('Data Not Saved in the Custom table');
			}
	}
}
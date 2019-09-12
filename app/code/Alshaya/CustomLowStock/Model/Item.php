<?php
/**
 * Item.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\CustomLowStock\Model;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */
use \Alshaya\CustomLowStock\Api\Data\ItemInterface;

/**
 * Class Item
 * @description  Defining Model Item
 */
class Item extends \Magento\Framework\Model\AbstractModel implements ItemInterface
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Alshaya\CustomLowStock\Model\ResourceModel\Item');
    }

	
	/**
     * @description Retrieve low stock identifier
     *
     * @return int
     */
    public function getLowStockId(){

		return $this->getData(self::LOWSTOCK_ID);
	}

	/**
     * @description Set stock identifier
     *
     * @param int $id
     * @return $this
    */
	public function setLowStockId($id){

		return $this->setData(self::LOWSTOCK_ID,$id);
	}

	/**
     * @description Retrieve Item SKU
     *
     * @return string
     */
	public function getSku(){
		$this->getData(self::SKU);
	}

	/**
     * @description Set product SKU
     *
     * @param string $sku
     * @return $this
    */
	public function setSku($sku){
		$this->setData(self::SKU,$sku);
	}

	/**
     * @description Retrieve Item style code
     *
     * @return string
     */
	public function getStyleCode(){
		$this->getData(self::STYLE_CODE);
	}

	/**
     * @description Set Product Style Code
     *
     * @param string $styleCode
     * @return $this
    */
	public function setStyleCode($styleCode){
		$this->setData(self::STYLE_CODE,$styleCode);
	}

	/**
     * @description Retrieve Product Name
     *
     * @return string
     */
    public function getProductName(){
    	$this->getData(self::PRODUCT_NAME);
    }

	/**
     * @description Set Product Name
     *
     * @param string $productName
     * @return $this
    */
	public function setProductName($productName){
    	$this->setData(self::PRODUCT_NAME, $productName);
    }

	/**
     * @description Retrieve size
     *
     * @return string
     */
    public function getSize(){
    	$this->getData(self::SIZE);
    }

	/**
     * @description Set Size
     *
     * @param string $size
     * @return $this
    */
	public function setSize($size){
    	$this->setData(self::SIZE, $size);
    }

	/**
     * @description Retrieve Datetime
     *
     * @return datetime
     */
    public function getTimestamp(){
    	$this->getData(self::TIMESTAMP);
    }

	/**
     * @description Set Timestamp
     *
     * @param datetime $timestamp
     * @return $this
    */
    public function setTimestamp($timestamp){
    	$this->setData(self::TIMESTAMP,$timestamp);
    }

	/**
     * @description Retrieve Item threshold
     *
     * @return int
     */
    public function getThreshold(){
    	$this->getData(self::THRESHOLD);
    }

	/**
     * @description Set Threshold
     *
     * @param int $threshold
     * @return $this
    */
	public function setThreshold($threshold){
    	$this->setData(self::THRESHOLD, $threshold);
    }
	
	/**
     * @description Retrieve Item country
     *
     * @return string
     */
    public function getCountry(){
    	$this->getData(self::COUNTRY);
    }

	/**
     * @description Set Threshold
     *
     * @param int $threshold
     * @return $this
    */
	public function setCountry($country){
    	$this->setData(self::COUNTRY, $country);
    }
}

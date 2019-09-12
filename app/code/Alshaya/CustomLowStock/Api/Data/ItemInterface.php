<?php
/**
 * ItemInterface.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\CustomLowStock\Api\Data;

/**
 * Interface ItemInterface
 * @description  Interface can be used for API service
 */
interface ItemInterface
{
	// Defining the required constants for Data Array 
	const LOWSTOCK_ID = 'lowstock_id';
	const SKU = 'sku';
	const STYLE_CODE = 'style_code';
	const PRODUCT_NAME = 'product_name';
	const SIZE = 'size';
	const TIMESTAMP = 'timestamp';
	const THRESHOLD = 'threshold';
	const COUNTRY = 'country';

	// Declaring the getter & Setter methods for the interface
	/**
     * @description Retrieve low stock identifier
     *
     * @return int
     */
	public function getLowStockId();
	
	/**
     * @description Set stock identifier
     *
     * @param int $id
     * @return $this
    */
	public function setLowStockId($id);

	/**
     * @description Retrieve Item SKU
     *
     * @return string
     */
	public function getSku();
	
	/**
     * @description Set product SKU
     *
     * @param string $sku
     * @return $this
    */
	public function setSku($sku);

	
	/**
     * @description Retrieve Item style code
     *
     * @return string
     */
	public function getStyleCode();
	
	/**
     * @description Set Product Style Code
     *
     * @param string $styleCode
     * @return $this
    */
	public function setStyleCode($styleCode);

	
	/**
     * @description Retrieve Product Name
     *
     * @return string
     */
	public function getProductName();
	
	/**
     * @description Set Product Name
     *
     * @param string $productName
     * @return $this
    */
	public function setProductName($productName);

	/**
     * @description Retrieve size
     *
     * @return string
     */
	public function getSize();
	
	/**
     * @description Set Size
     *
     * @param string $size
     * @return $this
    */
	public function setSize($size);

	/**
     * @description Retrieve Datetime
     *
     * @return datetime
     */
	public function getTimestamp();
	
	/**
     * @description Set Timestamp
     *
     * @param datetime $timestamp
     * @return $this
    */
	public function setTimestamp($timestamp);

	/**
     * @description Retrieve Item threshold
     *
     * @return int
     */
	public function getThreshold();
	
	/**
     * @description Set Threshold
     *
     * @param int $threshold
     * @return $this
    */
	public function setThreshold($threshold);
	
	/**
     * @description Retrieve Item country
     *
     * @return string
     */
	public function getCountry();
	
	/**
     * @description Set Threshold
     *
     * @param string $country
     * @return $this
    */
	public function setCountry($country);
}

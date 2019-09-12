<?php
namespace Alshaya\Cybersource\Api\Data;

interface AddressInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
	/**#@+
	 * Constants defined for keys of array, makes typos less likely
	 */
	const KEY_BLOCK = 'block';
	const KEY_BUILDING_NUMBER = 'building_number';
	const KEY_FLOOR = 'floor';
	const KEY_STREET_ADDRESS = 'street_address';
	const KEY_PREFIXM_MOBILENO = 'prefixm_mobileno';
	const KEY_FIRSTNAME = 'firstname';
	const KEY_LASTNAME = 'lastname';
	const KEY_SAMEASBILLING = 'same_as_billing';
	const KEY_COUNTRY_ID = 'country_id';
	const KEY_DM_GOVERNATE = 'dm_governate';
	const KEY_DM_GOVERNATE_TEXT = 'dm_governate_text';
	const KEY_DM_AREA = 'dm_area';
	const KEY_DM_AREA_TEXT = 'dm_area_text';
	/**#@-*/
	
	/**
	 * Get Block value
	 * @api
	 * @return string|null
	 */
	public function getBlock();
	
	/**
	 * Set Block value
	 * @param string $block
	 * @return $this
	 */
	public function setBlock($block);
	
	/**
	 * Get Building Number
	 * @api
	 * @return string|null
	 */
	public function getBuildingNumber();
	
	/**
	 * Set Builing Number
	 * @param string $builgingNumber
	 * @return $this
	 */
	public function setBuildingNumber($builgingNumber);
	
	/**
	 * Get Floor
	 * @api
	 * @return string|null
	 */
	public function getFloor();

	/**
	 * Set Floor
	 * @param string $floor
	 * @return $this
	 */
	public function setFloor($floor);
	
	/**
	 * Set Street Address
	 * @param string $street
	 * @return $this
	 */
	public function setStreetAddress($street);
	
	/**
	 * Get Street Address
	 * @api
	 * @return string|null
	 */
	public function getStreetAddress();

	/**
	 * Set Prefixm Mobileno
	 * @param string $mobile
	 * @return $this
	 */
	public function setPrefixmMobileno($mobile);
	
	/**
	 * Get Prefixm Mobileno
	 * @api
	 * @return string|null
	 */
	public function getPrefixmMobileno();
	
	/**
	 * Set Customer Firstname
	 * @param string $name
	 * @return $this
	 */
	public function setFirstname($name);
	
	/**
	 * Get Customer Firstname
	 * @api
	 * @return string
	 */
	public function getFirstname();

	/**
	 * Set Customer Lastname
	 * @param string $name
	 * @return $this
	 */
	public function setLastname($name);
	
	/**
	 * Get Customer Lastname
	 * @api
	 * @return string
	 */
	public function getLastname();
	
	/**
	 * Get same as billing flag
	 *
	 * @return int|null
	 */
	public function getSameAsBilling();
	
	/**
	 * Set same as billing flag
	 *
	 * @param int $sameAsBilling
	 * @return $this
	 */
	public function setSameAsBilling($sameAsBilling);
	
	/**
	 * Get country id
	 *
	 * @return string
	 */
	public function getCountryId();
	
	/**
	 * Set country id
	 *
	 * @param string $countryId
	 * @return $this
	 */
	public function setCountryId($countryId);

	/**
	 * Get DM Governate
	 *
	 * @return int|null
	 */
	public function getDmGovernate();
	
	/**
	 * Set DM Governate id
	 *
	 * @param int $governateId
	 * @return $this
	 */
	public function setDmGovernate($governateId);

	/**
	 * Get DM Governate Text
	 *
	 * @return string|null
	 */
	public function getDmGovernateText();
	
	/**
	 * Set DM Governate Text
	 *
	 * @param string $governateText
	 * @return $this
	 */
	public function setDmGovernateText($governateText);

	/**
	 * Get DM Area id
	 *
	 * @return int|null
	 */
	public function getDmArea();
	
	/**
	 * Set DM Area id
	 *
	 * @param int $areaId
	 * @return $this
	 */
	public function setDmArea($areaId);
	
	/**
	 * Get DM Area Text
	 *
	 * @return string|null
	 */
	public function getDmAreaText();
	
	/**
	 * Set DM Area Text
	 *
	 * @param string $governateText
	 * @return $this
	 */
	public function setDmAreaText($areaText);
	
	/**
	 * Retrieve existing extension attributes object or create a new one.
	 *
	 * @return \Alshaya\Cybersource\Api\Data\AddressInterface|null
	 */
	public function getExtensionAttributes();
	
	/**
	 * Set an extension attributes object.
	 *
	 * @param \Alshaya\Cybersource\Api\Data\AddressInterface $extensionAttributes
	 * @return $this
	 */
	public function setExtensionAttributes(\Alshaya\Cybersource\Api\Data\AddressInterface $extensionAttributes);
}
<?php
namespace Alshaya\Cybersource\Model\Data;

class Address extends \Magento\Framework\Model\AbstractExtensibleModel implements \Alshaya\Cybersource\Api\Data\AddressInterface
{
	/**
	 * @{inheritdoc}
	 */
	public function setBlock($block)
	{
		return $this->setData(self::KEY_BLOCK, $block);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getBlock()
	{
		return $this->getData(self::KEY_BLOCK);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setStreetAddress($street)
	{
		return $this->setData(self::KEY_STREET_ADDRESS, $street);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getStreetAddress()
	{
		return $this->getData(self::KEY_STREET_ADDRESS);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setFloor($floor)
	{
		return $this->setData(self::KEY_FLOOR, $floor);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getFloor()
	{
		return $this->getData(self::KEY_FLOOR);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setPrefixmMobileno($mobile)
	{
		return $this->setData(self::KEY_PREFIXM_MOBILENO, $mobile);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getPrefixmMobileno()
	{
		return $this->getData(self::KEY_PREFIXM_MOBILENO);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setBuildingNumber($builgingNumber)
	{
		return $this->setData(self::KEY_BUILDING_NUMBER, $builgingNumber);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getBuildingNumber()
	{
		return $this->getData(self::KEY_BUILDING_NUMBER);
	}

	/**
	 * @{inheritdoc}
	 */
	public function setFirstname($name)
	{
		return $this->setData(self::KEY_FIRSTNAME, $name);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getFirstname()
	{
		return $this->getData(self::KEY_FIRSTNAME);
	}

	/**
	 * @{inheritdoc}
	 */
	public function setLastname($name)
	{
		return $this->setData(self::KEY_LASTNAME, $name);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getLastname()
	{
		return $this->getData(self::KEY_LASTNAME);
	}

	/**
	 * @{inheritdoc}
	 */
	public function setSameAsBilling($sameAsBilling)
	{
		return $this->setData(self::KEY_SAMEASBILLING, $sameAsBilling);
	}
		
	/**
	 * @{inheritdoc}
	 */
	public function getSameAsBilling()
	{
		return $this->getData(self::KEY_SAMEASBILLING);
	}
	
	/**
	 * @inheritDoc
	 */
	public function setCountryId($countryId)
	{
		return $this->setData(self::KEY_COUNTRY_ID, $countryId);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getCountryId()
	{
		return $this->getData(self::KEY_COUNTRY_ID);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getDmGovernate()
	{
		return $this->getData(self::KEY_DM_GOVERNATE);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setDmGovernate($governateId)
	{
		return $this->setData(self::KEY_DM_GOVERNATE, $governateId);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getDmGovernateText()
	{
		return $this->getData(self::KEY_DM_GOVERNATE_TEXT);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setDmGovernateText($governateText)
	{
		return $this->setData(self::KEY_DM_GOVERNATE_TEXT, $governateText);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getDmArea()
	{
		return $this->getData(self::KEY_DM_AREA);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setDmArea($areaId)
	{
		return $this->setData(self::KEY_DM_AREA, $areaId);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getDmAreaText()
	{
		return $this->getData(self::KEY_DM_AREA_TEXT);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setDmAreaText($areaText)
	{
		return $this->setData(self::KEY_DM_AREA_TEXT, $areaText);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getExtensionAttributes()
	{
		return $this->_getExtensionAttributes();
	}
	
	/**
	 * @inheritDoc
	 */
	public function setExtensionAttributes(\Alshaya\Cybersource\Api\Data\AddressInterface $extensionAttributes)
	{
		return $this->_setExtensionAttributes($extensionAttributes);
	}
}
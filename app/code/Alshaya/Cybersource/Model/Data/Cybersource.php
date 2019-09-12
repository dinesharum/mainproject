<?php

namespace Alshaya\Cybersource\Model\Data;

class Cybersource extends \Magento\Framework\Model\AbstractExtensibleModel implements \Alshaya\Cybersource\Api\Data\CybersourceInterface
{
	/**
	 * @{inheritdoc}
	 */
	public function setBilling(\Alshaya\Cybersource\Api\Data\AddressInterface $address)
	{
		return $this->setData(self::BILLING, $address);
	}
		
	/**
	 * @{inheritdoc}
	 */
	public function getBilling()
	{
		return $this->getData(self::BILLING);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setShipping(\Alshaya\Cybersource\Api\Data\AddressInterface $address)
	{
		return $this->setData(self::SHIPPING, $address);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getShipping()
	{
		return $this->getData(self::SHIPPING);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setPaymentMethod($method)
	{
		return $this->setData(self::PAYMENT_METHOD, $method);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getPaymentMethod()
	{
		return $this->getData(self::PAYMENT_METHOD);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setFormKey($key)
	{
		return $this->setData(self::FORM_KEY, $key);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getFormKey()
	{
		return  $this->getData(self::FORM_KEY);	
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function getTncAccepted()
	{
		return $this->getData(self::TNC_ACCEPTED);
	}
	
	/**
	 * @{inheritdoc}
	 */
	public function setTncAccepted($code)
	{
		return $this->setData(self::TNC_ACCEPTED, $code);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getExtensionAttributes()
	{
		return $this->_getExtensionAttributes();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function setExtensionAttributes(
			\Alshaya\Cybersource\Api\Data\CybersourceInterface $extensionAttributes
			) {
				return $this->_setExtensionAttributes($extensionAttributes);
	}	
	
}
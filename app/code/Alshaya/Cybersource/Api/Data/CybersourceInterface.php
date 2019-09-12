<?php

namespace Alshaya\Cybersource\Api\Data;

interface CybersourceInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
	/**#@+
	 * Constants defined for keys of the data array. Identical to the name of the getter in snake case
	 */
	const BILLING = 'billing';
	const SHIPPING = 'shipping';
	const TNC_ACCEPTED = 'tnc_acceoted';
	const PAYMENT_METHOD = 'payment_method';
	const FORM_KEY = 'form_key';
	/**#@-*/
	
	/**
	 * Set Billing Address
	 * @api
	 * @param \Alshaya\Cybersource\Api\Data\AddressInterface $addresses
	 * @return $this 
	 */
	public function setBilling(\Alshaya\Cybersource\Api\Data\AddressInterface $address);
	
	/**
	 * Get Billing Address
	 * 
	 * @return \Alshaya\Cybersource\Api\Data\AddressInterface $addresses   
	 */
	public function getBilling();
	
	/**
	 * Set Billing Address
	 * @api
	 * @param \Alshaya\Cybersource\Api\Data\AddressInterface $addresses
	 * @return $this
	 */
	public function setShipping(\Alshaya\Cybersource\Api\Data\AddressInterface $address);
	
	/**
	 * Get Billing Address
	 *
	 * @return \Alshaya\Cybersource\Api\Data\AddressInterface $addresses
	 */
	public function getShipping();
    
    /**
     * Returns Term & Condition accept
     *
     * @return int|null
     */
    public function getTncAccepted();
    
    /**
     * Returns Term & Condition accept
     *
     * @param string $code
     * @return int|null
     */
    public function setTncAccepted($code);

    /**
     * Set Payment Method
     * 
     * @param string $method
     * @return $this
     */
    public function setPaymentMethod($method);
    
    /**
     * Get Payment Method
     * 
     * @return string
     */
    public function getPaymentMethod();    

    /**
     * Set Form Key
     * 
     * @param string $key
     * @return $this
     */
    public function setFormKey($key);
    
    /**
     * Get Form Key
     * 
     * @return string
     */
    public function getFormKey();
    
    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @api
     * @return \Alshaya\Cybersource\Api\Data\CybersourceInterface|null
     */
    public function getExtensionAttributes();
    
    /**
     * Set an extension attributes object.
     *
     * @api
     * @param \Alshaya\Cybersource\Api\Data\CybersourceInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Alshaya\Cybersource\Api\Data\CybersourceInterface $extensionAttributes);
}
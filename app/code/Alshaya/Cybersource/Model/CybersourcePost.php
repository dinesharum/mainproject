<?php

/**
 * Copyright 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Alshaya\Cybersource\Model;
use Alshaya\Cybersource\Api\CybersourcePostInterface;
use Magento\Framework\Exception\PaymentException;

/**
 * Defines the implementaiton class of the CybersourcePost service contract.
 */
class CybersourcePost extends \Magento\Framework\Model\AbstractModel implements CybersourcePostInterface
{
	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	protected $_objectManager;

	/**
	 * @var \Alshaya\Cybersource\Api\Data
	 */
	protected $_helper;

	/**
	 * @var \Magento\Framework\Data\Form\FormKey\Validator
	 */
	protected $_formKey;

	/**
	 * @var \Magento\Framework\Message\ManagerInterface
	 */
	protected $messageManager;
	
	/**
	 * Factory constructor
	 *
	 * @param \Magento\Framework\ObjectManagerInterface $objectManager
	 * @param string $instanceName
	 */
	public function __construct(
			\Magento\Framework\ObjectManagerInterface $objectManager,
			\Alshaya\Cybersource\Helper\Data $helper,
			\Magento\Framework\Data\Form\FormKey $formKey,
			\Magento\Framework\Message\ManagerInterface $messageManager
			)
	{
		$this->_objectManager = $objectManager;
		$this->_helper = $helper;
		$this->_formKey = $formKey;
		$this->messageManager = $messageManager;
	}
    
    /**
     * Create Order
     * @api
     * @param  \Alshaya\Cybersource\Api\Data\CybersourceInterface $data 
     * @return string
     */
    public function createOrder(\Alshaya\Cybersource\Api\Data\CybersourceInterface $data)
    {
    	if($data->getFormKey() != $this->_formKey->getFormKey()){
    		$this->messageManager->addError(__('Please try again'));
    		return json_encode(['error'=> true, 'message' => 'Please try again'], JSON_UNESCAPED_SLASHES);
    	}
    	if(empty($data->getTncAccepted()))
    		return json_encode(['error'=> true, 'message' => 'Please accept terms and conditions'], JSON_UNESCAPED_SLASHES);
    	$quote = $this->getOnepage()->getQuote();
    	if($data->hasShipping()){
    		if ($data->getShipping()->hasSameAsBilling())
            	$quote->getShippingAddress()->setSameAsBilling(1);
            else
            	$quote->getShippingAddress()->setSameAsBilling(0);
    	}
		try{
			$payment['method'] = $data->getPaymentMethod();
			if ($payment) {
				$payment['checks'] = [
						\Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_CHECKOUT,
						\Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
						\Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
						\Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
						\Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
					];
				$quote->getPayment()->setQuote($this->getOnepage()->getQuote());
				$quote->getPayment()->importData($payment);
			}
        
        	$quote->save();
        	$this->getOnepage()->saveOrder();
        }
        catch(PaymentException $e)
        {
        	$this->messageManager->addError($e->getMessage());
        	return json_encode(['error'=> true, 'message' => 'Please try again'], JSON_UNESCAPED_SLASHES);
        }
      	return $this->getCybersourceFormData((int)$this->getOnepage()->getCheckout()->getLastOrderId());
    }
    
    /**
     * Get one page checkout model
     *
     * @return \Magento\Checkout\Model\Type\Onepage
     * @codeCoverageIgnore
     */
    public function getOnepage()
    {
    	return $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage');
    }
    
    /**
     * Get Cybersource details
     * @param int $orderId
     * @return string
     */
    public function getCybersourceFormData($orderId)
    {
    	$orderObj = $this->_objectManager->get('Magento\Sales\Model\Order');
    	$order = $orderObj->load($orderId);
    	$data = $this->_helper->submitForm($order,'authorization');
    	return json_encode($data,JSON_UNESCAPED_SLASHES);
    }
}
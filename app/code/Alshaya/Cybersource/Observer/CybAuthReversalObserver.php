<?php

/**
 * Auction Sms Observer.
 *
 * @category    Alshaya
 *
 * @author      Alshaya Outsourcing Private Limited
 */

namespace Alshaya\Cybersource\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class CybAuthReversalObserver implements ObserverInterface {
    /*
     * @var
     */

   
    protected $_cybAuthreversal;
	protected $_order;
	protected $_logger;
	protected $_objectManager; 
	
    public function __construct(
      \Alshaya\Cybersource\Model\CybersourceAuthReversal $cybAuthreversal,
	  \Magento\Sales\Model\Order $order,
	  \Magento\Framework\ObjectManagerInterface $objectManager,
	  \Psr\Log\LoggerInterface $logger
    ) {
        $this->_cybAuthreversal = $cybAuthreversal;
		$this->_order = $order;
		$this->_logger = $logger;
		$this->_objectManager = $objectManager;
    }

    public function execute(EventObserver $observer) {
		
        $orders = $observer->getEvent()->getOrder();
		$orderId = $orders->getId();
        $orderIncrementId = $orders->getIncrementId();
		$entityId = $orders->getEntityId();
				
		$preOrder = $this->_order->load($entityId);
		$state = $preOrder->getState();
		$status = $preOrder->getStatus();
		$orderPayment = $preOrder->getPayment();
		$paymentMethod = $orderPayment->getMethod();
		$smsObj = $this->_objectManager->create('Alshaya\Sms\Model\Sms');
		
		
		if($state == 'processing' && $status == 'accepted')
		{
			if($paymentMethod == 'cybersource' )
			{
				$return = $this->_cybAuthreversal->authReversalCustomerCancel($orderIncrementId);
				
				if($return === 'ACCEPT')
				{				
					$observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
					$observer->getEvent()->getOrder()->setStatus('customer_cancellation_initiated');
					$observer->getEvent()->getOrder()->addStatusHistoryComment(
										__('changed status to Customer cancellation initiated.',false)
										)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();
				
					$observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
					$observer->getEvent()->getOrder()->setStatus('customer_cancellation_complete');
					$observer->getEvent()->getOrder()->addStatusHistoryComment(
									__('changed status to Customer cancellation complete.',false)
									)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();
				
				}else if($return === 'FAILED'){
				
					 $observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
					 $observer->getEvent()->getOrder()->setStatus('auth_reversal_failing');
					 $observer->getEvent()->getOrder()->addStatusHistoryComment(
									__('changed status to customer cancellation failing.',false)
									)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();
				}
				else if($return === 'EXECUTION_FAILED')
				{
					$observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
					$observer->getEvent()->getOrder()->setStatus('auth_reversal_failing');
					$observer->getEvent()->getOrder()->addStatusHistoryComment(
									__('changed status to customer cancellation failing.',false)
									)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();
					$this->_logger->critical("Customer cancellation process - EXECUTION_FAILED.");
				}
				$smsObj->createSms($orders);
								
			}
			else if($paymentMethod == 'knetpayment'){
					$smsObj->createSms($orders);
					$observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
					$observer->getEvent()->getOrder()->setStatus('customer_cancellation_initiated');
					$observer->getEvent()->getOrder()->addStatusHistoryComment(
									__('changed status to Customer cancellation initiated.',false)
									)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();

					$smsObj->createSms($observer->getEvent()->getOrder());
					/*$observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
					$observer->getEvent()->getOrder()->setStatus('customer_cancellation_complete');
					$observer->getEvent()->getOrder()->addStatusHistoryComment(
									__('changed status to Customer cancellation complete.',false)
									)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();
					$smsObj->createSms($observer->getEvent()->getOrder());*/
			}
			else if($paymentMethod == 'cashondelivery'){
					$observer->getEvent()->getOrder()->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
					$observer->getEvent()->getOrder()->setStatus('customer_cancellation_complete');
					$observer->getEvent()->getOrder()->addStatusHistoryComment(
									__('changed status to Customer cancellation complete.',false)
									)->setIsCustomerNotified(true);
					$observer->getEvent()->getOrder()->save();
					$smsObj->createSms($observer->getEvent()->getOrder());
			}
			else{
				$this->_logger->critical("payment method is not Cybersource or knetpayment.");
			}
		}
		else{
			$this->_logger->critical("Status is not accepted.");
		}
    }
}

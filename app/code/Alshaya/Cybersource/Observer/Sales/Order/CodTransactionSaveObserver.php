<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * OfflinePayments Observer
 */
namespace Alshaya\Cybersource\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;
use Magento\OfflinePayments\Model\Banktransfer;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\OfflinePayments\Model\Checkmo;

class CodTransactionSaveObserver implements ObserverInterface
{
    protected $_helper;
    public function __construct(
      \Alshaya\Cybersource\Helper\Data $helper      
    ){
        $this->_helper = $helper;        
    }

    /**
     * Sets current instructions for bank transfer account
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getIncrementId();
        $salesPayment = $order->getPayment();
        $paymentMethod = $salesPayment->getMethod();
        $uniqueId = mt_rand(4000000000000000, 9999999999999999);
        
        if ($paymentMethod == "cashondelivery"){ 
            // creating the transaction record for COD order
            $paymentData = array();
            $paymentData['id'] = $uniqueId;
            $paymentData['parentTxnId'] = NULL;
            $paymentData['is_closed'] = 0;
            $paymentData['txnType'] = "ORDER";

            if($orderId){
                $this->_helper->createTransaction($order,$paymentData);
            }
        }
    }
}

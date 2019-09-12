<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Alshaya\Cybersource\Model;

use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Info;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;

/**
 * Order payment information
 *
 * @method \Magento\Sales\Model\ResourceModel\Order\Payment _getResource()
 * @method \Magento\Sales\Model\ResourceModel\Order\Payment getResource()
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payment extends \Magento\Sales\Model\Order\Payment
{
    /**
     * Actions for payment when it triggered review state
     *
     * @var string
     */
    const REVIEW_ACTION_ACCEPT = 'accept';

    const REVIEW_ACTION_DENY = 'deny';

    const REVIEW_ACTION_UPDATE = 'update';

    /**
     * Order model object
     *
     * @var Order
     */
    protected $_order;

    /**
     * Whether can void
     * @var string
     */
    protected $_canVoidLookup = null;

    /**
     * @var string
     */
    protected $_eventPrefix = 'sales_order_payment';

    /**
     * @var string
     */
    protected $_eventObject = 'payment';

    /**
     * Transaction additional information container
     *
     * @var array
     */
    protected $transactionAdditionalInfo = [];

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected $transactionManager;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var Payment\Processor
     */
    protected $orderPaymentProcessor;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager
     * @param Transaction\BuilderInterface $transactionBuilder
     * @param Payment\Processor $paymentProcessor
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        ManagerInterface $transactionManager,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Processor $paymentProcessor,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->transactionRepository = $transactionRepository;
        $this->transactionManager = $transactionManager;
        $this->transactionBuilder = $transactionBuilder;
        $this->orderPaymentProcessor = $paymentProcessor;
        $this->orderRepository = $orderRepository; 
		
		 parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $encryptor,
			$creditmemoFactory,
			$priceCurrency,
			$transactionRepository,
			$transactionManager,
			$transactionBuilder,
			$paymentProcessor,
			$orderRepository,
            $resource,
            $resourceCollection,
            $data
        );
    }
  

     /**
     * Sets order state to 'processing' with appropriate message
     *
     * @param \Magento\Framework\Phrase|string $message
     */
    protected function setOrderStateProcessing($message)
    {
		$orderPayment = $this->getOrder()->getPayment();
		$paymentMethod = $orderPayment->getMethod();
		if($paymentMethod != 'cybersource'){
			$this->getOrder()->setState(Order::STATE_PROCESSING)
				->setStatus($this->getOrder()->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
				->addStatusHistoryComment($message);
		}		
    }
}

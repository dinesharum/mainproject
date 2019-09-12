<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Alshaya\Cybersource\Block;


use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Checkout\Model\Session;
use Magento\Payment\Model\Config;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Source;
/**
 * Transparent form block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Session
     */
    private $checkoutSession;
	
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;


    protected $paymentConfig;

    protected $cyberhelper;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;
    /**
     * @var string
     */
    protected $_template = 'Alshaya_Cybersource::transparent/form.phtml';

    /**
     * @param Context $context
     * @param Config $paymentConfig
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Config $paymentConfig,
        Session $checkoutSession,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
		\Alshaya\Cybersource\Helper\Data $cyberhelper,
        RequestInterface $request,
        Repository $assetRepo,
        Source $assetSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_paymentHelper = $paymentHelper;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->paymentConfig = $paymentConfig;
        $this->cyberhelper = $cyberhelper;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->localeResolver = $localeResolver;
        $this->assetSource = $assetSource;
    }

    /**
     * {inheritdoc}
     */
    protected function _toHtml222()
    {
        if ($this->shouldRender()) {
            return $this->processHtml();
        }

        return '';
    }

    /**
     * Checks whether block should be rendered
     * basing on TransparentInterface presence in checkout session
     *
     * @return bool
     */
    protected function shouldRender()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote) {
            $payment = $quote->getPayment();
            return $payment && $payment->getMethodInstance() instanceof TransparentInterface;
        }

        return false;
    }

    /**
     * Initializes method
     *
     * @return void
     */
    protected function initializeMethod()
    {
        $this->setData(
            'method',
            $this->checkoutSession
                ->getQuote()
                ->getPayment()
                ->getMethodInstance()
        );
    }

    /**
     * Parent rendering wrapper
     *
     * @return string
     */
    protected function processHtml()
    {
        $this->initializeMethod();
        return parent::_toHtml();
    }

    /**
     * Get type of request
     *
     * @return bool
     */
    public function isAjaxRequest()
    {
        return $this->getRequest()->getParam('isAjax');
    }

    /**
     * Get delimiter for date
     *
     * @return string
     */
    public function getDateDelim()
    {
        return $this->getMethodConfigData('date_delim');
    }

    /**
     * Get map of cc_code, cc_num, cc_expdate for gateway
     * Returns json formatted string
     *
     * @return string
     */
    public function getCardFieldsMap()
    {
        $keys = ['cccvv', 'ccexpdate', 'ccnum'];
        $ccfields = array_combine($keys, explode(',', $this->getMethodConfigData('ccfields')));
        return json_encode($ccfields);
    }

    /**
     * Retrieve place order url on front
     *
     * @return string
     */
    public function getOrderUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getMethodConfigData('place_order_url'),
            [
                '_secure' => $this->getRequest()->isSecure()
            ]
        );
    }
	
	  /**
     * Set quote and payment
     * 
     * @return $this
     */
    public function setMethodInfo()
    {
        $payment = $this->onepage
            ->getQuote()
            ->getPayment();
        $this->setMethod($payment->getMethodInstance());

        return $this;
    }

    /**
     * Retrieve gateway url
     *
     * @return string
     */
    public function getCode()
    {
        return 'cybersource';
    }
    public function getCgiUrl()
    {
        return (bool)$this->getMethodConfigData('sandbox_flag')
            ? $this->getMethodConfigData('cgi_url_test_mode')
            : $this->getMethodConfigData('cgi_url');
    }

    /**
     * Retrieve config data value by field name
     *
     * @param string $fieldName
     * @return mixed
     */
    public function getMethodConfigData($fieldName)
    {
         return $this->cyberhelper->getConfig($fieldName);
		 /* $method = $this->getMethod();
        if ($method instanceof TransparentInterface) {
            return $method->getConfigInterface()->getValue($fieldName);
        }
        return $method->getConfigData($fieldName); */
    }

    /**
     * Returns transparent method service
     *
     * @return TransparentInterface
     * @throws LocalizedException
     */
	 
	 
    public function getMethodCode()
    {
		return $this->getCode();
	}
    public function getMethod()
    {
		$code= $this->getCode();
        $method= $this->_paymentHelper->getMethodInstance($code);
        return $method;
    }
	public function getInfoData($field)
    {
       return $this->escapeHtml($this->cyberhelper->getConfig($field));
    }
	public function getCcAvailableTypes()
    {
		 $types = $this->paymentConfig->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
		
		
    }
	
	
	public function getIcons()
    {
        $icons = [];
        $types = $this->getCcAvailableTypes();
        foreach (array_keys($types) as $code) {
            if (!array_key_exists($code, $icons)) {
                $asset = $this->createAsset('Magento_Payment::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findRelativeSourceFilePath($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height
                    ];
                }
            }
        }
        return $icons;
    }
	
	public function createAsset($fileId, array $params = [])
    {
        $params = array_merge(['_secure' => $this->request->isSecure()], $params);
        return $this->assetRepo->createAsset($fileId, $params);
    }
	
	public function getCcMonths()
    {
		 $data = [];
        $months = (new \Magento\Framework\Locale\Bundle\DataBundle())->get(
            $this->localeResolver->getLocale()
        )['calendar']['gregorian']['monthNames']['format']['wide'];
        foreach ($months as $key => $value) {
            $monthNum = ++$key < 10 ? '0' . $key : $key;
            $data[$monthNum] = $monthNum . ' - ' . $value;
        }
        return $data;
	}
	public function getCcYears()
    {
		return $this->paymentConfig->getYears();
	}
	public function hasVerification()
    {
		return true;
	}
	
	
}

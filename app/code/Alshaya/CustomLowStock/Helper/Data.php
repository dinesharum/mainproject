<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (http://www.amasty.com)
 * @package Amasty_HelloWorld
 */
namespace Alshaya\CustomLowStock\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterfac
     */
    protected $_scopeConfig;

    CONST HOST      = 'alshaya_customlowstock/general/mail_host';
    CONST PORT = 'alshaya_customlowstock/general/mail_port';
    CONST EMAIL  = 'alshaya_customlowstock/general/mail_username';
    CONST PASSWORD  = 'alshaya_customlowstock/general/mail_password';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);

        $this->_scopeConfig = $context->getScopeConfig();
    }

    public function getMailHost(){
        return $this->_scopeConfig->getValue(self::HOST);
    }

    public function getMailPort(){
        return $this->_scopeConfig->getValue(self::PORT);
    }

    public function getEmailAddress(){
        return $this->_scopeConfig->getValue(self::EMAIL);
    }

    public function getMailPassword(){
        return $this->_scopeConfig->getValue(self::PASSWORD);
    }
}


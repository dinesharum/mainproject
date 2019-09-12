<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Alshaya\Cybersource\Model;


class AuthorizeStrategyCommand extends \Magento\Cybersource\Gateway\Command\AuthorizeStrategyCommand
{
    
    public function execute(array $commandSubject)
    {
		return true;
    }
}

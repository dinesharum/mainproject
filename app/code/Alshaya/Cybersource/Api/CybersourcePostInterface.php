<?php

/**
 * Copyright 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Alshaya\Cybersource\Api;


/**
 * Defines the service contract for some simple maths functions. The purpose is
 * to demonstrate the definition of a simple web service, not that these
 * functions are really useful in practice. The function prototypes were therefore
 * selected to demonstrate different parameter and return values, not as a good
 * calculator design.
 */
interface CybersourcePostInterface
{
	/**
     * Set payment information and place order for a specified cart.
     *
     * @api
     * @param \Alshaya\Cybersource\Api\Data\CybersourceInterface $data
     * @return string 
     */
    public function createOrder(\Alshaya\Cybersource\Api\Data\CybersourceInterface $data);
   
}
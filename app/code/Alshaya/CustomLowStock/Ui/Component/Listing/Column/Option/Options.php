<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Alshaya\CustomLowStock\Ui\Component\Listing\Column\Option;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManager;
use Alshaya\CustomLowStock\Model\ItemFactory;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;
    protected $_storeManager;
    protected $logger;
    protected $_modelItemFactory;
   

    /**
     * @description initializing the construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory
     * @param \Magento\Catalog\Model\ProductFactory $modelProductFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory,
        \Magento\Store\Model\StoreManager $storeManager
    ) 
    {
       $this->_logger = $logger;
       $this->_modelItemFactory = $modelItemFactory;
       $this->_storeManager = $storeManager;
    }
    
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];
			
			$websites = $this->_storeManager->getWebsites();
            foreach ($websites as $website) 
            {
                     $this->options[] = [
                        'value' => $website->getName(),
                        'label' => $website->getName()
                    ];
                   
            }
        }

        return $this->options;
    }
}

<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Alshaya\Phpexcel\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Alshaya\Phpexcel\Model\Export\ConvertToXlsx;
use Magento\Framework\App\Response\Http\FileFactory;


/**
 * Class Render
 */
class GridToXlsx extends Action
{
    /**
     * @var ConvertToXml
     */
    protected $converter;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var logger
     */
     protected $_logger;

	/**
     * @var response
     */
    protected $_response;
	 
	 /**
     * Module registry
     *
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;


    /**
     * @param Context $context
     * @param ConvertToXml $converter
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        ComponentRegistrarInterface $componentRegistrar,
        ConvertToXlsx $converter,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->converter = $converter;
		$this->componentRegistrar = $componentRegistrar;
        $this->fileFactory = $fileFactory;
    }
    
    
    /**
     * Determine if authorized to perform group actions.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Alshaya_Phpexcel::gridToXlsx');
    }
	
    /**
     * Export data provider to XML
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\App\ResponseInterface
     */

     public function execute()
    {

        return $this->fileFactory->create('export.xlsx', $this->converter->getXlsxFile(), 'var');
    }
  
}

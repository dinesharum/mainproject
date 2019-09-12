<?php
/**
 * Index.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\CustomLowStock\Controller\Adminhtml\Item;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Item
 * @description  Defining the block Item class for Admin Grid Container
 */
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Alshaya_CustomLowStock::item';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Determine if authorized to perform group actions.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
    
    /**
     * Index action
     * @description default execute method
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Alshaya_CustomLowStock::item');
        $resultPage->addBreadcrumb(__('Alshaya Low Stock Items'), __('Report'));
        $resultPage->getConfig()->getTitle()->prepend(__('Low Stock Items Report'));

        return $resultPage;
    }
}

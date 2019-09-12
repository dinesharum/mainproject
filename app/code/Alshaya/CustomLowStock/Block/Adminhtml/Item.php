<?php
/**
 * Item.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\CustomLowStock\Block\Adminhtml;

/**
 * Class Item
 * @description  Defining the block Item class for Admin Grid Container
 */
class Item extends \Magento\Backend\Block\Widget\Grid\Container
{

	/**
     * @description default Construct
     *
     * @param null
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_item';
        $this->_blockGroup = 'Alshaya_CustomLowStock';
        $this->_headerText = __('Alshaya Low Stock Reports');
        parent::_construct();
    }

	/**
     * @description checks for access permission from acl
     *
     * @param $resourceId
	 * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}

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
namespace Alshaya\CustomLowStock\Model\ResourceModel;

/**
 * Class Item
 * @description  Defining the Item class for Resource Model
 */
class Item extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('alshaya_custom_lowstock', 'lowstock_id');
    }
}

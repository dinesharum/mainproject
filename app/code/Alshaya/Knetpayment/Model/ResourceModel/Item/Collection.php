<?php
/**
 * Collection.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\Knetpayment\Model\ResourceModel\Item;

/**
 * Class Collection
 * @description  Defining the collection class for the Resource Model DB
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Alshaya\Knetpayment\Model\Item', 'Alshaya\Knetpayment\Model\ResourceModel\Item');
    }
}

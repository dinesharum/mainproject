<?php
/**
 * Pendingpayment.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Knetpayment\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

/**
 * Class ResourcePathOption
 * @description  order status for the source model
 */
class Pendingpayment extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT];
}

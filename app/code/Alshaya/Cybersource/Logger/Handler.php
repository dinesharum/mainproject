<?php
/**
 * Handler.php
 *
 * @package    Alshaya
 * @module     Cybersource
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Cybersource\Logger;

//use Monolog\Logger;
/**
 * Class Handler
 * @description  Handler class to used for logging the details of every transactions of Knet payment gateway
 * 				 
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
 
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/cybersource/cybersource.log';
   
}
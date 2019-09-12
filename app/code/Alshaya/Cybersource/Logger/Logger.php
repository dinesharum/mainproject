<?php
/**
 * Logger.php
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


use Magento\Framework;

/**
 * Class Logger
 * @description  Logger class to used for logging the details of every transactions of Knet payment gateway
 * 				 
 */
class Logger extends \Monolog\Logger
{

	 /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info($message, array $context = array())
    {    	
    	if(isset($context['logger'])){ $config = $context['logger']; } else { $config = 0; }    	
    	if($config)
    	{
        	return $this->addRecord(static::INFO, $message, $context);
        }
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $context = array())
    {
        if(isset($context['logger'])){ $config = $context['logger']; } else { $config = 0; }
        if($config)
        {
            return $this->addRecord(static::DEBUG, $message, $context);
        }
    }
}
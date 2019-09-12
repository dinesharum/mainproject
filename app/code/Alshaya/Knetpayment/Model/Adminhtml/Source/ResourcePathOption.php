<?php
/**
 * ResourcePathOption.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\Knetpayment\Model\Adminhtml\Source;

/**
 * Class ResourcePathOption
 * @description  providing the resource files options for knet payment gateway system configurations
 */
class ResourcePathOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '/resource/live/',
                'label' => __('Live')
            ],

             [
                'value' => '/resource/test/',
                'label' => __('Test')
            ]
        ];
    }
}

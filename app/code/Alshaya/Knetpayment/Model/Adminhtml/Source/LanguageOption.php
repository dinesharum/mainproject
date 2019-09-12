<?php
/**
 * LanguageOption.php
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
 * Class LanguageOption
 * @description  providing the language options for knet payment gateway system configurations
 */
class LanguageOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'ENG',
                'label' => __('English')
            ],

             [
                'value' => 'ARA',
                'label' => __('Arabic')
            ]
        ];
    }
}

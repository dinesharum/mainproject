<?php
/**
 * InstallSchema.php
 *
 * @package    Alshaya
 * @module     Knetpayment
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\Knetpayment\Setup;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @description  create the required main table through script
 */
class InstallSchema implements InstallSchemaInterface
{
	/**
     * @description initializing the construct
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
	 */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $table  = $installer->getConnection()
            ->newTable($installer->getTable('knet_payment_details'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )
            ->addColumn(
                'knet_resp_payment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'knet response payment id'
            )
            ->addColumn(
                'knet_resp_payment_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'knet response payment status'
            )
            ->addColumn(
                'knet_resp_auth',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'knet response payment auth code'
            )
            ->addColumn(
                'knet_resp_ref',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'knet response payment reference no.'
            )
            ->addColumn(
                'knet_resp_postdate',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'knet response payment post date'
            )
            ->addColumn(
                'knet_resp_trans_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'knet response payment transaction id'
            )
            ->addColumn(
                'knet_resp_track_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'knet response payment transaction id'
            )
            ->addColumn(
                'knet_error',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'knet error msg code'
            )
            ->addColumn(
                'knet_resp_full_data',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'knet resp full data'
            )
            ->addColumn(
                'timestamp',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false],
                'Date Time'
            );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}

<?php
/**
 * InstallSchema.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */ 
namespace Alshaya\CustomLowStock\Setup;

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
            ->newTable($installer->getTable('alshaya_custom_lowstock'))
            ->addColumn(
                'lowstock_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )
            ->addColumn(
                'sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'SKU'
            )
            ->addColumn(
                'style_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'Style Code'
            )
            ->addColumn(
                'product_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'Product Name'
            )
            ->addColumn(
                'size',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'Size'
            )
            ->addColumn(
                'timestamp',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false],
                'Date Time'
            )
            ->addColumn(
                'threshold',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [ 'nullable' => false, 'unsigned' => false],
                'Threshold'
            )->addColumn(
                'country',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'Country'
            );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}

<?php
/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$table = $installer->getTable('zero1_seo_redirects/redirection');
/** @var Magento_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();
$connection->addColumn($table,
    'updated_at', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable' => false,
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE,
        'comment' => 'Last Updated Timestamp',
    )
);

/**
 * Create table 'zero1_seoredirects/redirection_import_status'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('zero1_seo_redirects/importStatus'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
    ), 'id')
	->addColumn('scope', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable'  => false,
	), 'scope')
    ->addColumn('scope_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
    ), 'scope id')
	->addColumn('to_be_imported', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
	), 'total number to be imported')
	->addColumn('imported', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
	), 'total number imported')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		'default'  => 0,
	), 'started at')
	->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		'default'  => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE,
	), 'updated at');
$installer->getConnection()->createTable($table);

/**
 * Create table 'zero1_seoredirects/redirection_import_log'
 */
$table = $installer->getConnection()
	->newTable($installer->getTable('zero1_seo_redirects/importLog'))
	->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
	), 'id')
	->addColumn('scope', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable'  => false,
	), 'scope')
	->addColumn('scope_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
	), 'scope id')
	->addColumn('severity', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
	), 'severity')
	->addColumn('line_number', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => true,
	), 'line number')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'log message')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		'default'  => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
	), 'started at');
$installer->getConnection()->createTable($table);

$installer->endSetup();

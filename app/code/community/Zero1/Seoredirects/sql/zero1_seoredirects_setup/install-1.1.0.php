<?php
$installer = $this;
$installer->startSetup();

// Clean up old table
$installer->run("DROP TABLE IF EXISTS {$installer->getTable('zero1_seo_redirects/redirection')};");

/**
 * Create table 'zero1_seoredirects/redirection'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('zero1_seo_redirects/redirection'))
    ->addColumn('redirection_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Redirection Id')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'default'   => '0',
    ), 'Store Id')

    ->addColumn('from_url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'From URL')

    ->addColumn('to_url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'To URL')

    ->addIndex($installer->getIdxName('zero1_seo_redirects/redirection', array('redirection_id')),
        array('redirection_id'))
    ->addIndex($installer->getIdxName('zero1_seo_redirects/redirection', array('store_id')),
        array('store_id'))
    ->addForeignKey($installer->getFkName('zero1_seo_redirects/redirection', 'store_id', 'core/store', 'store_id'),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Zero1 Seoredirects Redirection');

$installer->getConnection()->createTable($table);

$installer->endSetup();

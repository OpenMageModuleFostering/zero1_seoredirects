<?php
/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$table = $installer->getTable('zero1_seo_redirects/redirection');
$installer->getConnection()->addColumn($table,
    'from_type', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
        'comment' => 'The status of this redirection'
    )
);
$installer->getConnection()->addColumn($table,
    'persist_query', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
        'comment' => 'Flag for persisting query through to to_url'
    )
);
$installer->getConnection()->addColumn($table,
    'source', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
        'comment' => 'Source of redirect, import, logged or manual'
    )
);
$installer->getConnection()->addColumn($table,
    'hits', array(
        'type' => Varien_Db_Ddl_Table::TYPE_BIGINT,
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
        'comment' => 'The number of times this redirect has been used'
    )
);
$installer->getConnection()->addColumn($table,
    'status', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
        'comment' => 'The status of this redirection'
    )
);
$installer->getConnection()->dropColumn($table, 'from_url');
$installer->getConnection()->addColumn($table,
    'from_url_path', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => false,
        'comment' => 'From URL path'
    )
);
$installer->getConnection()->addColumn($table,
    'from_url_query', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => false,
        'comment' => 'From URL query',
        'default' => '',
    )
);

/**
 * Create table 'zero1_seoredirects/redirection_cache'
 */
$installer->run("DROP TABLE IF EXISTS {$installer->getTable('zero1_seo_redirects/redirection_cache')};");
$table = $installer->getConnection()
    ->newTable($installer->getTable('zero1_seo_redirects/redirection_cache'))
    ->addColumn('redirection_cache_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Redirection Cache Id')
    ->addColumn('redirection_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
    ), 'Related redirection id')
    ->addColumn('from_url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'From URL')
    ->addColumn('to_url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
    ), 'To URL')

    ->addIndex($installer->getIdxName('zero1_seo_redirects/redirection_cache', array('redirection_cache_id')),
        array('redirection_id'))
    ->addForeignKey(
        $installer->getFkName('zero1_seo_redirects/redirection_cache', 'redirection_id', 'zero1_seo_redirects/redirection', 'redirection_id'),
        'redirection_id',
        $installer->getTable('zero1_seo_redirects/redirection'),
        'redirection_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_NO_ACTION)
    ->setComment('Zero1 Redirection Cache');

$installer->getConnection()->createTable($table);
$installer->endSetup();

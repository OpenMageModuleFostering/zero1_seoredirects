<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml customer grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Zero1_Seoredirects_Block_Adminhtml_Manage_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('manageGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //$this->setVarNameFilter('product_filter');
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        /* @var $collection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
        $store = $this->_getStore();
        $collection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection();
        $collection->addExpressionFieldToSelect(
            'from_url',
            'CONCAT({{from_url_path}}, IF({{from_url_query}}=\'\', \'\', \'?\'), {{from_url_query}})',
            array('from_url_path'=>'from_url_path', 'from_url_query'=>'from_url_query')
        );

        if ($store->getId()) {
            $collection->addFieldToFilter('store_id', $store->getId());
        }

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('redirection_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'redirection_id',
                'is_system' => true,
        ));

        $this->addColumn('from_url', array(
            'header'    => Mage::helper('catalog')->__('From'),
            'header_export' => 'from-url',
            'index'     => 'from_url',
            'filter_index' => 'CONCAT(from_url_path, IF(from_url_query=\'\', \'\', \'?\'), from_url_query)',
            'renderer' => 'zero1_seo_redirects/adminhtml_manage_grid_renderer_url',
        ));

        $this->addColumn('to_url', array(
            'header'    => Mage::helper('catalog')->__('To'),
            'header_export' => 'to-url',
            'index'     => 'to_url',
            'renderer' => 'zero1_seo_redirects/adminhtml_manage_grid_renderer_url',
        ));

        $this->addColumn('from_type',
            array(
                'header'=> Mage::helper('catalog')->__('From Type'),
                'header_export' => 'type',
                'width' => '125px',
                'index' => 'from_type',
                'type'  => 'options',
                'options' => Mage::helper('zero1_seo_redirects')->getFromTypes(),
                'renderer' => 'zero1_seo_redirects/adminhtml_manage_grid_renderer_options',

        ));

        $this->addColumn('persist_query',
            array(
                'header'=> Mage::helper('catalog')->__('Persist Query'),
                'header_export' => 'persist-query',
                'width' => '75px',
                'index' => 'persist_query',
                'type'  => 'options',
                'options' => array(0 => 'No', 1 => 'Yes'),
                'renderer' => 'zero1_seo_redirects/adminhtml_manage_grid_renderer_options',
            ));

        $this->addColumn('source',
            array(
                'header'=> Mage::helper('catalog')->__('Source'),
                'width' => '100px',
                'index' => 'source',
                'type'  => 'options',
                'options' => Mage::helper('zero1_seo_redirects')->getSources(),
                'is_system' => true,
            ));

        $this->addColumn('status',
            array(
                'header'=> Mage::helper('catalog')->__('Status'),
                'width' => '75px',
                'index' => 'status',
                'type'  => 'options',
                'options' => Mage::helper('zero1_seo_redirects')->getStatuses(),
                'is_system' => true,
            ));

        $this->addColumn('hits',
            array(
                'header'=> Mage::helper('catalog')->__('Hits'),
                'width' => '75px',
                'index' => 'hits',
                'type'  => 'number',
                'is_system' => true,
            ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Edit'),
                        'url'     => array(
                            'base'=>'*/*/edit',
                            'params'=>array('store'=>$this->getRequest()->getParam('store'))
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('redirection_id');
        $this->getMassactionBlock()->setFormFieldName('redirection_id'); //<<??

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> Mage::helper('catalog')->__('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => Mage::helper('catalog')->__('Are you sure?')
        ));

        Mage::dispatchEvent('adminhtml_catalog_product_grid_prepare_massaction', array('block' => $this));
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }

    /**
     * Write item data to csv export file
     *
     * @param Varien_Object $item
     * @param Varien_Io_File $adapter
     */
    protected function _exportCsvItem(Varien_Object $item, Varien_Io_File $adapter)
    {
        $row = array();
        /* @var $column Mage_Adminhtml_Block_Widget_Grid_Column:: */
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $row[] = $column->getRowFieldExport($item);
            }
        }
        $adapter->streamWriteCsv($row);
    }
}

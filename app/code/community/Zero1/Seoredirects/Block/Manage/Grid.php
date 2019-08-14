<?php
class Zero1_Seoredirects_Block_Manage_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('manageGrid');
        $this->setDefaultSort('entity_id');      
    }
    
	protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
    
    protected function _prepareCollection()
    {
    	$store = $this->_getStore();
    	
    	$collection = Mage::getModel('seoredirects/redirection')->getCollection();
    	
    	if($store->getId())
    	{
	        $collection->addFieldToFilter('store', $store->getId());
    	}
        
        $this->setCollection($collection);
        
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {        
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('catalog')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'entity_id'
        ));
        
        $this->addColumn('redirect_from', array(
            'header'    => Mage::helper('catalog')->__('From'),
            'index'     => 'redirect_from'
        ));
        
        $this->addColumn('redirect_to', array(
            'header'    => Mage::helper('catalog')->__('To'),
            'index'     => 'redirect_to'
        ));
        
        $this->addColumn('store', array(
        		'header'    => Mage::helper('newsletter')->__('Store'),
        		'index'     => 'store',
        		'type'      => 'options',
        		'options'   => Mage::getModel('adminhtml/system_store')->getStoreOptionHash()
        ));

        return parent::_prepareColumns();
    }
}
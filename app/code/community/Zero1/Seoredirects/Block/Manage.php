<?php
class Zero1_Seoredirects_Block_Manage extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	protected $_controller = 'seoredirects';
	
    public function __construct()
    {
    	$this->_controller = 'manage';
    	$this->_blockGroup = 'seoredirects';
    	
        $this->_headerText = Mage::helper('seoredirects')->__('SEO Redirection Management');
        $this->_addButton('refresh', array(
        		'label'   => $this->__('Refresh URLs'),
        		'onclick' => "setLocation('{$this->getUrl('*/*/update')}')",
        		'class'   => 'refresh'
        		));
        
        parent::__construct();        
        $this->_removeButton('add');
    }
}
<?php
class Zero1_Seoredirects_Block_Adminhtml_Import extends Mage_Adminhtml_Block_Widget_Container
{
    public function __construct()
    {
        //$this->_controller  = 'system_store';
        $this->_headerText  = Mage::helper('adminhtml')->__('Import Redirects');
        $this->setTemplate('zero1/seoredirects/import/container.phtml');
        parent::__construct();
    }

    protected function _prepareLayout()
    {
        if(Zero1_Seoredirects_Model_Importer::isRunning()){
            $this->_addButton('refresh_data', array(
                'label'     => Mage::helper('core')->__('Refresh Data'),
                'onclick'   => 'window.location =\''.$this->getUrl('*/*/*').'\';',
                'class'     => 'add',
            ));
        }else{
            $this->_addButton('run', array(
                'label'     => Mage::helper('core')->__('Run Import'),
                'onclick'   =>
                    'new Ajax.Request(\''.$this->getUrl('*/*/run').'\', {
                	asynchronous: false,
                	method: \'get\'
                }); window.location =\''.$this->getUrl('*/*/*').'\';',
                'class'     => 'add',
            ));
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getLayout()->createBlock('zero1_seo_redirects/adminhtml_import_grid')->toHtml();
    }
}

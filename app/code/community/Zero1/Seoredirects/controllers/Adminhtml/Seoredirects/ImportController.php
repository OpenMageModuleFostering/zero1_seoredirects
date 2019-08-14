<?php
class Zero1_Seoredirects_Adminhtml_Seoredirects_ImportController extends Mage_Adminhtml_Controller_Action
{	
	public function indexAction()
	{
        $this->_title($this->__('SEO Redirects'))
        	 ->_title($this->__('Import Redirects'));
		$this->loadLayout();
        $this->_setActiveMenu('catalog/seoredirects');
		$this->renderLayout();
	}

    public function runAction(){
        /* @var $import Zero1_Seoredirects_Model_Importer */
        $import = Mage::getModel('zero1_seo_redirects/importer');
        $content = '';

        try{
            $log = $import->import();
            /* @var $b Zero1_Seoredirects_Block_Adminhtml_Manage_Report */
            $b = Mage::app()->getLayout()->createBlock('zero1_seo_redirects/adminhtml_manage_report');
            $content .= $b->toHtml();

            /* @var $b Zero1_Seoredirects_Block_Adminhtml_Import_Report */
            $b = Mage::app()->getLayout()->createBlock('zero1_seo_redirects/adminhtml_import_report');
            $b->setLogData($log);
            $content .= $b->toHtml();
        }catch(Exception $e){
            $content .= $e;
        }

        echo $content;

    }
}

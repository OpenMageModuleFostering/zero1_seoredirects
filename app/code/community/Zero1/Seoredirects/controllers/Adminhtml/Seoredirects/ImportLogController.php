<?php
class Zero1_Seoredirects_Adminhtml_Seoredirects_ImportLogController extends Mage_Adminhtml_Controller_Action
{	
	public function indexAction()
	{
        $this->_title($this->__('SEO Redirects'))
        	 ->_title($this->__('Import Logs'));

		$this->loadLayout();
        $this->_setActiveMenu('catalog/seoredirects');
		$this->renderLayout();
	}

    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
}

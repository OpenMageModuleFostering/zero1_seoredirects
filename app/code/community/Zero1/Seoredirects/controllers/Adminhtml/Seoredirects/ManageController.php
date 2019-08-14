<?php
class Zero1_Seoredirects_Adminhtml_Seoredirects_ManageController extends Mage_Adminhtml_Controller_Action
{	
	public function indexAction()
	{		
        $this->_title($this->__('SEO Redirects'))
        	 ->_title($this->__('Manage Redirects'));
        
		$this->loadLayout();
        $this->_setActiveMenu('catalog/seoredirects');
		$this->renderLayout();
	}
	
	public function updateAction()
	{
		Mage::getModel('seoredirects/observer')->updateRedirectionsCronJob();
		
		$results = Mage::registry('seoredirects_results');		
		$stores = Mage::getResourceModel('core/store_collection');
		$pad = '&nbsp;&nbsp;&nbsp;&nbsp;';
		
		foreach($stores as $store)
		{
			if(($results[$store->getId()]['updated'] + 
				$results[$store->getId()]['added'] + 
				$results[$store->getId()]['deleted'] + 
				$results[$store->getId()]['loops']) == 0)
			{
				continue;
			}
			
			$this->_getSession()->addSuccess(
					Mage::helper('seoredirects')->__($store->getName())
				);
			
			if($results[$store->getId()]['updated'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('seoredirects')->__($pad.'Updated '.$results[$store->getId()]['updated'].' redirection(s).')
				);
			
			if($results[$store->getId()]['added'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('seoredirects')->__($pad.'Added '.$results[$store->getId()]['added'].' redirection(s).')
				);
			
			if($results[$store->getId()]['deleted'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('seoredirects')->__($pad.'Deleted '.$results[$store->getId()]['deleted'].' redirection(s).')
				);
			
			if($results[$store->getId()]['loops'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('seoredirects')->__($pad.'Deleted '.$results[$store->getId()]['loops'].' looping redirection(s).')
				);
			
			if(!empty($results[$store->getId()]['limitation']))
				$this->_getSession()->addSuccess(
						Mage::helper('seoredirects')->__($pad.$results[$store->getId()]['limitation'])
				);
		}
		
		$this->_redirect('*/*/index');
	}
}

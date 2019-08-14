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

    public function newAction(){
        $this->_forward('edit');
    }

    public function editAction(){
        $this->_title($this->__('Catalog'))->_title($this->__('Seo Redirects'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('zero1_seo_redirects/redirection');

        if($id){
            $model->load($id);
            if(!$model->getId()){
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('zero1_seo_redirects')->__('Redirect no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_title($model->getId() ? $this->__('Edit Seo Redirection') : $this->__('Create New Seo Redirection'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if(!empty($data)){
            $model->setData($data);
        }

        Mage::register('zero1_seo_redirect', $model);
        $this->loadLayout()->renderLayout();
    }

    public function saveAction(){
        if($data = $this->getRequest()->getParams()){
			/* @var $model Zero1_Seoredirects_Model_Redirection */
            $model = Mage::getModel('zero1_seo_redirects/redirection');
            //if editing load model first
            if(isset($data['redirection_id'])){
                $model->load($data['redirection_id']);
            }
            //add changes from form
            $model->setData($data);
            try{
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('zero1_seo_redirects')->__('Redirection has been saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                $this->_redirect('*/*/');
                return;
            }catch(Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                Mage::register('zero1_seo_redirect', $model);
                $this->_redirect('*/*/edit');
                return;
            }
        }
    }

    public function deleteAction(){
        if(!$id = $this->getRequest()->getParam('id')){
            //complete fail no ids available
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('zero1_seo_redirects')->__('Unable to find redirect.')
            );
            $this->_redirect('*/*/');
            return;
        }
        try{
            $model = Mage::getModel('zero1_seo_redirects/redirection');
            $model->load($id);
            $model->delete();

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('zero1_seo_redirects')->__('The redirect has been deleted')
            );
            $this->_redirect('*/*/');
            return;
        }catch(Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/edit');
            return;
        }
    }

    public function massDeleteAction(){
        if(!$ids = $this->getRequest()->getParam('redirection_id')){
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('zero1_seo_redirecs')->__('Unable to process request.')
            );
        }
        $model = Mage::getModel('zero1_seo_redirects/redirection');
        $result = array(
            'success' => 0,
            'fails' => array(),
        );
        foreach($ids as $id){
            try{
                $model->load($id);
                $model->delete();
                $result['success']++;
            }catch(Exception $e){
                $result['fails'][] = '[id:'.$id.'] '.$e->getMessage();
            }
        }
        if(count($result['success']) > 0){
            Mage::getSingleton('adminhtml/session')->addSuccess(
                count($result['success']).Mage::helper('zero1_seo_redirects')->__(' redirect(s) successfully deleted.')
            );
        }
        if(count($result['fails']) > 0){
            $errorString = count($result['fails']) . Mage::helper('zero1_seo_redirects')->__(' redirect(s) failed to be deleted.<br />');
            foreach($result['fails'] as $aFail){
                $errorString .= $aFail . '<br />';
            }
            Mage::getSingleton('adminhtml/session')->addError($errorString);
        }
        $this->_redirect('*/*/');
        return;
    }

    public function gridAction(){
        $this->loadLayout(false);
        $this->renderLayout();
    }
	
	public function updateAction()
	{
		Mage::getModel('zero1_seo_redirects/observer')->updateRedirectionsCronJob();
		
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
					Mage::helper('zero1_seo_redirects')->__($store->getName())
				);
			
			if($results[$store->getId()]['updated'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('zero1_seo_redirects')->__($pad.'Updated '.$results[$store->getId()]['updated'].' redirection(s).')
				);
			
			if($results[$store->getId()]['added'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('zero1_seo_redirects')->__($pad.'Added '.$results[$store->getId()]['added'].' redirection(s).')
				);
			
			if($results[$store->getId()]['deleted'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('zero1_seo_redirects')->__($pad.'Deleted '.$results[$store->getId()]['deleted'].' redirection(s).')
				);
			
			if($results[$store->getId()]['loops'] > 0)
				$this->_getSession()->addSuccess(
						Mage::helper('zero1_seo_redirects')->__($pad.'Deleted '.$results[$store->getId()]['loops'].' looping redirection(s).')
				);
			
			if(!empty($results[$store->getId()]['limitation']))
				$this->_getSession()->addSuccess(
						Mage::helper('zero1_seo_redirects')->__($pad.$results[$store->getId()]['limitation'])
				);
		}
		
		$this->_redirect('*/*/index');
	}

    /**
     * Export grid to CSV format
     */
    public function exportCsvAction()
    {
        /* @var $grid Zero1_Seoredirects_Block_Adminhtml_Manage_Grid */
        $fileName   = 'zero1_seo_redirects.csv';
        $grid       = $this->getLayout()->createBlock('zero1_seo_redirects/adminhtml_manage_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
}

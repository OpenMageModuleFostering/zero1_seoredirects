<?php
class Zero1_Seoredirects_Adminhtml_Seoredirects_ImportController extends Mage_Adminhtml_Controller_Action
{	
	public function indexAction()
	{
//		if(Zero1_Seoredirects_Model_Importer::isRunning()){
//			return $this->_redirect('*/*/status');
//		}
        $this->_title($this->__('SEO Redirects'))
        	 ->_title($this->__('Import Redirects'));
		$this->loadLayout();
        $this->_setActiveMenu('catalog/seoredirects');
		$this->renderLayout();
	}

	public function runAction()
	{
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'adam.log', true);
        $pathToScript = Mage::getModuleDir('', 'Zero1_Seoredirects').DS.'scripts'.DS.'import.php';
        $phpLocation = Mage::helper('zero1_seo_redirects')->getPhpLocation();
        $logLocation = Mage::getBaseDir('log').DS.'seo.log';
        $cmd = sprintf('%s %s 1>> %s 2>&1 &', $phpLocation, $pathToScript, $logLocation);
        //Mage::log('cmd: '.$cmd, Zend_Log::DEBUG, 'adam.log', true);
        shell_exec($cmd);
        return;
	}

    public function unlockAction()
    {
        /** @var Mage_Core_Model_Config_Data $config */
        $config = Mage::getModel('core/config_data')->load(Zero1_Seoredirects_Model_Importer::CONFIG_PATH_RUNLOCK, 'path');
        if(!$config->getId()){
            $config->setScope('default')
                ->setScopeId(0)
                ->setPath(Zero1_Seoredirects_Model_Importer::CONFIG_PATH_RUNLOCK);
        }
        $config->setValue(Zero1_Seoredirects_Model_Importer::RUNLOCK_NOT_RUNNING)->save();

        echo json_encode(array(
            'result' => 'OK',
        ));
        die;
    }

    public function runaAction(){
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

<?php
class Zero1_Seoredirects_Helper_Files extends Mage_Core_Helper_Abstract
{
    const CONFIG_FILE_PATH = 'seoredirects/settings/local_file';
    const CONFIG_SECTION = 'seoredirects';

    protected function getFilePathPrefix($getTemplatePath = false){
        $path = 'var'.DS.'Zero1'.DS.'SeoRedirects'.DS;
        if(!$getTemplatePath){
            $path .= 'data'.DS;
        }
        return $path;
    }

    public function getRelativeImportPath($store = null, $website = null){
        $path = $this->getFilePathPrefix();

        if($store != null){
            $store = Mage::app()->getStore($store);
            $path .= $store->getWebsite()->getCode().DS;
            $path .= $store->getCode().DS;
        }elseif($website != null){
            $path .= Mage::app()->getWebsite($website)->getCode().DS;
        }

        return $path;
    }

    public function getUrlPathToFile($path = null, $name= null){
		return Mage::helper('adminhtml')->getUrl('adminhtml/seoredirects_config/index', array('_query' => array('path'=>urlencode($path), 'name'=>urlencode($name))));
    }

    /**
     * @param int
     * @param int
     * @return Zero1_Seoredirects_Model_File
     */
    public function getFile($store = null, $website = null){
        /* @var $file Zero1_Seoredirects_Model_File */
        $file = Mage::getModel('zero1_seo_redirects/file');
        $fileName = Mage::helper('zero1_seo_redirects')->getFileName($store, $website);
        if(!$fileName){
            return $file;
        }
        $internalPath = Mage::getBaseDir('base').DS.$this->getRelativeImportPath($store, $website).$fileName;
		if(file_exists($internalPath)){
			$file->setFileName($fileName);
			$file->setExternalPath($this->getUrlPathToFile($internalPath, $fileName));
			$file->setInternalPath($internalPath);
		}else{
			//do this as someone has deleted the file
            //TODO move this into helper
			$this->_removeConfigOption($website, $store);
		}

        return $file;
    }

    public function getTemplateFile(){
        /* @var $file Zero1_Seoredirects_Model_File */
        $file = Mage::getModel('zero1_seo_redirects/file');
        $file->setFileName('template.csv');
		$internalPath = Mage::getBaseDir('base').DS.$this->getRelativeImportPath().$file->getFileName();
        $file->setExternalPath($this->getUrlPathToFile($internalPath, $file->getFileName()));
        $file->setInternalPath($internalPath);
        return $file;
    }

	protected function _removeConfigOption($website = null, $store = null){

		if ($store) {
			$scope   = 'stores';
			$scopeId = (int)Mage::getConfig()->getNode('stores/' . $store . '/system/store/id');
		} elseif ($website) {
			$scope   = 'websites';
			$scopeId = (int)Mage::getConfig()->getNode('websites/' . $website . '/system/website/id');
		} else {
			$scope   = 'default';
			$scopeId = 0;
		}

		/* @var $configData Mage_Core_Model_Resource_Config_Data_Collection */
		$configData = Mage::getModel('core/config_data')
			->getCollection()
			->addFieldToFilter('scope', $scope)
			->addFieldToFilter('scope_id', $scopeId)
			->addFieldToFilter('path', array('like' => self::CONFIG_FILE_PATH));

		if($configData->count() > 0){
			$configData->getFirstItem()->delete();
		}
		return;
	}
}
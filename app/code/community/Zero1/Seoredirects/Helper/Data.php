<?php
class Zero1_Seoredirects_Helper_Data extends Mage_Core_Helper_Abstract
{
    const INGNOREABLES_CONFIG_PATH = 'seoredirects/redirection_settings/params';
    const ENABLED_CONFIG_PATH = 'seoredirects/settings/enabled';
    const USE_LOCAL_FILE_CONFIG_PATH = 'seoredirects/settings/use_local_file';
    const LOCAL_FILE_NAME_CONFIG_PATH = 'seoredirects/settings/local_file';
    const GOOGLE_DOC_URL_CONFIG_PATH = 'seoredirects/settings/url';
    const USE_GOOGLE_DOC_CONFIG_PATH = 'seoredirects/settings/use_google_docs';
    const LOG_404S_CONFIG_PATH = 'seoredirects/advanced_settings/log_404s';
    const LOG_404S_LIMIT_CONFIG_PATH = 'seoredirects/advanced_settings/log_404s_limit';
    const ENABLED_DEBUG_CONFIG_PATH = 'seoredirects/debug_settings/enable_debug';
    const DEBUG_IPS_CONFIG_PATH = 'seoredirects/debug_settings/debug_ips';

	const CONFIG_PATH_IMPORT_LOG_LEVEL = 'seoredirects/advanced_settings/import_log_level';

    public function getStoreUrls(){
        $stores = array();
        $s = Mage::app()->getStores();
        foreach($s as $id => $store){
            /* @var $store Mage_Core_Model_Store */
            $stores[$store->getId()] = $store->getBaseUrl();
        }
        return $stores;
    }

    public function getFromTypes(){
        return array(
            Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_VALUE => Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_LABEL,
            Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE => Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_LABEL,
        );
    }

    public function getStatuses(){
        return array(
            Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_DISABLED_VALUE => Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_DISABLED_LABEL,
            Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_ENABLED_VALUE => Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_ENABLED_LABEL,
        );
    }

    public function getSources(){
        return array(
            Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE => Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_LABEL,
            Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_LOGGED_VALUE => Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_LOGGED_LABEL,
            Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_MANUAL_VALUE => Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_MANUAL_LABEL,
        );
    }

    public function getIsLog404Enabled($storeId = null){
        return (bool)Mage::getStoreConfig(self::LOG_404S_CONFIG_PATH, $storeId);
    }

    public function getIsDebugEnabled($storeId = null){
        return (bool)Mage::getStoreConfig(self::ENABLED_DEBUG_CONFIG_PATH, $storeId);
    }

    public function getDebugIps($storeId = null){
        $ips = Mage::getStoreConfig(self::DEBUG_IPS_CONFIG_PATH, $storeId);
        if(!$ips){
            $ips = array();
        }else{
            $ips = explode(',', $ips);
        }
        return $ips;
    }

    public function canDebug($store = null, $ip = null){
        if(!$this->getIsDebugEnabled($store)){
            return false;
        }
        if(!$ip){
            $ip = Mage::app()->getRequest()->getClientIp();
        }
        $debugIps = $this->getDebugIps($store);
        if(!empty($debugIps) && array_search($ip, $debugIps) === false){
            return false;
        }

        return true;
    }

    public function getLog404Limit($storeId = null){
        return (int)Mage::getStoreConfig(self::LOG_404S_LIMIT_CONFIG_PATH, $storeId);
    }

    public function stripIgnoreables($storeId = null, $args = array()){
        if(!$storeId){
            $storeId = Mage::app()->getStore()->getId();
        }

        $ignoreables = Mage::getStoreConfig(self::INGNOREABLES_CONFIG_PATH, $storeId);
        if($ignoreables === null){
            return $args;
        }

        $ignoreables = unserialize($ignoreables);
        $ignoreables = $ignoreables['params'];
        foreach($ignoreables as $param){
            if(isset($args[$param])){
                unset($args[$param]);
            }
        }
        return $args;
    }

    public function getIsEnabled($store = null, $website = null){
        if($store !== null){
            return Mage::app()->getStore($store)->getConfig(self::ENABLED_CONFIG_PATH);
        }
        return Mage::app()->getWebsite($website)->getConfig(self::ENABLED_CONFIG_PATH);
    }

    /**
     * @param int
     * @param int
     * @return bool
     */
    public function getHasLocalFile($store = null, $website =  null){

        //first check if enabled at this level
        if($website !== null){
            $localFile = (bool)Mage::app()->getWebsite($website)->getConfig(self::USE_LOCAL_FILE_CONFIG_PATH);
        }else{
            $localFile = (bool)Mage::app()->getStore($store)->getConfig(self::USE_LOCAL_FILE_CONFIG_PATH);
        }

        if(!$localFile){
            return false;
        }

        /* @var $fileHelper Zero1_Seoredirects_Helper_Files */
        $fileHelper = Mage::helper('zero1_seo_redirects/files');
        $file = $fileHelper->getFile($store, $website);

        if($file->getFileName() === null){
            return false;
        }else{
            return true;
        }
    }

    public function getFileName($store = null, $website = null){
        $params = array();
        if($store !== null){
            $params['scope'] = 'stores';
            $params['scope_id'] = $store;
        }elseif($website !== null){
            $params['scope'] = 'websites';
            $params['scope_id'] = $website;
        }else{
            $params['scope'] = 'default';
            $params['scope_id'] = 0;
        }
        $params['path'] = self::LOCAL_FILE_NAME_CONFIG_PATH;
        $config = $this->_getConfig($params);
        if(empty($config)){
            return false;
        }else{
            return $config[0]['value'];
        }
    }

    public function getIsGoogleDocEnabled($store = null, $website = null){
        if($website === null){
            $useGoogleDoc = (bool)Mage::app()->getStore($store)->getConfig(self::USE_GOOGLE_DOC_CONFIG_PATH);
        }else{
            $useGoogleDoc = (bool)Mage::app()->getWebsite($website)->getConfig(self::USE_GOOGLE_DOC_CONFIG_PATH);
        }
        return $useGoogleDoc;
    }

    public function getIsLocalFileEnabled($store = null, $website = null){
        if($website === null){
            $useGoogleDoc = (bool)Mage::app()->getStore($store)->getConfig(self::USE_LOCAL_FILE_CONFIG_PATH);
        }else{
            $useGoogleDoc = (bool)Mage::app()->getWebsite($website)->getConfig(self::USE_LOCAL_FILE_CONFIG_PATH);
        }
        return $useGoogleDoc;
    }

    public function getHasRemoteFile($store = null, $website = null){
        //first check if enabled at this level
        $useGoogleDoc = $this->getIsGoogleDocEnabled($store, $website);

        if(!$useGoogleDoc){
            return false;
        }

        $params = array();
        if($website !== null){
            $params['scope'] = 'websites';
            $params['scope_id'] = $website;
        }else{
            if($store == 0){
                $params['scope'] = 'default';
                $params['scope_id'] = 0;
            }else{
                $params['scope'] = 'stores';
                $params['scope_id'] = $store;
            }
        }
        $params['path'] = self::GOOGLE_DOC_URL_CONFIG_PATH;
        $config = $this->_getConfig($params);
        if(empty($config) || !isset($config[0]['value'])){
            return false;
        }else{
            return true;
        }
    }

    public function getRemoteFileUrl($store = null, $website = null){
        $url = null;
        if($website !== null){
            $url = Mage::app()->getWebsite($website)->getConfig(self::GOOGLE_DOC_URL_CONFIG_PATH);
        }else{
            $url = Mage::app()->getStore($store)->getConfig(self::GOOGLE_DOC_URL_CONFIG_PATH);
        }
        return $url;
    }

    protected function _getConfig($params){
        /* @var $configCollection Mage_Core_Model_Resource_Config */
        $configCollection = Mage::getModel('core_resource/config');
        $read = $configCollection->getReadConnection();

        /* @var $select Varien_Db_Select */
        $select = $read->select()
            ->from($configCollection->getMainTable())
            ->where('path = :path')
            ->where('scope = :scope')
            ->where('scope_id = :scope_id');

        return $read->fetchAll($select, $params);
    }

    public function debug($message){
        if(!$this->canDebug()){return;}
    }

	public function getImportLogLevel($store = null){
		return (int)Mage::getStoreConfig(self::CONFIG_PATH_IMPORT_LOG_LEVEL, $store);
	}
}
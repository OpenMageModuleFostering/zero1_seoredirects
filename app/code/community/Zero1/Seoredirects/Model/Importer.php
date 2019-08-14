<?php

/**
 * Class Zero1_Seoredirects_Model_Importer
 *
 * @method Mage_Core_Model_Store getStore()
 * @method setStore(Mage_Core_Model_Store $storeId)
 * @method Mage_Core_Model_Website getWebsite()
 * @method setWebsite(Mage_Core_Model_Website $websiteId)
 */
class Zero1_Seoredirects_Model_Importer extends Varien_Object
{
	const CONFIG_PATH_RUNLOCK = 'seoredirects/advanced_settings/import_status';
	const RUNLOCK_RUNNING = 'running';
	const RUNLOCK_NOT_RUNNING = 'not running';
    const RUNLOCK_ERRORED = 'error';

	protected $redirector = null;
	protected $localImporter = null;
	protected $googleImporter = null;
	protected $startTime = null;

	protected $scope = null;
	protected $scopeId = null;
	const SCOPE_DEFAULT = 'default';
	const SCOPE_STORE = 'store';
	const SCOPE_WEBSITE = 'website';
    const SCOPE_SYSTEM = 'system';
	const SCOPE_SUMMARY = 'summary';

    /**
     * @return Zero1_Seoredirects_Helper_Data
     */
    protected function getHelper()
	{
        return Mage::helper('zero1_seo_redirects');
    }

	/**
	 * @return Zero1_Seoredirects_Model_Importer_Local
	 */
	protected function getLocalImporter()
	{
		if(!$this->localImporter){
			$this->localImporter = Mage::getModel('zero1_seo_redirects/importer_local');
		}
		return $this->localImporter;
	}

	/**
	 * @return Zero1_Seoredirects_Model_Importer_Google
	 */
	protected function getGoogleImporter()
	{
		if(!$this->googleImporter){
			$this->googleImporter = Mage::getModel('zero1_seo_redirects/importer_google');
		}
		return $this->googleImporter;
	}

    public function import()
    {
        try{
            $this->run();
        }catch(Exception $e){
            $this->log($e->getMessage().PHP_EOL.$e->getTraceAsString(), null, Zend_Log::CRIT, self::SCOPE_SYSTEM, 0);
            /** @var Mage_Core_Model_Config_Data $config */
            $config = $this->getRunLockConfig();
            $config->setValue(self::RUNLOCK_ERRORED)->save();
            Mage::log($e->getMessage(), Zend_Log::DEBUG, 'seo.log', true);
            Mage::log($e->getTraceAsString(), Zend_Log::DEBUG, 'seo.log', true);
        }
    }

    protected function run()
	{
		if($this->isRunning()){
			//Mage::log('already running', Zend_Log::ALERT, 'seo.log', true);
			return;
		}
		//set running lock
		$this->start();
		//clear down log and status tables
		$this->cleanUp();

		/* @var $website Mage_Core_Model_Website */
		foreach(Mage::app()->getWebsites(false) as $websiteId => $website){
			$this->setWebsite($website);
			$this->scope = self::SCOPE_WEBSITE;
			$this->scopeId = $websiteId;
            $this->checkAndImportEnableds(null, $website->getId());
			/* @var $store Mage_Core_Model_Store */
			foreach($website->getStores(false) as $storeId  => $store){
				$this->setStore($store);
				$this->scope = self::SCOPE_STORE;
				$this->scopeId = $storeId;
                $this->checkAndImportEnableds($store->getId(), null);

				$this->setStore(null);
			}
			$this->setWebsite(null);
		}
        //do default to finish
		$this->scope = self::SCOPE_DEFAULT;
		$this->scopeId = 0;
        $this->checkAndImportEnableds();

        //delete now unused urls, and
        $this->removeOldImportedUrls();

		//refresh license count
		$this->updateStatuses();
		
		//remove running lock
		$this->end();
    }

	/**
	 * @return Mage_Core_Model_Config_Data
	 */
	protected function getRunLockConfig()
	{
		/** @var Mage_Core_Model_Config_Data $config */
		$config = Mage::getModel('core/config_data')->load(self::CONFIG_PATH_RUNLOCK, 'path');
		if(!$config->getId()){
			$config->setScope('default')
				->setScopeId(0)
				->setPath(self::CONFIG_PATH_RUNLOCK);
		}
		return $config;
	}

	protected function start()
	{
		//Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		$config = $this->getRunLockConfig();
		if($config->getValue() == self::RUNLOCK_RUNNING){
			throw new Exception('Importer Already Running');
		}
		$config->setValue(self::RUNLOCK_RUNNING)->save();
		$this->startTime = Mage::getSingleton('core/date')->timestamp(time());

	}

	protected function cleanUp()
	{
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		/** @var Zero1_Seoredirects_Model_Resource_ImportLog_Collection $importLogCollection */
		$importLogCollection = Mage::getModel('zero1_seo_redirects/importLog')->getCollection();
		$importLogCollection->deleteAll();
		/** @var Zero1_Seoredirects_Model_Resource_ImportStatus_Collection $importStatusCollection */
		$importStatusCollection = Mage::getModel('zero1_seo_redirects/importStatus')->getCollection();
		$importStatusCollection->deleteAll();
	}

	protected function log($message, $lineNumber = null, $severity = Zend_Log::INFO, $scope = null, $scopeId = null)
	{
		if($scope === null){
			$scope = $this->scope;
		}
		if($scopeId === null){
			$scopeId = $this->scopeId;
		}
		/** @var Zero1_Seoredirects_Model_ImportLog $importLog */
		$importLog = Mage::getModel('zero1_seo_redirects/importLog');
		$importLog->setMessage($message)
			->setScope($scope)
			->setScopeId($scopeId)
			->setSeverity($severity)
			->setLineNumber($lineNumber)
			->save();
	}

    protected function checkAndImportEnableds($storeId = null, $websiteId = null)
    {
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
        if($this->getHelper()->getIsEnabled($storeId, $websiteId)){
            $this->log('Is Enabled');
            if($this->getHelper()->getIsGoogleDocEnabled($storeId, $websiteId)){
				$this->log('Google Docs Is Enabled');
                if($this->getHelper()->getHasRemoteFile($storeId, $websiteId)){
                    $this->getGoogleImporter()->import($storeId, $websiteId);
                }else{
					$this->log('No remote file found');
                }
            }else{
				$this->log('Google Docs Is Not Enabled');
            }

            if($this->getHelper()->getIsLocalFileEnabled($storeId, $websiteId)){
                $this->log('Local File Is Enabled');
                if($this->getHelper()->getHasLocalFile($storeId, $websiteId)){
                    $this->getLocalImporter()->import($storeId, $websiteId);
                }else{
                    $this->log('Not local file found');
                }
            }else{
                $this->log('Local File Is Not Enabled');
            }

        }else{
            $this->log('Not enabled');
        }
    }

	/**
	 * If an 'imported' url wasnt updated in the import, then it has been removed from the sheet and will need to be removed
	 */
	protected function removeOldImportedUrls()
	{
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		/* @var $redirectionCollection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
		$redirectionCollection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection();
        $date = date('Y-m-d H:i:s', $this->startTime);

        $redirectionCollection->addFieldToFilter('source', Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE);
        $redirectionCollection->addFieldToFilter('updated_at', array('lt' => $date));
        $this->log(sprintf('Deleted %d old url(s)', $redirectionCollection->count()), null, Zend_Log::INFO, self::SCOPE_SUMMARY, 0);

        //Mage::log('deleting: source = "'.Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE.'" AND updated_at < "'.$date.'"', Zend_Log::DEBUG, 'seo.log', true);
        $redirectionCollection->getConnection()->delete($redirectionCollection->getMainTable(),
            'source = "'.Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE.'" AND updated_at < "'.$date.'"');
	}

	/**
	 * If the number of enabled redirects is lower than the limit, and there are disabled redirects, enable all up to limit
	 * this occurs because a clean down of the urls occurs last.
	 */
	protected function updateStatuses()
    {
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		/* @var $redirectionCollection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
		$enabledRedirectionCount = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
			->addFieldToFilter('status', Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_ENABLED_VALUE)
			->count();

		/* @var $licenseHelper Zero1_SeoRedirects_Helper_License */
		$licenseHelper = Mage::helper('zero1_seo_redirects/license');
		$licenseLimit = $licenseHelper->getLicenceLimit();

		if($enabledRedirectionCount < $licenseLimit || $licenseLimit == 0){
			$redirectionCollection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
				->addFieldToFilter('source', array('neq' => Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_LOGGED_VALUE))
				->addFieldToFilter('status', Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_DISABLED_VALUE);

			if($licenseLimit > 0){
				$redirectionCollection->setPageSize($licenseLimit - $enabledRedirectionCount);
				$redirectionCollection->setCurPage(1);
			}
			/* @var $redirect Zero1_Seoredirects_Model_Redirection */
			foreach($redirectionCollection as $redirect){
				$redirect->setStatus(Zero1_Seoredirects_Model_Redirection::REDIRECTION_STATUS_ENABLED_VALUE);
				$redirect->save();
			}
		}
	}

	protected function end()
	{
		//Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		$config = $this->getRunLockConfig();
		$config->setValue(self::RUNLOCK_NOT_RUNNING)->save();
	}

	public static function isRunning()
	{
		$value = Mage::getStoreConfig(self::CONFIG_PATH_RUNLOCK);
		if($value == self::RUNLOCK_RUNNING){
			return true;
		}else{
			return false;
		}
	}

    public static function getStatus()
    {
        $value = Mage::getStoreConfig(self::CONFIG_PATH_RUNLOCK);
        if(!$value){
            $value = self::RUNLOCK_NOT_RUNNING;
        }
        return $value;
    }
}
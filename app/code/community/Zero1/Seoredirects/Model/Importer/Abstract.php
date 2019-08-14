<?php
/**
 * Class Zero1_Seoredirects_Model_Importer_Abstract
 */
abstract class Zero1_Seoredirects_Model_Importer_Abstract
{
	protected $websiteId = null;
	protected $storeId = null;
	protected $scope = null;
	protected $scopeId = null;
	const SCOPE_DEFAULT = 'default';
	const SCOPE_STORE = 'store';
	const SCOPE_WEBSITE = 'website';
	protected $status = null;
	protected $lineNumber = 0;
	protected $columns;
	protected $allowedUrls;
	protected $redirector = null;

	const HEADER_FROM_URL = 'from-url';
	const HEADER_TO_URL = 'to-url';
	const HEADER_TYPE = 'type';
	const HEADER_PERSIST_QUERY = 'persist-query';
	const COLUMN_PARTICIPATION_MANDATORY = 'mandatory';
	const COLUMN_PARTICIPATION_OPTIONAL = 'optional';

	const STATUS_UPDATE_SET_SIZE = 50;

	/**
	 * @return Zero1_Seoredirects_Helper_Data
	 */
	protected function getHelper()
	{
		return Mage::helper('zero1_seo_redirects');
	}

	/**
	 * @return Zero1_Seoredirects_Model_ImportStatus
	 */
	protected function getStatus()
	{
		if(!$this->status){
			$this->status = Mage::getModel('zero1_seo_redirects/importStatus');
		}
		return $this->status;
	}

	final public function import($storeId, $websiteId)
	{
		$this->start($storeId, $websiteId);
		$this->run();
		$this->end();
	}

	protected function start($storeId, $websiteId)
	{
		if($storeId === null && $websiteId === null){
			$this->scopeId = 0;
			$this->scope = self::SCOPE_DEFAULT;
		}elseif($storeId === null){
			$this->scopeId = $websiteId;
			$this->scope = self::SCOPE_WEBSITE;
		}else{
			$this->scopeId = $storeId;
			$this->scope = self::SCOPE_STORE;
		}
		$this->storeId = $storeId;
		$this->websiteId = $websiteId;

		$this->getStatus()->setScope($this->scope)
			->setScopeId($this->scopeId)
			->save();
		$this->lineNumber = 0;

		$this->initColumns();
		$this->initAllowedUrls();
	}

	protected function initColumns()
	{
		$self = $this;
		$this->columns = array(
			self::HEADER_FROM_URL => array(
				'index' => 0,
				'participation' => self::COLUMN_PARTICIPATION_MANDATORY,
				'parse' => function($value) use ($self) {
						return $value;
					}
			),
			self::HEADER_TO_URL => array(
				'index' => 1,
				'participation' => self::COLUMN_PARTICIPATION_MANDATORY,
				'parse' => function($value) use ($self) {
						return $value;
					}
			),
			self::HEADER_TYPE => array(
				'index' => 2,
				'participation' => self::COLUMN_PARTICIPATION_OPTIONAL,
				'parse' => function($value) use ($self) {
						switch($value){
							case Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_LABEL:
								$t = Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE;
								break;
							case Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_LABEL:
								$t = Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_VALUE;
								break;
							default:
								$t = Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE;
								$self->log('No type found defaulting to fixed, options are: '.Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_LABEL.', '.Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_LABEL, $self->getLineNumber(), Zend_Log::INFO);
						}
						return $t;
					}
			),
			self::HEADER_PERSIST_QUERY => array(
				'index' => 3,
				'participation' => self::COLUMN_PARTICIPATION_OPTIONAL,
				'parse' => function($value) use ($self) {
						switch($value){
							case 'No':
								$t = 0;
								break;
							case 'Yes':
								$t = 1;
								break;
							default:
								$t = 0;
								$self->log('No matching value found for persist query, options are: \'Yes\' or \'No\'. Defaulting to No', $self->getLineNumber(), Zend_Log::INFO);
						}
						return $t;
					}
			),
		);
	}

	protected function initAllowedUrls()
	{
		$this->allowedUrls = array();
		/** @var $store Mage_Core_Model_Store */
		/** @var $website Mage_Core_Model_Website */
		switch($this->scope){
			case self::SCOPE_STORE:
				$store = Mage::app()->getStore($this->scopeId);
				$this->allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false)] = $store->getId();
				$this->allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)] = $store->getId();
				break;
			case self::SCOPE_WEBSITE:
				$website = Mage::app()->getWebsite($this->scopeId);
				foreach($website->getStores(false) as $storeId => $store){
					$this->allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false)] = $storeId;
					$this->allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)] = $storeId;
				}
				break;
			case self::SCOPE_DEFAULT:
				foreach(Mage::app()->getWebsites(false) as $websiteId => $website){
					foreach($website->getStores(false) as $storeId => $store){
						$this->allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false)] = $storeId;
						$this->allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)] = $storeId;
					}
				}
				break;
		}
	}

	abstract function run();

	protected function end()
	{
		$this->getStatus()->setDataChanges(true);
		$this->getStatus()->save();
	}

	public function log($message, $lineNumber = null, $severity = Zend_Log::INFO, $scope = null, $scopeId = null)
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

	protected function hasHeaders($data)
	{
		$from = array_search(self::HEADER_FROM_URL, $data);
		$to = array_search(self::HEADER_TO_URL, $data);
		if($from === false || $to === false){
			$this->log(sprintf('No headers found, for headers to be found, you should at least specify \'%s\' and \'%s\' columns. You may also specify \'%s\' and \'$s\'',
				self::HEADER_FROM_URL, self::HEADER_FROM_URL, self::HEADER_PERSIST_QUERY, self::HEADER_TYPE),
				$this->lineNumber, Zend_Log::WARN);
			return false;
		}
		return true;
	}

	protected function importHeaders($data)
	{
		$from = array_search(self::HEADER_FROM_URL, $data);
		$to = array_search(self::HEADER_TO_URL, $data);
		$type = array_search(self::HEADER_TYPE, $data);
		$persistQuery = array_search(self::HEADER_PERSIST_QUERY, $data);

		if($from !== false){
			$this->columns[self::HEADER_FROM_URL]['index'] = $from;
		}
		if($to !== false){
			$this->columns[self::HEADER_TO_URL]['index'] = $to;
		}
		if($type !== false){
			$this->columns[self::HEADER_TYPE]['index'] = $type;
		}
		if($persistQuery !== false){
			$this->columns[self::HEADER_PERSIST_QUERY]['index'] = $persistQuery;
		}
		$this->log('Headers found ('.json_encode(array_keys($this->columns)).')', $this->lineNumber, Zend_Log::INFO);
		return true;
	}

	protected function updateStatus($force = false)
	{
		if(($this->lineNumber % self::STATUS_UPDATE_SET_SIZE) == 0 || $force){
			$this->getStatus()->setImported($this->lineNumber)->save();
		}
	}

	protected function importRow($data)
	{
		$this->log(sprintf('Importing row: %s', json_encode($data)), $this->lineNumber, Zend_Log::DEBUG);
		$from = $this->getCell(self::HEADER_FROM_URL, $data);
		$to = $this->getCell(self::HEADER_TO_URL, $data);

		if($from === false || $to === false){
			return;
		}

		$type = $this->getCell(self::HEADER_TYPE, $data);
		$persistQuery = $this->getCell(self::HEADER_PERSIST_QUERY, $data);

		/** @var Zero1_Seoredirects_Model_UrlFactory $urlFactory */
		$urlFactory = Mage::getModel('zero1_seo_redirects/urlFactory');
		$urlFactory->setAllowedUrls($this->allowedUrls);

		/** @var Zero1_Seoredirects_Model_Url $fromUrl */
		$fromUrl = $urlFactory->buildUrl($from);
		if(!$fromUrl->getStoreId()){
			$this->log('Skipped Url: "'.$from.'" as couldn\'t find matching store', $this->lineNumber, Zend_Log::WARN);
			return;
		}
		$urlFactory->lockToUrl($fromUrl);
		/** @var Zero1_Seoredirects_Model_Url $toUrl */
		$toUrl = $urlFactory->buildUrl($to);
		if(!$toUrl->getStoreId()){
			$this->log('Skipped Url: "'.$to.'" as couldn\'t find matching store', $this->lineNumber, Zend_Log::WARN);
			return;
		}
		$urlFactory->unlockToUrl();

		//has this redirect previously been imported?
        $this->log(sprintf('Looking for redirect, store: %d, url: %s',$fromUrl->getStoreId(), $fromUrl->getUrl()));
		list($redirectId, $resultToUrl) = $this->getRedirector()->getRedirectId($fromUrl->getStoreId(), $fromUrl->getUrl(), true);
        //Mage::log(sprintf('Id found: %s', $redirectId), Zend_Log::DEBUG, 'seo.log', true);
        $this->log(sprintf('Id found: %s', $redirectId));

		/** @var Zero1_Seoredirects_Model_Redirection $redirection */
		$redirection = Mage::getModel('zero1_seo_redirects/redirection');
		if($redirectId){
            $this->log(sprintf('loaded: %d', $redirectId));
			$redirection->load($redirectId);
		}

		$redirection->setStoreId($fromUrl->getStoreId())
			->setFromUrlInstance($fromUrl)
			->setToUrlInstance($toUrl)
			->setFromType($type)
			->setPersistQuery($persistQuery)
			->setSource(Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE);

        $this->log(sprintf('saving: %s', json_encode($redirection->getData())));
		try{
            //Mage::log('saving, model has changes?'.json_encode($redirection->hasDataChanges()), Zend_Log::DEBUG, 'seo.log', true);
			$redirection->save();
		}
		catch(Zero1_Seoredirects_Exception $e){
			$messages = $e->getMessages();
			foreach($messages as $err){
				$this->log($err.' Redirect was not imported.', $this->lineNumber, Zend_Log::WARN);
			}
		}
		catch(Exception $e){
			//TODO maybe just throw this?
			$this->log($e->getMessage(), $this->lineNumber, Zend_Log::CRIT);
		}
	}

	protected function getCell($cell, $data)
	{
		$index = $this->columns[$cell]['index'];
		$participation = $this->columns[$cell]['participation'];
		$parse = $this->columns[$cell]['parse'];

		$dataValue = (isset($data[$index])? $data[$index] : '');
		$result = $parse($dataValue);

		if($result == ''){
			if($participation == self::COLUMN_PARTICIPATION_MANDATORY){
				$this->log('Could not find column "'.$cell.'", skipping this row', $this->lineNumber, Zend_Log::WARN);
			}else{
				$this->log('Could not find column "'.$cell.'"', $this->lineNumber, Zend_Log::NOTICE);
			}
			return false;
		}
		return $result;
	}

	/**
	 * @return Zero1_Seoredirects_Model_Redirecter
	 */
	protected function getRedirector()
	{
		if(!$this->redirector){
			$this->redirector = Mage::getModel('zero1_seo_redirects/redirecter');
		}
		return $this->redirector;
	}

    public function getLineNumber()
    {
        return $this->lineNumber;
    }
}
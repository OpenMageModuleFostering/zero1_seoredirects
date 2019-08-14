<?php

/**
 * Class Zero1_Seoredirects_Model_Importer
 *
 * @method Mage_Core_Model_Store getStore()
 * @method Mage_Core_Model_Website getWebsite();
 */
class Zero1_Seoredirects_Model_Importer extends Varien_Object{

    protected $_log = array();
    protected $_columns = array();
    protected $_lineNumber = 1;
	protected $_allowedUrls = null;
    protected $_model = null;
    protected $_zendHttpClient;
    protected $_usedIds = array();

	const COLUMN_PARTICIPATION_MANDATORY = 0;
	const COLUMN_PARTICIPATION_OPTIONAL = 1;
	const LOCATION_STORE = 0;
	const LOCATION_WEBSITE = 1;
	const LOCATION_DEFAULT = 2;

    public function _construct(){
        $this->_log = array();
        $this->_columns = array('from' => array('index' => 0, 'participation' => self::COLUMN_PARTICIPATION_MANDATORY),
			'to' => array('index' => 1, 'participation' => self::COLUMN_PARTICIPATION_MANDATORY),
			'type' => array('index' => 2, 'participation' => self::COLUMN_PARTICIPATION_OPTIONAL),
			'persist_query' => array('index' => 3, 'participation' => self::COLUMN_PARTICIPATION_OPTIONAL),
		);
    }

    protected function _log($msg = '', $lineNumber = null, $severity = Zend_Log::DEBUG, $force = false){
		if($severity > Mage::helper('zero1_seo_redirects')->getImportLogLevel()&& !$force){
			return $this;
		}
		$log = array(
            'line_number' => (($lineNumber === null)? $this->_lineNumber : $lineNumber),
            'message' => $msg,
            'severity' => $severity
        );

		switch($this->_getLocation()){
			case self::LOCATION_STORE:
				if(!isset($this->_log[$this->getWebsite()->getCode()])){
                    $this->_log[$this->getWebsite()->getCode()] = array();
                    $this->_log[$this->getWebsite()->getCode()]['log'] = array();
                    $this->_log[$this->getWebsite()->getCode()]['stores'] = array();
                }
				if(!isset($this->_log[$this->getWebsite()->getCode()]['stores'][$this->getStore()->getCode()])){
                    $this->_log[$this->getWebsite()->getCode()]['stores'][$this->getStore()->getCode()] = array();
                    $this->_log[$this->getWebsite()->getCode()]['stores'][$this->getStore()->getCode()]['log'] = array();
                }
				$this->_log[$this->getWebsite()->getCode()]['stores'][$this->getStore()->getCode()]['log'][] = $log;
				break;
			case self::LOCATION_WEBSITE:
				if(!isset($this->_log[$this->getWebsite()->getCode()])){
                    $this->_log[$this->getWebsite()->getCode()] = array();
                    $this->_log[$this->getWebsite()->getCode()]['log'] = array();
                    $this->_log[$this->getWebsite()->getCode()]['stores'] = array();
                }
				$this->_log[$this->getWebsite()->getCode()]['log'][] = $log;
				break;
			case self::LOCATION_DEFAULT:
				if(!isset($this->_log['default'])){
                    $this->_log['default'] = array();
                    $this->_log['default']['log'] = array();
                }
				$this->_log['default']['log'][] = $log;
		}
        return $this;
    }

	protected function _getLocation(){
		if($this->getStore() !== null){
			return self::LOCATION_STORE;
		}
		if($this->getWebsite() !== null){
			return self::LOCATION_WEBSITE;
		}
		return self::LOCATION_DEFAULT;
	}

    /**
     * @return Zero1_Seoredirects_Helper_Data
     */
    protected function _helper(){
        return Mage::helper('zero1_seo_redirects');
    }

    /**
     * @param bool $reset
     * @return Zero1_Seoredirects_Model_Redirection
     */
    protected function _getModel($reset = false){
        if($this->_model === null){
            $this->_model = Mage::getModel('zero1_seo_redirects/redirection');
        }
        if($reset){
            $this->_model->setData(array());
        }
        return $this->_model;
    }

    /**
     * @return Zend_Http_Client
     */
    protected function _getZendClient(){
        if (!$this->_zendHttpClient instanceof Zend_Http_Client) {
            $this->_zendHttpClient = new Zend_Http_Client();
        }
        return $this->_zendHttpClient;
    }

    public function import($store = null, $website = null){
        //get all ids of redirects type 'import'
		/* @var $website Mage_Core_Model_Website */
		foreach(Mage::app()->getWebsites(false) as $websiteId => $website){
			$this->setWebsite($website);
            $this->checkAndImportEnableds(null, $website->getId());
			/* @var $store Mage_Core_Model_Store */
			foreach($website->getStores(false) as $storeId  => $store){
				$this->setStore($store);
                $this->checkAndImportEnableds($store->getId(), null);

				$this->setStore(null);
				$this->_allowedUrls = null;
			}
			$this->setWebsite(null);
			$this->_allowedUrls = null;
		}
        //do default to finish
        $this->checkAndImportEnableds();

        //delete now unused urls, and refresh license count
        $this->_clearUrls();

        return $this->_log;
    }

    protected function checkAndImportEnableds($storeId = null, $websiteId = null){
        if($this->_helper()->getIsEnabled($storeId, $websiteId)){
            $this->_log('Is Enabled', 'N/A', Zend_Log::INFO, true);
            if($this->_helper()->getIsGoogleDocEnabled($storeId, $websiteId)){
                $this->_log('Google Docs Is Enabled', 'N/A', Zend_Log::INFO, true);
                if($this->_helper()->getHasRemoteFile($storeId, $websiteId)){
                    $this->_importRemote($this->_helper()->getRemoteFileUrl($storeId, $websiteId));
                }else{
                    $this->_log('No remote file found', 'N/A', Zend_Log::INFO, true);
                }
            }else{
                $this->_log('Google Docs Is Not Enabled', 'N/A', Zend_Log::INFO, true);
            }

            if($this->_helper()->getIsLocalFileEnabled($storeId, $websiteId)){
                $this->_log('Local File Is Enabled', 'N/A', Zend_Log::INFO, true);
                if($this->_helper()->getHasLocalFile($storeId, $websiteId)){
                    $this->_importLocal(Mage::helper('zero1_seo_redirects/files')->getFile($storeId, $websiteId));
                }else{
                    $this->_log('Not local file found', 'N/A', Zend_Log::INFO, true);
                }
            }else{
                $this->_log('Local File Is Not Enabled', 'N/A', Zend_Log::INFO, true);
            }

        }else{
            $this->_log('Not enabled', 'N/A', Zend_Log::INFO, true);
        }
    }

    /**
     * @param $file Zero1_Seoredirects_Model_File
     */
    protected function _importLocal($file){
		$this->_log('Importing local file: '.$file->getInternalPath(),'N/A', Zend_Log::INFO, true);
        $handle = fopen($file->getInternalPath(), 'r');
        $this->_lineNumber = 0;

        while(!feof($handle)){
            $line = fgetcsv($handle);
            if($this->_lineNumber == 0){
                if(!$this->_checkHeaders($line)){
                    $this->_importRow($line);
                }
            }else{
                $this->_importRow($line);
            }
            $this->_lineNumber++;
        }
        $this->_log('Total number of lines parsed: '.$this->_lineNumber, 'N/A', Zend_Log::INFO, true);
        fclose($handle);
    }

	protected function _importRemote($url = ''){
        $this->_log('Importing remote file: "'.$url.'"','N/A', Zend_Log::INFO, true);
		preg_match('/output=csv/', $url, $results, PREG_OFFSET_CAPTURE);
		if(!empty($results)){
			return $this->_importRemoteCsv($url);
		}else{
			preg_match('/pubhtml/', $url, $results, PREG_OFFSET_CAPTURE);
            if(!empty($results)){
                return $this->_importRemoteHtml($url);
            }else{
                $this->_log('File type could not be verified, aborting.','N/A', Zend_Log::WARN);
                return;
            }
		}
	}

    protected function _importRemoteHtml($url){
        $this->_log('Importing remote html: "'.$url.'"','N/A', Zend_Log::INFO, true);
        $content = '';
        try {
            $this->_getZendClient()->setUri($url);
            $content = $this->_getZendClient()->request()->getBody();
        } catch(Exception $e) {
            $this->_log('Failed to retrieve content from remote document "'.$url.'" Error: '.$e->getMessage(), 'N/A', Zend_Log::WARN);
            return;
        }
        if(preg_match('~<!DOCTYPE html>~', $content) != 1){
            $this->_log('Content from remote url, does not appear to html "'.$url.'"', 'N/A', Zend_Log::WARN);
        }

        $this->_lineNumber = 1;
        $doc = new DOMDocument();
        $doc->loadHTML($content);
        /* @var $tables DOMNodeList */
        $tables = $doc->getElementsByTagName('table');
        $table = $tables->item(0);

        /* @var $child DOMElement */
        foreach($table->childNodes as $child){
            if($child->nodeName !== 'tbody'){
                continue;
            }else{
                /* @var $row DOMElement */
                /* @var $cell DOMElement */
                foreach($child->childNodes as $row){
                    $data = array();
                    foreach($row->childNodes as $cell){
                        if($cell->nodeName === 'td'){
                            $data[] = $cell->textContent;
                        }
                    }
                    if($this->_lineNumber == 1){
                        if(!$this->_checkHeaders($data)){
                            $this->_importRow($data);
                        }
                    }else{
                        $this->_importRow($data);
                    }
                    $this->_lineNumber++;
                }
                $this->_log('Total number of lines parsed: '.$this->_lineNumber, 'N/A', Zend_Log::NOTICE, true);
                return;
            }
        }
    }

    protected function _importRemoteCsv($url){
        $this->_log('Importing remote csv: "'.$url.'"','N/A', Zend_Log::INFO, true);
        try {
            $this->_getZendClient()->setUri($url);
            $content = $this->_getZendClient()->request()->getBody();
        } catch(Exception $e) {
            $this->_log('Failed to retrieve content from remote document "'.$url.'" Error: '.$e->getMessage(), 'N/A', Zend_Log::WARN);
            return;
        }

        $this->_lineNumber = 1;
        foreach (explode(PHP_EOL, $content) as $line) {
            $data = str_getcsv($line);
            if($this->_lineNumber == 1){
                if(!$this->_checkHeaders($data)){
                    $this->_importRow($data);
                }
            }else{
                $this->_importRow($data);
            }
            $this->_lineNumber++;
        }
        $this->_log('Total number of lines parsed: '.$this->_lineNumber, 'N/A', Zend_Log::NOTICE, true);
        return;
    }

    protected function _checkHeaders($data){
        $from = array_search('from-url', $data);
        $to = array_search('to-url', $data);
        $type = array_search('type', $data);
		$persistQuery = array_search('persist-query', $data);
        if($from === false || $to === false){
            $this->_log('No headers found, for headers to be found, you should at least specify \'from-url\' and \'to-url\' columns. You may also specify \'type\' and \'persist-query\'', null, Zend_Log::WARN);
            if($from === false){
				$this->_columns['from']['index'] = 0;
			}else{
				$this->_columns['from']['index'] = $from;
			}
			if($to === false){
				$this->_columns['to']['index'] = 1;
			}else{
				$this->_columns['to']['index'] = $to;
			}
			return false;
        }
        $this->_columns['from']['index'] = $from;
        $this->_columns['to']['index'] = $to;
        if($type !== false){
            $this->_columns['type']['index'] = $type;
        }
		if($persistQuery !== false){
			$this->_columns['persist_query']['index'] = $persistQuery;
		}
        $this->_log('Headers found ('.json_encode(array_keys($this->_columns)).')', null, Zend_Log::INFO);
        return true;
    }

    protected function _importRow($data){
		$this->_log('_importRow: '.json_encode($data), null, Zend_Log::DEBUG);
		$from = $this->_getCell('from', $this->_columns['from'], $data);
		$to = $this->_getCell('to', $this->_columns['to'], $data);
		if($from === false || $to === false){ return; }

        $type = $this->_parseType($this->_getCell('type', $this->_columns['type'], $data));
		$persistQuery = $this->_parsePersistQuery($this->_getCell('persist-query', $this->_columns['persist_query'], $data));

		$allowedUrls = $this->_getAllowedUrls();

		//check that there is a match in the allowed urls
		$storeMatched = false;
        $currentMatch = '';
		foreach($allowedUrls as $storeUrl => $data){
			if(strpos($from, $storeUrl) === 0){
                if(strlen($storeUrl) > strlen($currentMatch)){
                    $storeMatched = true;
                    $currentMatch = $storeUrl;
                }
			}
		}

        if($storeMatched){
            $storeId = $allowedUrls[$currentMatch]['store_id'];
        }else{

			if(strpos($from, '://') !== false || $from[0] !== '/'){
				$this->_log('Skipped as from url location did not match any of the allowed stores.', null, Zend_Log::WARN);
				return;
			}

			$this->_log('Relative path found, prepending both \'from\' and \'to\' with current scope url', null, Zend_Log::NOTICE);

			switch($this->_getLocation()){
				case self::LOCATION_STORE:
					$storeId = $this->getStore()->getId();
					break;
				case self::LOCATION_WEBSITE:
					$storeId = $this->getWebsite()->getId();
					break;
				default:
					$storeId = 0;
			}
			$from = $this->getBaseUrl().substr($from, 1);
			$to = $this->getBaseUrl().substr($to, 1);
		}

		$from = Mage::helper('zero1_seo_redirects/urls')->parseUrl($from, $storeId);
		$to = Mage::helper('zero1_seo_redirects/urls')->parseUrl($to, $storeId);

		if(!isset($from['scheme'], $from['host'], $from['path'], $to['scheme'], $to['host'], $to['path']) ||
			$from['scheme'] != $to['scheme'] || $from['host'] != $to['host']
		){
			$this->_log('Skipped as to url location did not match the domain as the from url', null, Zend_Log::WARN);
            return;
		}

		//has this redirect previously been imported?
        /* @var $redirectCollection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
        $redirectCollection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
            ->addFieldToFilter('from_url_path', $from['path'])
            ->addFieldToFilter('from_url_query', (isset($from['query'])? $from['query'] : null))
            ->addFieldToFilter('store_id', $storeId);

        //the redirect has already imported/created so just make sure that it has a IMPORT source
        if($redirectCollection->count()){
            $redirect = $redirectCollection->getFirstItem();
            //check if redirect has already been used.
            if($this->_checkId($redirect->getId()) !== false){
                $redirect = $this->_getModel(true);
                $redirect->setFromUrlPath($from['path'])
                    ->setFromUrlQuery((isset($from['query'])? $from['query'] : null))
                    ->setStoreId($storeId)
                    ->setFromType($type)
					->setPersistQuery($persistQuery);
            }
        }else{
            //no redirect exists
            $redirect = $this->_getModel(true);
            $redirect->setFromUrlPath($from['path'])
                ->setFromUrlQuery((isset($from['query'])? $from['query'] : null))
                ->setStoreId($storeId)
                ->setFromType($type)
				->setPersistQuery($persistQuery);
        }
        $redirect->setSource(Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE)
            ->setToUrl($this->_buildUrl($to));
        try{
            $redirect->save();
            $this->_removeId($redirect->getId());
        }
        catch(Zero1_Seoredirects_Exception $e){
            $messages = $e->getMessages();
            foreach($messages as $err){
                $this->_log($err.' Redirect was not imported.', null, Zend_Log::WARN);
            }
        }
        catch(Exception $e){
            //TODO maybe just throw this?
            $this->_log($e->getMessage(), null, Zend_Log::CRIT);
        }

    }

	protected function _getCell($colName, $col, $data){
		if(isset($data[$col['index']])){
			$result = $data[$col['index']];
		}else{
			if($col['participation'] == self::COLUMN_PARTICIPATION_MANDATORY){
				$this->_log('Could not find column "'.$colName.'", skipping this row', null, Zend_Log::WARN);
			}else{
				$this->_log('Could not find column "'.$colName.'"', null, Zend_Log::NOTICE);
			}
			return false;
		}
		if($result == ''){
			if($col['participation'] == self::COLUMN_PARTICIPATION_MANDATORY){
				$this->_log('Empty cell in column "'.$colName.'", skipping this row', null, Zend_Log::WARN);
			}else{
				$this->_log('Empty cell in column "'.$colName.'"', null, Zend_Log::NOTICE);
			}
			$result = false;
		}
		return $result;
	}

	/**
	 * returns an array of urls that are allowed for the current location
	 * @return array
	 */
	protected function _getAllowedUrls(){
		if($this->_allowedUrls !== null){
			return $this->_allowedUrls;
		}
		$this->_allowedUrls = array();
		switch($this->_getLocation()){
			case self::LOCATION_STORE:
				$this->_addToAlloweds($this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false), $this->getStore());
				$this->_addToAlloweds($this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true), $this->getStore());
				break;
			case self::LOCATION_WEBSITE:
				foreach($this->getWebsite()->getStores(false) as $storeId => $store){
					$this->_addToAlloweds($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false), $store);
					$this->_addToAlloweds($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true), $store);
				}
				break;
			case self::LOCATION_DEFAULT:
				foreach(Mage::app()->getWebsites(false) as $websiteId => $website){
					foreach($website->getStores(false) as $storeId => $store){
						$this->_addToAlloweds($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false), $store);
						$this->_addToAlloweds($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true), $store);
					}
				}
		}
		return $this->_allowedUrls;
	}
    protected function getBaseUrl(){
        switch($this->_getLocation()){
            case self::LOCATION_STORE:
                $url = Mage::app()->getStore($this->getStore()->getId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false);
                break;
            case self::LOCATION_WEBSITE:
                $url = Mage::app()->getWebsite($this->getWebsite()->getId())->getConfig('web/unsecure/base_link_url');
                if(strpos($url, '{{unsecure_base_url}}') !== false){
                    $url = Mage::app()->getStore(0)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false);
                }
                break;
            case self::LOCATION_DEFAULT:
            default:
                $url = Mage::app()->getStore(0)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false);
                break;
        }
        return $url;
    }
	protected function _addToAlloweds($url, $store){
		$this->_allowedUrls[$url] = array(
			'store_id' => $store->getId(),
			'url' => parse_url($url),
		);
	}
    protected function _parseType($type = null){
        switch($type){
            case Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_LABEL:
                $t = Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE;
                break;
            case Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_LABEL:
                $t = Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_VALUE;
                break;
            default:
                $t = Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE;
                $this->_log('No type found defaulting to fixed, options are: '.Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_LABEL.', '.Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_LABEL, null, Zend_Log::INFO);
        }
        return $t;
    }

	protected function _parsePersistQuery($persistQuery = null){
		switch($persistQuery){
			case 'No':
				$t = 0;
				break;
			case 'Yes':
				$t = 1;
				break;
			default:
				$t = 0;
				$this->_log('No matching value found for persist query, options are: \'Yes\' or \'No\'. Defaulting to No', null, Zend_Log::INFO);
		}
		return $t;
	}

    /**
     * Remove id from the pool of allowed ids
     * @param $id
     */
    protected function _removeId($id){
        $this->_usedIds[] = $id;
    }

    /**
     * check if the redirect has been imported already
     * @param $id
     */
    protected function _checkId($id){
        return array_search($id, $this->_usedIds);
    }

    /**
     * Any url that is of type import and wasn't imported this run, remove.
     */
    protected function _clearUrls(){
		if(empty($this->_usedIds)){
            return;
        }
		//delete all imported urls for this store that are of type import
		/* @var $redirectionCollection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
		$redirectionCollection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection();
		$redirectionCollection
			->addFieldToFilter('source', Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_IMPORT_VALUE)
            ->addFieldToFilter('redirection_id', array('nin' => array($this->_usedIds)));
		$this->_log('Deleting: '.$redirectionCollection->count().' redirects', 'N/A', Zend_Log::INFO);
		$redirectionCollection->delete();
        $this->_recalculateStatuses();
	}

    /**
     * If the number of enabled redirects is lower than the limit, and there are disabled redirects, enable all up to limit
     * this occurs because a clean down of the urls occurs last.
     */
    protected function _recalculateStatuses(){
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

    protected function _buildUrl($parsedUrl){
        $url = $parsedUrl['path'];
        if(isset($parsedUrl['query'])){
            $url .= '?'.$parsedUrl['query'];
        }
        return $url;
    }



}
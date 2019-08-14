<?php
class Zero1_Seoredirects_Model_Redirector{

	private $originalUrl;
	private $cachedUrlModel;

	/**
	 * @param $storeId
	 * @param $url
	 * @param bool $dryRun
	 * @return string | null
	 */
	public function redirect($storeId, $url, $dryRun = false){
        $this->_getHelper()->debug('redirect('.$storeId.', '.$url.', '.json_encode($dryRun).')');

		$this->originalUrl = $url;
		$this->cachedUrlModel = null;

		//first check if there is a cached version of the $url
		if($this->isCached()){
			if($dryRun){
				return $this->getCachedRedirect()->getId();
			}else{
				$this->_redirect($this->getCachedRedirect()->getId(), $this->getCachedRedirect()->getToUrl());
			}
		}
        $this->_getHelper()->debug('No cached url found');

		//sort query string alphabetically, and remove ignoreables
		$parsedUrl = $this->_getUrlHelper()->parseUrl($url, $storeId);
        $this->_getHelper()->debug(json_encode($parsedUrl));

        /* @var $redirection Zero1_Seoredirects_Model_Redirection */
        $redirection = Mage::getModel('zero1_seo_redirects/redirection');

        //search for matches of type fixed
        $redirection->loadFixed($storeId, $parsedUrl['path'], (isset($parsedUrl['query'])? $parsedUrl['query'] : null));
        if($redirection->getId()){
            if($dryRun){
                return $redirection->getId();
            }
            $this->_getHelper()->debug('Found Url outside of cache');
            if($redirection->isEnabled()){
                $to = $parsedUrl['scheme'].'://'.$parsedUrl['host'].$redirection->getToUrl();
                $this->_cache($redirection->getId(), $url, $to);
                $this->_redirect($redirection->getId(), $to);
            }else{
                $this->_getHelper()->debug('Redirect disabled');
                $this->_incrementHits($redirection->getId());
                return false;
            }
        }
        $this->_getHelper()->debug('No fixed matches');

        //search for matches of type open ended
        $queryless = !isset($parsedUrl['query']);
        /* @var $redirectionCollection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
        $redirectionCollection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection();
        $redirectionCollection->addOpenEnded()
            ->addFieldToFilter('store_id', 1)
            ->addFieldToFilter('from_url_path', $parsedUrl['path']);
        //if the input doesn't have a query string, then we are looking for an open ended with no query params
        if($queryless){
            $redirectionCollection->addFieldToFilter('from_url_query', null);
        }
        $this->_getHelper()->debug($redirectionCollection->getSelectSql(true));

        $this->_getHelper()->debug('Open ended possible matches: '.$redirectionCollection->count());

        if($redirectionCollection->count() == 0){
            if(!$dryRun){
                $this->_logNoMatch($storeId, $parsedUrl);
            }
            return false;
        }

        //just use the first result
        if($queryless){
            $redirection = $redirectionCollection->getFirstItem();
            if($dryRun){
                return $redirection->getId();
            }
            if($redirection->isEnabled()){
                $to = $parsedUrl['scheme'].'://'.$parsedUrl['host'].$redirection->getToUrl();
                $this->_cache($redirection->getId(), $url, $to);
                $this->_redirect($redirection->getId(), $to);
            }else{
                $this->_getHelper()->debug('Redirect disabled');
                $this->_incrementHits($redirection->getId());
                return false;
            }
        }

        //if its a dryRun find out if the from path is matched exactly
        if($dryRun){
            $match = null;
            foreach($redirectionCollection as $redirection){
                if($parsedUrl['query'] == $redirection->getFromUrlQuery()){
                    $match = $redirection;
                    break;
                }
            }
            if($match){
                return $match->getId();
            }else{
                return false;
            }
        }
        //find the result with the highest number of matches
        $highestMatching = null;
        $highestCount = -1;
        $inputParams = $this->_getUrlHelper()->getAssocQuery($parsedUrl['query']);
        foreach($redirectionCollection as $redirection){
            $redirectParams = $this->_getUrlHelper()->getAssocQuery($redirection->getFromUrlQuery());
            //get the number of common k=>v params
            $count = count(array_intersect_assoc($inputParams, $redirectParams));
            //count needs to match params from redirect
            if($count >= count($redirectParams) && $count > $highestCount){
                $highestCount = $count;
                $highestMatching = $redirection;
            }
        }

        if($highestMatching == null){
            $this->_logNoMatch($storeId, $parsedUrl);
            return false;
        }

        if(!$highestMatching->isEnabled()){
            $this->_getHelper()->debug('Redirect disabled');
            $this->_incrementHits($highestMatching->getId());
            return false;
        }

        $to = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
        $parsedMatch = parse_url($highestMatching->getToUrl());
        $to .= $parsedMatch['path'];
        //merge in query params if need
        if($highestMatching->shouldPersistQuery()){
            $to .= '?'.$parsedUrl['query'];
        }else{
            $to .= (isset($parsedMatch['query'])? '?'.$parsedMatch['query'] : '');
        }

        $this->_cache($highestMatching->getId(), $url, $to);
        $this->_redirect($highestMatching->getId(), $to);

        Mage::log('Zero1 SeoRedirects Error', Zend_Log::CRIT);
	}

	protected function isCached(){
		if(!$this->getCachedRedirect()->getId()){
			return false;
		}else{
			return true;
		}
	}

	/* @return Zero1_Seoredirects_Model_Redirection_Cache */
	protected function getCachedRedirect(){
		if($this->cachedUrlModel == null){
			$this->cachedUrlModel = Mage::getModel('zero1_seo_redirects/redirection_cache')->load($this->originalUrl, 'from_url');
		}
		return $this->cachedUrlModel;
	}


	protected function _redirect($redirectionId, $url){
        $this->_incrementHits($redirectionId);
		if($this->_getHelper()->canDebug()){
			$this->_getHelper()->debug('Redirecting to: '.$url);
        }
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$url);
		die();
	}

    protected function _cache($redirectId, $from, $to){
        $this->_getHelper()->debug('caching: '.$from.' => '.$to);
        /* @var $cached Zero1_Seoredirects_Model_Redirection_Cache */
        $cached = Mage::getModel('zero1_seo_redirects/redirection_cache');
        $cached->setRedirectionId($redirectId)
            ->setFromUrl($from)
            ->setToUrl($to)
            ->save();
        return $this;
    }

    protected function _incrementHits($redirectionId){
        $this->_getHelper()->debug('incrementing hits: ');
        /* @var $r Zero1_Seoredirects_Model_Redirection */
        $r = Mage::getModel('zero1_seo_redirects/redirection')->load($redirectionId);
        $this->_getHelper()->debug($r->getFromUrl());
        if($r->getId()){
            $r->incrementHits()->save();
        }
    }

    protected function _logNoMatch($storeId, $parsedUrl){
        //check if logging is enabled
        if(!$this->_getHelper()->getIsLog404Enabled($storeId)){
            if($this->_getHelper()->canDebug()){
                $this->_getHelper()->debug('logging 404\'s is disabled');
            }
			return;
        }
        //check if log is at limit
        $limit = $this->_getHelper()->getLog404Limit($storeId);
        //no point counting if limit is unlimited
        if($limit != 0){
            $loggedCount = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
                ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('source', Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_LOGGED_VALUE)
                ->count();

            if($loggedCount == $limit){
                if($this->_getHelper()->canDebug()){
					$this->_getHelper()->debug('logging is enabled, but limit has been reached');
                }
				return;
            }
        }

        /* @var $r Zero1_Seoredirects_Model_Redirection */
        $r = Mage::getModel('zero1_seo_redirects/redirection');
        $r->setFromUrlPath($parsedUrl['path'])
            ->setStoreId($storeId)
            ->setSource(Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_LOGGED_VALUE)
            ->setStatus(0)
            ->setFromUrlQuery((isset($parsedUrl['query'])? $parsedUrl['query'] : null))
            ->setFromType(Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE)
            ->setHits(1)
            ->save();

        if($this->_getHelper()->canDebug()){
			$this->_getHelper()->debug('logged no match');
        }
		return;
    }

	/**
	 * @return Zero1_Seoredirects_Helper_Urls
	 */
	protected function _getUrlHelper(){
		return Mage::helper('zero1_seo_redirects/urls');
	}

    /**
     * @return Zero1_Seoredirects_Helper_Data
     */
    protected function _getHelper(){
        return Mage::helper('zero1_seo_redirects');
    }
}
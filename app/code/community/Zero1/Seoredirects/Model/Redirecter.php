<?php
class Zero1_Seoredirects_Model_Redirecter{

	private $originalUrl;
	private $orignalUrlObject;
	private $storeId;
    private $found;
    private $shouldCache;

    /**
     * @param $storeId
     * @param $url
     * @return Zero1_Seoredirects_Model_Redirection|null
     */
    public function redirect($storeId, $url)
    {
		list($redirectId, $url) = $this->getRedirectId($storeId, $url);
		if($this->found){
			$this->incrementHits($redirectId);
			if($this->shouldCache){
				$this->cache($redirectId, $url);
			}
            $redirection = Mage::getModel('zero1_seo_redirects/redirection')->load($redirectId);
            //Mage::log('redirection: '.json_encode($redirection), Zend_Log::DEBUG, 'seo.log', true);
            if($redirection->isEnabled()){
                $this->redirectTo($url);
            }
            return $redirection;
		}else{
			if($this->getHelper()->shouldLog404()){
				$this->log404();
			}
		}
    }

	/**
	 * @param $storeId
	 * @param $url
	 * @return string | null
	 */
	public function getRedirectId($storeId, $url)
    {
		/** @var Zero1_Seoredirects_Model_UrlFactory $urlFactory */
		$urlFactory = Mage::getModel('zero1_seo_redirects/urlFactory');
		$urlFactory->setAllowedStores(array($storeId));

		$this->originalUrl = $url;
		$this->orignalUrlObject = $urlFactory->buildUrl($url);
		$this->storeId = $storeId;

        $this->found = false;
        $this->shouldCache = false;

        list($redirectionId, $url) = $this->checkCache();
        if($this->found){
            return array($redirectionId, $url);
        }

        $this->shouldCache = true;

        list($redirectionId, $url) = $this->checkForFixedRedirect();
        if($this->found){
            return array($redirectionId, $url);
        }

        list($redirectionId, $url) = $this->checkForOpenEndedRedirect();
        if($this->found){
            return array($redirectionId, $url);
        }

		return array(false, '');
	}

	public function checkCache()
	{
		/** @var Zero1_Seoredirects_Model_Redirection_Cache $cachedUrl */
		//have used the private property, as this will have the query string untouched
		$cachedUrl = Mage::getModel('zero1_seo_redirects/redirection_cache')->load($this->originalUrl, 'from_url');
		if($cachedUrl->getId()){
			$this->found = true;
			return array($cachedUrl->getRedirectionId(), $cachedUrl->getToUrl());
		}
		return array(false, '');
	}

	public function checkForFixedRedirect()
	{
		/* @var $redirection Zero1_Seoredirects_Model_Redirection */
		$redirection = Mage::getModel('zero1_seo_redirects/redirection');
		$redirection->loadFixed($this->storeId, $this->getOriginalUrl()->getPath(), $this->getOriginalUrl()->getQuery());

		if($redirection->getId()){
			$this->found = true;
            $redirection->getToUrlInstance()->setScheme($this->getOriginalUrl()->getScheme());
			return array($redirection->getId(), $redirection->getToUrlInstance()->getUrl());
		}
		return array(false, '');
	}

	public function checkForOpenEndedRedirect()
	{
		/* @var $redirectionCollection Zero1_Seoredirects_Model_Resource_Redirection_Collection */
		$redirectionCollection = Mage::getModel('zero1_seo_redirects/redirection')->getCollection();
		$redirectionCollection->addOpenEnded()
			->addFieldToFilter('store_id', 1)
			->addFieldToFilter('from_url_path', $this->getOriginalUrl()->getPath());

		if(!$this->getOriginalUrl()->hasQuery()){
			$redirectionCollection->addFieldToFilter('from_url_query', null);
		}

		if($redirectionCollection->count() == 0){
			return array(false, '');
		}

		/** @var $redirection Zero1_Seoredirects_Model_Redirection */
		$redirection = null;

		if(!$this->getOriginalUrl()->hasQuery()){
			$this->found = true;
			$redirection = $redirectionCollection->getFirstItem();
			return array($redirection->getId(), $redirection->getToUrlInstance()->getUrl());
		}

		$originalRequestAssocQuery = $this->getOriginalUrl()->getAssocQuery();
		$numberOfParams = count($originalRequestAssocQuery);

		$highestMatchedCount = 0;
		/** @var $matchedRedirect Zero1_Seoredirects_Model_Redirection */
		$matchedRedirect = null;

		foreach($redirectionCollection as $redirection){
			$redirectionAssocQuery = $redirection->getFromUrlInstance()->getAssocQuery();
			$matchedQueryParams = count(array_intersect_assoc($originalRequestAssocQuery, $redirectionAssocQuery));

			if($matchedQueryParams > $highestMatchedCount){
				$highestMatchedCount = $matchedQueryParams;
				$matchedRedirect = $redirection;
				if($matchedQueryParams == $numberOfParams){
					break;
				}
			}
		}

		if($highestMatchedCount === 0){
			return array(false, '');
		}
		$this->found = true;

		if($matchedRedirect->shouldPersistQuery()){
			$matchedRedirect->getToUrlInstance()->mergeQuery($originalRequestAssocQuery);
		}
		return array($matchedRedirect->getId(), $matchedRedirect->getToUrlInstance(true)->getUrl());
	}

	/**
	 * @return Zero1_Seoredirects_Model_Url
	 */
	protected function getOriginalUrl()
	{
		return $this->orignalUrlObject;
	}

	protected function incrementHits($redirectionId)
	{
        //Mage::log(__METHOD__.'::START', Zend_Log::DEBUG, 'seo.log', true);
		/* @var $r Zero1_Seoredirects_Model_Redirection */
		$r = Mage::getModel('zero1_seo_redirects/redirection')->load($redirectionId);
        //Mage::log('$r: '.json_encode($r->getData()), Zend_Log::DEBUG, 'seo.log', true);
		if($r->getId()){
			$r->incrementHits();
            //Mage::log('$r: '.json_encode($r->getData()), Zend_Log::DEBUG, 'seo.log', true);
            $r->save();
		}
        //Mage::log(__METHOD__.'::END', Zend_Log::DEBUG, 'seo.log', true);
	}

	protected function cache($redirectionId, $toUrl)
	{
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		/* @var $cached Zero1_Seoredirects_Model_Redirection_Cache */
		$cached = Mage::getModel('zero1_seo_redirects/redirection_cache');
		$cached->setRedirectionId($redirectionId)
			->setFromUrl($this->originalUrl)
			->setToUrl($toUrl)
            ->save();
        //Mage::log(__METHOD__.'::END', Zend_Log::DEBUG, 'seo.log', true);
	}

	/**
	 * @return Zero1_Seoredirects_Helper_Data
	 */
	protected function getHelper()
	{
		return Mage::helper('zero1_seo_redirects');
	}

	protected function log404()
	{
		/* @var $redirection Zero1_Seoredirects_Model_Redirection */
		$redirection = Mage::getModel('zero1_seo_redirects/redirection');
		$redirection->setFromUrlInstance($this->getOriginalUrl())
			->setSource(Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_LOGGED_VALUE)
			->setStatus(0)
			->setFromType(Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE)
			->setHits(1)
			->save();
	}

	protected function redirectTo($url)
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$url);
		die();
	}
}
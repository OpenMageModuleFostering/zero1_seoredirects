<?php
/**
 * Class Zero1_Seoredirects_Model_UrlFactory
 *
 */
class Zero1_Seoredirects_Model_UrlFactory
{
	protected $lockedUrl = null;
	protected $allowedUrls;

    const SCHEME_HTTP = 'http';
    const SCHEME_HTTPS = 'https';

	/**
	 * @param $url
	 * @return Zero1_Seoredirects_Model_Url
	 */
	public function buildUrl($url)
	{
		$matchFound = false;
		$biggestMatch = '';
		$biggestMatchValue = -1;
		if($this->lockedUrl === null){
			foreach($this->allowedUrls as $storeUrl => $storeUlrObj){
				list($found, $matchValue) = $this->match($storeUlrObj, $url);
				if($found){
					$matchFound = true;
					if($matchValue > $biggestMatchValue){
						$biggestMatch = $storeUrl;
						$biggestMatchValue = $matchValue;
					}
				}
			}
		}else{
			list($found, $matchValue) = $this->match($this->lockedUrl, $url);
			if($found){
				$matchFound = true;
				$biggestMatch = $this->lockedUrl->getUrl(true, true, false, false);
			}
		}

		if(!$matchFound){
			/** @var Zero1_Seoredirects_Model_Url $urlObj */
			$urlObj = Mage::getModel('zero1_seo_redirects/url');
			return $urlObj;
		}

		return $this->buildMatchedUrl($this->allowedUrls[$biggestMatch], $url);
	}

	protected function match(Zero1_Seoredirects_Model_Url $storeUrl, $toBeCheckedUrl)
	{
		$parsedToBeCheckedUrl = $this->parseUrl($toBeCheckedUrl);
		if(!isset($parsedToBeCheckedUrl['host'])){
			return array(true, 0);
		}

		if(isset($parsedToBeCheckedUrl['scheme'])){
			if($parsedToBeCheckedUrl['scheme'] != $storeUrl->getScheme()){
				return array(false, 0);
			}
		}

		$toBeCheckedHostAndPath = '';
		if(isset($parsedToBeCheckedUrl['host'])){
			$toBeCheckedHostAndPath .= rtrim($parsedToBeCheckedUrl['host'], '/').'/';
		}
		if(isset($parsedToBeCheckedUrl['path'])){
			$toBeCheckedHostAndPath .= ltrim($parsedToBeCheckedUrl['path'], '/');
		}

		$index = strpos($toBeCheckedHostAndPath, $storeUrl->getUrl(false, true, false, false));
		if($index !== 0){
			return array(false, 0);
		}

		return array(true, strlen($toBeCheckedHostAndPath));
	}

	protected function buildMatchedUrl(Zero1_Seoredirects_Model_Url $storeUrl, $toBeBuiltUrl)
	{
		$parsedToBeBuilt = $this->parseUrl($toBeBuiltUrl);
		/** @var Zero1_Seoredirects_Model_Url $urlObj */
		$urlObj = Mage::getModel('zero1_seo_redirects/url');
		$urlObj->setStoreId($storeUrl->getStoreId());

		if(!isset($parsedToBeBuilt['scheme'])){
			$urlObj->setScheme($storeUrl->getScheme());
		}else{
			$urlObj->setScheme($parsedToBeBuilt['scheme']);
		}

		if(!isset($parsedToBeBuilt['host'])){
			$urlObj->setHost($storeUrl->getHost());
			if(isset($parsedToBeBuilt['path'])){
				$urlObj->setPath($parsedToBeBuilt['path']);
			}
		}else{
			$toBeBuiltHostAndPath = $parsedToBeBuilt['host'];
			if(isset($parsedToBeBuilt['path'])){
				$toBeBuiltHostAndPath .= $parsedToBeBuilt['path'];
			}
			if(strpos($toBeBuiltHostAndPath, $storeUrl->getHost()) !== 0){
				die('Error');
			}
			$length = strlen($storeUrl->getHost());
			$urlObj->setHost(substr($toBeBuiltHostAndPath, 0, $length));
			$urlObj->setPath(substr($toBeBuiltHostAndPath, $length));
		}

		if(isset($parsedToBeBuilt['query'])){
			$urlObj->setQuery($parsedToBeBuilt['query']);
		}
		return $urlObj;
	}

	public function lockToUrl(Zero1_Seoredirects_Model_Url $url)
	{
		$this->lockedUrl = $url;
	}

	public function unlockToUrl()
	{
		$this->lockedUrl = null;
	}

	public function setAllowedUrls(array $allowedUrls)
	{
		$this->allowedUrls = array();
		foreach($allowedUrls as $storeUrl => $storeId){
			$urlObj = $this->buildAllowedUrl($storeUrl, $storeId);
			$this->allowedUrls[$urlObj->getUrl()] = $urlObj;
		}
		return $this;
	}

	public function setAllowedStores(array $storeIds)
	{
		$allowedUrls = array();
		foreach($storeIds as $storeId){
			/** @var Mage_Core_Model_Store $store */
			$store = Mage::app()->getStore($storeId);
			$allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false)] = $storeId;
			$allowedUrls[$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)] = $storeId;
		}
		return $this->setAllowedUrls($allowedUrls);
	}

	protected function buildAllowedUrl($url, $storeId)
	{
		/** @var Zero1_Seoredirects_Model_Url $urlObj */
		$urlObj = Mage::getModel('zero1_seo_redirects/url');
		$parsed = $this->parseUrl($url);
		$urlObj->setScheme($parsed['scheme']);
		$host = '';
		if(isset($parsed['host'])){
			$host .= $parsed['host'];
		}
		if(isset($parsed['path'])){
			$host .= $parsed['path'];
		}
		$urlObj->setHost($host);
		$urlObj->setStoreId($storeId);
		return $urlObj;
	}

	protected function parseUrl($url)
	{
		$parsed = array();

		$index = strpos($url, '://');
		if($index !== false){
            $scheme = substr($url, 0, $index);
            if($this->isValidScheme($scheme)){
                $parsed['scheme'] = $scheme;
                $url = substr($url, ($index + 3));
            }
		}

		$index = strpos($url, '/');
		if($index === false){
			$parsed['path'] = $url;
			return $parsed;
		}

		if($index !== 0){
			$parsed['host'] = substr($url, 0, $index);
			$url = substr($url, $index);
		}

		$index = strpos($url, '?');
		if($index !== false){
			$parsed['path'] = substr($url, 0, $index);
			$url = substr($url, ($index + 1));
			$parsed['query'] = $url;
		}else{
			$parsed['path'] = $url;
		}

		return $parsed;
	}

    public function isValidScheme($scheme)
    {
        switch($scheme){
            case self::SCHEME_HTTP:
            case self::SCHEME_HTTPS:
                return true;
            default:
                return false;
        }
    }
}
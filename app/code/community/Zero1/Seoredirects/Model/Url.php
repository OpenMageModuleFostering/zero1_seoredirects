<?php
/**
 * Class Zero1_Seoredirects_Model_Url
 */
class Zero1_Seoredirects_Model_Url
{
	const SCHEME_HTTPS = 'https';
	const SCHEME_HTTP = 'http';

	//example https://www.mysite.com/uk/catalog/product?a=1&b=2
	protected $scheme; // https
	protected $host;// www.mysite.com/uk/
	protected $path;// catalog/product
	protected $query;// a=1&b=2
	protected $storeId;

	/**
	 * @param bool $scheme
	 * @param bool $host
	 * @param bool $path
	 * @param bool $query
	 * @return string
	 */
	public function getUrl($scheme = true, $host = true, $path = true, $query = true)
	{
		$url = '';
		if($scheme){
			$url .= $this->scheme.'://';
		}
		if($host){
			$url .= $this->host;
		}
		if($path){
			$url .= $this->path;
		}
		if($query){
			if($this->query != ''){
				$url .= '?'.$this->query;
			}
		}
		return $url;
	}

	/**
	 * @param $scheme
	 * @return $this
	 * @throws Exception
	 */
	public function setScheme($scheme)
	{
		if($scheme != self::SCHEME_HTTP && $scheme != self::SCHEME_HTTPS){
			throw new Exception(sprintf('Invalid Scheme: %s', $scheme));
		}
		$this->scheme = $scheme;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getScheme()
	{
		return $this->scheme;
	}

	/**
	 * @param $host
	 * @return $this
	 */
	public function setHost($host)
	{
		$host = rtrim($host, '/').'/';
		$this->host = $host;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @param $path
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->path = ltrim($path, '/');
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->query = $query;
		if($this->storeId){
			$strippedQuery = $this->getHelper()->stripIgnoreables($this->storeId, $this->getAssocQuery());
			if(empty($strippedQuery)){
				$this->query = null;
			}else{
				$this->query = http_build_query($strippedQuery);
			}
		}

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getQuery()
	{
		return $this->query;
	}

	public function hasQuery()
	{
		if($this->query == '' || $this->query == null || $this->query == false){
			return false;
		}else{
			return true;
		}
	}

	public function mergeQuery(array $mergingQueryParams)
	{
		$currentParams = $this->getAssocQuery();
		foreach($mergingQueryParams as $k => $v){
			$currentParams[$k] = $v;
		}
		$this->setQuery(http_build_query($currentParams));
	}

	/**
	 * @param $id
	 * @return $this
	 */
	public function setStoreId($id)
	{
		$this->storeId = $id;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getStoreId()
	{
		return $this->storeId;
	}

	/**
	 * @return Zero1_Seoredirects_Helper_Data
	 */
	protected function getHelper()
	{
		return Mage::helper('zero1_seo_redirects');
	}

	public function getAssocQuery()
	{
		$query = $this->query;
		$urlParams = array();
		if($query == null || $query == ''){
			return $urlParams;
		}
		$query = ltrim($query, '?');

		if(strpos($query, '&') !== false){
			$params = explode('&', $query);
		}else{
			$params = array($query);
		}
		if(empty($params)){
			return $urlParams;
		}
		foreach($params as $pair){
			list($key, $value) = explode('=', $pair);
			$urlParams[$key] = $value;
		}
		return $urlParams;
	}
}
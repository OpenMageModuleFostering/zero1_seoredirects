<?php
class Zero1_Seoredirects_Helper_Urls extends Mage_Core_Helper_Abstract
{
	/**
	 * Parses url and orders query string
	 * @param string $url
	 * @param $storeId
	 * @return array
	 */
	public function parseUrl($url = '', $storeId = null){
		$this->_getHelper()->debug('parseUrl');
		$parsed = parse_url($url);

		//sort out issue where BASE PATH is domain.com/a/b
		$this->stripHostFromPath($parsed, $storeId);

		if(!isset($parsed['query'])){
			return $parsed;
		}
		//sort alphabetically
		$q = explode('&', $parsed['query']);
		$args = array();
		foreach($q as $kvPair){
			list($k, $v) = explode('=', $kvPair);
			$args[$k] = $v;
		}
		ksort($args);

		//strip ignoreables
		$stripped = $this->_getHelper()->stripIgnoreables($storeId, $args);

		if(empty($stripped)){
			unset($parsed['query']);
		}else{
			$parsed['query'] = http_build_query($stripped);
		}

		return $parsed;
	}

	protected function stripHostFromPath(&$parsed, $storeId = null){
		$baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
		if(!isset($parsed['host'])){ return; }

		//remove protocol
		$pos = strpos($baseUrl, $parsed['host']);
		if($pos !== false){
			$baseUrl = substr($baseUrl, $pos);
		}

		$stringLengthDifference = (strlen($baseUrl) - strlen($parsed['host']));
		if($stringLengthDifference > 0){
			$extraPartOfHost = substr($baseUrl, (strlen($baseUrl) - $stringLengthDifference));
			if(strpos($parsed['path'], $extraPartOfHost) === 0){
				$parsed['host'] = $baseUrl;
				$parsed['path'] = substr($parsed['path'], $stringLengthDifference);
			}
		}
		$this->_getHelper()->debug('base url: '.$baseUrl.PHP_EOL.'parsed: '.json_encode($parsed));
	}

    public function getAssocQuery($query){
        $urlParams = array();
        if($query == null || $query == ''){
            return $urlParams;
        }
        if($query[0] == '?'){
            $query = substr($query, 1);
        }
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

	/**
	 * @return Zero1_Seoredirects_Helper_Data
	 */
	protected function _getHelper(){
		return Mage::helper('zero1_seo_redirects');
	}
}
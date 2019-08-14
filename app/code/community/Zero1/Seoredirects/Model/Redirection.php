<?php

/**
 * Class Zero1_Seoredirects_Model_Redirection
 * @method Zero1_Seoredirects_Model_Redirection setHits(int $hits)
 * @method int getHits()
 * @method string getToUrl()
 * @method Zero1_Seoredirects_Model_Redirection setToUrl(string $toUrl)
 * @method Zero1_Seoredirects_Model_Redirection setStoreId(int $storeId)
 * @method Zero1_Seoredirects_Model_Redirection setSource(int $source)
 * @method int getSource()
 * @method Zero1_Seoredirects_Model_Redirection setStatus(int $status)
 * @method Zero1_Seoredirects_Model_Redirection setFromType(int $fromType)
 * @method int getFromType()
 * @method Zero1_Seoredirects_Model_Redirection setFromUrlQuery(string $query)
 * @method string getFromUrlQuery()
 * @method int getPersistQuery()
 * @method Zero1_Seoredirects_Model_Redirection setFromUrlPath(string $path)
 * @method string getFromUrlPath()
 */
class Zero1_Seoredirects_Model_Redirection extends Mage_Core_Model_Abstract
{
    const FROM_TYPE_OPEN_ENDED_QUERY_VALUE = 0;
    const FROM_TYPE_OPEN_ENDED_QUERY_LABEL = 'Open Ended Query';
    const FROM_TYPE_FIXED_QUERY_VALUE = 1;
    const FROM_TYPE_FIXED_QUERY_LABEL = 'Fixed Query';

    const SOURCE_TYPE_LOGGED_VALUE = 0;
    const SOURCE_TYPE_LOGGED_LABEL = 'Logged';
    const SOURCE_TYPE_IMPORT_VALUE = 1;
    const SOURCE_TYPE_IMPORT_LABEL = 'Import';
    const SOURCE_TYPE_MANUAL_VALUE = 2;
    const SOURCE_TYPE_MANUAL_LABEL = 'Manual';

    const REDIRECTION_STATUS_DISABLED_VALUE = 0;
    const REDIRECTION_STATUS_DISABLED_LABEL = 'Disabled';
    const REDIRECTION_STATUS_ENABLED_VALUE = 1;
    const REDIRECTION_STATUS_ENABLED_LABEL = 'Enabled';

	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/redirection');
	}

    protected function _afterLoad(){
        $this->setFromUrl($this->getFromUrlPath().(($this->getFromUrlQuery() != '')? '?'.$this->getFromUrlQuery() : ''));
    }

    /**
     * @param $storeId
     * @param $urlPath
     * @param null $query
     * @return $this|Mage_Core_Model_Abstract
     */
    public function loadFixed($storeId, $urlPath, $query = null)
    {
        $id = $this->_getResource()->loadFixed($storeId, $urlPath, $query);
        if($id === false){
            return $this;
        }else{
            return $this->load($id);
        }
    }

    public function save(){
        if (!$this->_hasModelChanged()) {
            return parent::save();
        }
        $validation = $this->validate();
        if(!$validation['result']){
            /* @var $ex Zero1_Seoredirects_Exception */
            $ex = Mage::exception('Zero1_Seoredirects', implode('<br />', $validation['errors']));
            foreach($validation['errors'] as $er){ $ex->addMessage($er); }
            throw $ex;
        }
        //if its not new and anything other than just hits has changed clear cache for this redirect
        $diff = array_diff_assoc((($this->getOrigData() == null)? array() : $this->getOrigData()), $this->getData());
        if(count($diff) > 1 || !isset($diff['hits'])){
            /* @var $cacheCollection Zero1_Seoredirects_Model_Resource_Redirection_Cache_Collection */
            $cacheCollection = Mage::getModel('zero1_seo_redirects/redirection_cache')->getCollection();
            $cacheCollection->addFieldToFilter('redirection_id', $this->getId());
            $cacheCollection->delete();

            //clear all cached redirects with the same path if the is/was an open ended redirection
            if($this->getFromType() == self::FROM_TYPE_FIXED_QUERY_VALUE || $this->getOrigData('from_type') == self::FROM_TYPE_OPEN_ENDED_QUERY_VALUE){
                $redirectionIds = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
                    ->addFieldToFilter('from_url_path', $this->getFromUrlPath())
                    ->getAllIds();

                if(!empty($redirectionIds)){
                    Mage::getModel('zero1_seo_redirects/redirection_cache')->getCollection()
                        ->addFieldToFilter('redirection_id', array('in'=> array($redirectionIds)))
                        ->delete();
                }
            }
        }
        return parent::save();
    }

    public function validate(){
        $v = array(
            'result' => true,
            'errors' => array(),
        );
        //check urls and order the query strings
        $v = $this->_validateURLs($v);
        //check if from url exists
        $v = $this->_checkFrom($v);
        //check if to url exists
        $v = $this->_checkTo($v);
        //check to and from
        $v = $this->_checkToAndFrom($v);
		$v = $this->_checkPersistQuery($v);

        //TODO check license
        $v = $this->_checkStatus($v);

        return $v;
    }

    protected function _validateURLs($v){
        //do from url
        $url = preg_replace('/\/$/','',$this->getStoreUrl())
            .'/'.preg_replace('/^\//','',$this->getFromUrlPath());

        list($url, $v) = $this->_parseAndValidateUrl($url, $v);

        if($v['result']){
            $this->setFromUrlPath($url['path']);
            if(isset($url['query'])){
                $this->setFromUrlQuery($url['query']);
            }else{
                $this->setFromUrlQuery(null);
            }
        }

        //do to url
        //default logged urls to homepage
        $url = preg_replace('/\/$/','',$this->getStoreUrl()).'/';
        if($this->getSource() != self::SOURCE_TYPE_LOGGED_VALUE){
            $url .= preg_replace('/^\//','',$this->getToUrl());
        }

        list($url, $v) = $this->_parseAndValidateUrl($url, $v);
        if($v['result']){
            $this->setToUrl($url['path'].(isset($url['query'])? '?'.$url['query'] : ''));
        }

        return $v;
    }
    protected function _parseAndValidateUrl($url, $result){
        if(!filter_var($url, FILTER_VALIDATE_URL)){
            $result['result'] = false;
            $result['errors'][] = $this->getHelper()->__('Failed to validate url. '.$url);
        }
        if(!$u = parse_url($url)){
            $result['result'] = false;
            $result['errors'][] = $this->getHelper()->__('Failed to parse url.'.$url);
        }elseif(isset($u['query']) && substr_count($u['query'], '=') == 0){
            $result['result'] = false;
            $result['errors'][] = $this->getHelper()->__('Failed to validate url, could not find \'=\' in query string. '.$url);
        }else{
            $url = $this->_getUrlHelper()->parseUrl($url, $this->getStoreId());
        }
        return array($url, $result);
    }

    protected function _checkFrom($v){
        /* @var $redirector Zero1_Seoredirects_Model_Redirector */
        $redirector = Mage::getModel('zero1_seo_redirects/redirector');
        $result = $redirector->redirect(
            $this->getStoreId(),
            $this->getFromUrl(),
            true
        );

        if($result && $result != $this->getId()){
            $v['result'] = false;
            $v['errors'][] = $this->getHelper()->__('A redirect for this location already exists ('.$result.').');
        }

        return $v;
    }

    protected function _checkTo($v){
        /* @var $redirector Zero1_Seoredirects_Model_Redirector */
        $redirector = Mage::getModel('zero1_seo_redirects/redirector');
        $result = $redirector->redirect(
            $this->getStoreId(),
            $this->getToUrl(),
            true
        );

        if($result){
            $v['result'] = false;
            $v['errors'][] = $this->getHelper()->__('A redirect for the location you are redirecting to already exists ('.$result.').');
        }

        return $v;
    }

    protected function _checkToAndFrom($v){
        if($this->getToUrl() == $this->getFromUrlPath().((!$this->getFromUrlQuery())? '' : '?'.$this->getFromUrlQuery())){
            $v['result'] = false;
            $v['errors'][] = $this->getHelper()->__('A redirect cannot have a to and from that are the same');
        }
        return $v;
    }

	protected function _checkPersistQuery($v){
		if($this->getFromType() != self::FROM_TYPE_OPEN_ENDED_QUERY_VALUE){
			$this->setPersistQuery(false);
		}
		return $v;
	}

    protected function _checkStatus($v){

        if($this->getSource() == self::SOURCE_TYPE_LOGGED_VALUE){
            $this->setStatus(self::REDIRECTION_STATUS_DISABLED_VALUE);
            return $v;
        }

        //TODO figure out a better way to do this
        $cCount = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
            ->addFieldToFilter('status', self::REDIRECTION_STATUS_ENABLED_VALUE);
		if($this->getRedirectionId()){
			$cCount->addFieldToFilter('redirection_id', array('neq' => $this->getRedirectionId()));
		}
		$cCount = $cCount->count();

        $lCount = $this->_getLicenseHelper()->getLicenceLimit();

        if($cCount < $lCount || $lCount == 0){
			$this->setStatus(self::REDIRECTION_STATUS_ENABLED_VALUE);
        }else{
			$this->setStatus(self::REDIRECTION_STATUS_DISABLED_VALUE);
        }

        return $v;
    }

    //helpers
    public function getStoreUrl(){
        if(!$this->getData('store_url')){
            $this->setStoreUrl(Mage::app()->getStore($this->getStoreId())->getBaseUrl());
        }
        return $this->getData('store_url');
    }

    /**
     * @return Zero1_Seoredirects_Helper_Data
     */
    public function getHelper(){
        return Mage::helper('zero1_seo_redirects');
    }

    /**
     * @return Zero1_Seoredirects_Helper_Urls
     */
    protected function _getUrlHelper(){
        return Mage::helper('zero1_seo_redirects/urls');
    }

    /**
     * @return Zero1_Seoredirects_Helper_License
     */
    protected function _getLicenseHelper(){
        return Mage::helper('zero1_seo_redirects/license');
    }


	public function incrementHits(){
		return $this->setHits($this->getHits() + 1);
	}

    public function isEnabled(){
        return (bool)$this->getStatus();
    }

    public function shouldPersistQuery(){
        return (bool)$this->getPersistQuery();
    }

	public function setPersistQuery($persist = false){
		$this->setData('persist_query', (bool)$persist);
		return $this;
	}

    public function getFromUrl(){
        if(!$this->getData('from_url')){
            $storeUrl = parse_url($this->getStoreUrl());
            $this->setFromUrl(
                $storeUrl['scheme'].'://'.
                preg_replace('/\/$/','',$storeUrl['host']).'/'.
                preg_replace('/^\//','',$this->getFromUrlPath()).
                ((!$this->getFromUrlQuery())? '' : '?'.$this->getFromUrlQuery())
            );
        }
        return $this->getData('from_url');
    }
}
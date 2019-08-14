<?php

/**
 * Class Zero1_Seoredirects_Model_Redirection
 * @method Zero1_Seoredirects_Model_Redirection setHits(int $hits)
 * @method int getHits()
 * @method string getToUrl()
 * @method Zero1_Seoredirects_Model_Redirection setToUrl(string $toUrl)
 * @method Zero1_Seoredirects_Model_Redirection setStoreId(int $storeId)
 * @method int getStoreId()
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

    protected $fromUrlInstance;
    protected $toUrlInstance;
    protected $validator;

    protected $urlFactory;

    protected function _construct()
    {
        $this->_init('zero1_seo_redirects/redirection');
    }

    /**
     * @return Zero1_Seoredirects_Model_RedirectionValidator
     */
    protected function getValidator()
    {
        if(!$this->validator){
            $this->validator = Mage::getModel('zero1_seo_redirects/redirectionValidator');
        }
        return $this->validator;
    }

    protected function _afterLoad()
    {
        //todo remove?
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

    public function save()
    {
        if (!$this->_hasModelChanged()) {
            return parent::save();
        }

        $this->setFromUrlPath(ltrim($this->getFromUrlPath(), '/'));
        $this->setToUrl(ltrim($this->getToUrl(), '/'));

        $validation = $this->getValidator()->validate($this);
        $this->checkLicenseState();
        if(!$validation['result']){
            /* @var $ex Zero1_Seoredirects_Exception */
            $ex = Mage::exception('Zero1_Seoredirects', implode('<br />', $validation['errors']));
            foreach($validation['errors'] as $er){ $ex->addMessage($er); }
            throw $ex;
        }
        //if its not new and anything other than just hits has changed clear cache for this redirect
        $diff = array_diff_assoc((($this->getOrigData() == null)? array() : $this->getOrigData()), $this->getData());
        // fix to get around persist query issue
        if(isset($diff['persist_query']) &&
            ((bool)$this->getOrigData('persist_data') == ((bool)$this->getData('persist_query')))
        ){
            unset($diff['persist_query']);
        }

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

    protected function checkLicenseState()
    {
        if($this->getSource() == self::SOURCE_TYPE_LOGGED_VALUE){
            $this->setStatus(self::REDIRECTION_STATUS_DISABLED_VALUE);
            return;
        }

        //TODO figure out a better way to do this
        $cCount = Mage::getModel('zero1_seo_redirects/redirection')->getCollection()
            ->addFieldToFilter('status', self::REDIRECTION_STATUS_ENABLED_VALUE);
        if($this->getRedirectionId()){
            $cCount->addFieldToFilter('redirection_id', array('neq' => $this->getRedirectionId()));
        }
        $cCount = $cCount->count();

        $lCount = $this->getLicenseHelper()->getLicenceLimit();

        if($cCount < $lCount || $lCount == 0){
            $this->setStatus(self::REDIRECTION_STATUS_ENABLED_VALUE);
        }else{
            $this->setStatus(self::REDIRECTION_STATUS_DISABLED_VALUE);
        }
    }

    //helpers
    public function getStoreUrl()
    {
        if(!$this->getData('store_url')){
            $this->setStoreUrl(Mage::app()->getStore($this->getStoreId())->getBaseUrl());
        }
        return $this->getData('store_url');
    }

    /**
     * @return Zero1_Seoredirects_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('zero1_seo_redirects');
    }

    /**
     * @return Zero1_Seoredirects_Helper_Urls
     */
    protected function getUrlHelper(){
        return Mage::helper('zero1_seo_redirects/urls');
    }

    /**
     * @return Zero1_Seoredirects_Helper_License
     */
    protected function getLicenseHelper()
    {
        return Mage::helper('zero1_seo_redirects/license');
    }

    public function incrementHits()
    {
        return $this->setHits($this->getHits() + 1);
    }

    public function isEnabled()
    {
        return (bool)$this->getStatus();
    }

    public function shouldPersistQuery()
    {
        return (bool)$this->getPersistQuery();
    }

    public function setPersistQuery($persist = false)
    {
        $this->setData('persist_query', (bool)$persist);
        return $this;
    }

    /**
     * @param Zero1_Seoredirects_Model_Url $url
     * @return Zero1_Seoredirects_Model_Redirection
     */
    public function setFromUrlInstance(Zero1_Seoredirects_Model_Url $url)
    {
        $this->fromUrlInstance = $url;
        $this->setFromUrlPath($url->getPath());
        $this->setFromUrlQuery($url->getQuery());
        $this->setStoreId($url->getStoreId());
        return $this;
    }

    /**
     * @return Zero1_Seoredirects_Model_Url
     */
    public function getFromUrlInstance($force = false)
    {
        if(!$this->fromUrlInstance || $force){
            $url = $this->getFromUrlPath().(($this->getFromUrlQuery())? '?'.$this->getFromUrlQuery() : '');
            $this->fromUrlInstance = $this->getUrlFactory()
                ->setAllowedStores(array($this->getStoreId()))
                ->buildUrl($this->getToUrl($url));
        }
        return $this->fromUrlInstance;
    }

    /**
     * @param Zero1_Seoredirects_Model_Url $url
     * @return Zero1_Seoredirects_Model_Redirection
     */
    public function setToUrlInstance(Zero1_Seoredirects_Model_Url $url)
    {
        $this->toUrlInstance = $url;
        $this->setToUrl('/'.$url->getUrl(false, false, true, true));
        $this->setStoreId($url->getStoreId());
        return $this;
    }

    /**
     * @return Zero1_Seoredirects_Model_UrlFactory
     */
    protected function getUrlFactory()
    {
        if(!$this->urlFactory){
            $this->urlFactory = Mage::getModel('zero1_seo_redirects/urlFactory');
        }
        return $this->urlFactory;
    }

    /**
     * @param bool $force
     * @return Zero1_Seoredirects_Model_Url
     */
    public function getToUrlInstance($force = false)
    {
        if(!$this->toUrlInstance || $force){
            $this->toUrlInstance = $this->getUrlFactory()
                ->setAllowedStores(array($this->getStoreId()))
                ->buildUrl('/'.ltrim($this->getToUrl(), '/'));
        }
        return $this->toUrlInstance;
    }

}
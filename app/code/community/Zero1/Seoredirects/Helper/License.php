<?php
class Zero1_Seoredirects_Helper_License extends Mage_Core_Helper_Abstract
{	
	private $_license_required_for_community	= true;
	private $_license_required_for_enterprise	= true;
    protected $_licenceLimit = null;

    private $licenseData = null;

    const DEFAULT_LIMIT = 50;
		
	public function isEnterprise()
	{				
		if(method_exists('Mage', 'getEdition'))
		{
			// Mage::getEdition() exists so it is EE 1.12+ or CE 1.7+
			if(Mage::getEdition() == Mage::EDITION_ENTERPRISE)
				return true;
		} else {
			// Fallback on the old version number lookup
			$version = Mage::getVersionInfo();
			 
			if($version['major'] == 1 && $version['minor'] >= 7)
				return true;
		}
		
		return false;
	}
	
	public function isValid($data)
	{
		if($this->isEnterprise())
		{
			if($this->_license_required_for_enterprise && !isset($data['enterprise']))
				return false;
		} else {
			if($this->_license_required_for_community && !isset($data['community']))
				return false;
		}

        if(!isset($data['limit'])){
            return false;
        }
		
		return true;
	}
	
    public function getData()
    {
        if($this->licenseData){
            return $this->licenseData;
        }
        $this->buildDefaultData();

		$store = Mage::app()->getStore(0);
		
    	$data			= array();
    	$module_name	= preg_replace('/^Zero1_([^_]*)_Helper_License$/si', '$1', get_class($this));
    	$module_name	= strtolower($module_name);
    	$serial			= base64_decode($store->getConfig($module_name.'/settings/serial'));
    	$url			= $store->getConfig('web/unsecure/base_url');

        if(!$serial || !$url){
            return $this->licenseData;
        }

        //try using legacy method full base url
        $data = $this->decrypt($module_name, $url, $serial);
        if($this->isValid($data)){
            $this->licenseData = $data;
            return $this->licenseData;
        }

        //try using new method domain only
        $url = parse_url($url, PHP_URL_HOST);
        $data = $this->decrypt($module_name, $url, $serial);
        if($this->isValid($data)){
            $this->licenseData = $data;
            return $this->licenseData;
        }

        //nothing is valid
        return $this->licenseData;
    }

    protected function decrypt($moduleName, $url, $serial)
    {
        $hash = hash('sha256', '$4$W8DgMGQZ$Twn84iicE6FQo7wCrxnL4Aow5/w$'.$moduleName.$url, true);
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $data = @unserialize(mcrypt_decrypt(MCRYPT_BLOWFISH, $hash, $serial, MCRYPT_MODE_ECB, $iv));
        $data = (!is_array($data)) ? array() : $data;
        return $data;
    }

    protected function buildDefaultData()
    {
        $this->licenseData = array();
        if($this->isEnterprise()){
            $this->licenseData['enterprise'] = 1;
        }else{
            $this->licenseData['community'] = 1;
        }
        $this->licenseData['limit'] = self::DEFAULT_LIMIT;
    }
    
    public function getRequestURL($store = null)
    {
		$store = ($store === null) ? Mage::app()->getStore() : $store;
		
    	$params					= array();
    	$params['url']			= $store->getConfig('web/unsecure/base_url');
    	$params['enterprise']	= ($this->isEnterprise()) ? '1' : '0';
    	
    	return 'http://www.zero1.co.uk/licence/index.php?'.http_build_query($params);
    }

    public function getLicenceLimit()
    {
        $data = $this->getData();
        return $data['limit'];
    }
}
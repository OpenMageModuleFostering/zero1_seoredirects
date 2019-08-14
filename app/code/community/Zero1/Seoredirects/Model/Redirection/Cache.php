<?php

/**
 * Class Zero1_Seoredirects_Model_Redirection_Cache
 * @method string getFromUrl()
 * @method Zero1_Seoredirects_Model_Redirection_Cache setFromUrl(string $fromUrl)
 * @method string getToUrl()
 * @method Zero1_Seoredirects_Model_Redirection_Cache setToUrl(string $toUrl)
 * @method int getRedirectionId()
 * @method Zero1_Seoredirects_Model_Redirection_Cache setRedirectionId(int $redirectionId)
 */
class Zero1_Seoredirects_Model_Redirection_Cache extends Mage_Core_Model_Abstract
{
	protected function _construct(){
		$this->_init('zero1_seo_redirects/redirection_cache');
	}
}
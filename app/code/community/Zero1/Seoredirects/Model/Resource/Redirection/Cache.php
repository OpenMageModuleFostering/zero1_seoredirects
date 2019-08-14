<?php

/**
 * Class Zero1_Seoredirects_Model_Resource_Redirection_Cache
 */
class Zero1_Seoredirects_Model_Resource_Redirection_Cache extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/redirection_cache', 'redirection_cache_id');
	}
}
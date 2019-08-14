<?php
class Zero1_Seoredirects_Model_Resource_ImportLog extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/importLog', 'id');
	}
}
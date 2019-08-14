<?php
class Zero1_Seoredirects_Model_Resource_Redirection extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		$this->_init('seoredirects/redirection', 'entity_id');
	}
}
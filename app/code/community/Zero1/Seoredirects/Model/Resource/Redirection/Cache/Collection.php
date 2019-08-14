<?php
class Zero1_Seoredirects_Model_Resource_Redirection_Cache_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/redirection_cache');
	}

	public function delete(){
		$this->load();
		foreach($this->_items as $r){
			$r->delete();
		}
	}
}
<?php
class Zero1_Seoredirects_Model_Resource_Redirection_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/redirection');
	}

	public function delete(){
		$this->load();
		foreach($this->_items as $r){
			$r->delete();
		}
	}

    public function addOpenEnded(){
        return $this->addFieldToFilter('from_type', Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_VALUE);
    }
}
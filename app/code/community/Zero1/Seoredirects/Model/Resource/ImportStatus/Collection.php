<?php
class Zero1_Seoredirects_Model_Resource_ImportStatus_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/importStatus');
	}

	public function delete()
	{
		$this->load();
		foreach($this->_items as $r){
			$r->delete();
		}
	}

    public function deleteAll()
    {
        $this->getConnection()->delete($this->getMainTable());
    }
}
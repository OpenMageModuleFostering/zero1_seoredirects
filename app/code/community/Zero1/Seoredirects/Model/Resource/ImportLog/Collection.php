<?php
class Zero1_Seoredirects_Model_Resource_ImportLog_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/importLog');
	}

	public function delete()
	{
		$this->load();
        /** @var Zero1_Seoredirects_Model_ImportLog $r */
        foreach($this->_items as $r){
			$r->delete();
		}
        $this->clear();
        $this->load();
	}

    public function deleteAll()
    {
        $this->getConnection()->delete($this->getMainTable());
    }
}
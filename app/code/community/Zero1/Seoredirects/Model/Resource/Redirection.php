<?php
class Zero1_Seoredirects_Model_Resource_Redirection extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/redirection', 'redirection_id');
	}


    public function loadFixed($storeId = null, $urlPath = '', $query = null)
    {
        if(is_null($storeId)){
            return null;
        }

        $read = $this->_getReadAdapter();
        $select = $read->select('id')
            ->from($this->getMainTable())
            ->where('from_url_path = ?', $urlPath)
            ->where('store_id = ?', $storeId)
            ->where('from_type = ?', Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE)
            ->where('from_url_query = ?', $query);

        return $read->fetchOne($select);
    }
}
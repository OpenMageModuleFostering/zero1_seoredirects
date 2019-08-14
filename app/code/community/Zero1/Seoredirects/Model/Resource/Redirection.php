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

    /**
     * Prepare data for passed table
     *
     * @param Varien_Object $object
     * @param string $table
     * @return array
     */
    protected function _prepareDataForTable(Varien_Object $object, $table)
    {
        $data = array();
        $fields = $this->_getWriteAdapter()->describeTable($table);
        foreach (array_keys($fields) as $field) {
            if ($object->hasData($field)) {
                $fieldValue = $object->getData($field);
                if ($fieldValue instanceof Zend_Db_Expr) {
                    $data[$field] = $fieldValue;
                } else {
                    if (null !== $fieldValue) {
                        $fieldValue   = $this->_prepareTableValueForSave($fieldValue, $fields[$field]['DATA_TYPE']);
                        $data[$field] = $this->_getWriteAdapter()->prepareColumnValue($fields[$field], $fieldValue);
                    } else if (!empty($fields[$field]['NULLABLE'])) {
                        $data[$field] = null;
                    }
                }
            }
        }
        $data['updated_at'] = null;
        return $data;
    }
}
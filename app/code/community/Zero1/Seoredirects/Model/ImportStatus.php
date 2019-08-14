<?php
/**
 * Class Zero1_Seoredirects_Model_ImportStatus
 *
 * @method Zero1_Seoredirects_Model_ImportStatus setScope(string $scope)
 * @method string getScope()
 * @method Zero1_Seoredirects_Model_ImportStatus setScopeId(int $scopeId)
 * @method int getScopeId()
 * @method Zero1_Seoredirects_Model_ImportStatus setToBeImported(int $toBeImported)
 * @method int getToBeImported()
 * @method Zero1_Seoredirects_Model_ImportStatus setImported(int $imported)
 * @method int getImported()
 * @method int getCreatedAt()
 * @method int getUpdatedAt()
 */
class Zero1_Seoredirects_Model_ImportStatus extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/importStatus');
	}

    protected function _beforeSave()
    {
        if(!$this->getCreatedAt()){
            $this->setCreatedAt(
                date('Y-m-d H:i:s', Mage::getSingleton('core/date')->timestamp(time()))
            );
        }
        parent::_beforeSave();
    }
}
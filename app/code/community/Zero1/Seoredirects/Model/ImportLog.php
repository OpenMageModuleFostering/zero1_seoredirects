<?php
/**
 * Class Zero1_Seoredirects_Model_ImportLog
 *
 * @method Zero1_Seoredirects_Model_ImportLog setScope(string $scope)
 * @method string getScope()
 * @method Zero1_Seoredirects_Model_ImportLog setScopeId(int $scopeId)
 * @method int getScopeId()
 * @method Zero1_Seoredirects_Model_ImportLog setSeverity(int $severity)
 * @method int getSeverity()
 * @method Zero1_Seoredirects_Model_ImportLog setLineNumber($lineNumber)
 * @method int|null getLineNumber()
 * @method Zero1_Seoredirects_Model_ImportLog setMessage(string $message)
 * @method string getMessage()
 * @method int getCreatedAt()
 */
class Zero1_Seoredirects_Model_ImportLog extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('zero1_seo_redirects/importLog');
	}
}
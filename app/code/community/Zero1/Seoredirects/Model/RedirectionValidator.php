<?php
class Zero1_Seoredirects_Model_RedirectionValidator
{
	protected $redirecter;

	/**
	 * @param Zero1_Seoredirects_Model_Redirection $redirection
	 * @return array
	 */
	public function validate(Zero1_Seoredirects_Model_Redirection &$redirection)
	{
        //Mage::log(__METHOD__.'START', Zend_Log::DEBUG, 'seo.log', true);
		$this->redirection = $redirection;
		$validationResult = array(
			'result' => true,
			'errors' => array(),
		);
		$this->validatePersistQuery($validationResult, $redirection);
		$this->validateTo($validationResult, $redirection);
		$this->validateFrom($validationResult, $redirection);
		$this->validateToAndFrom($validationResult, $redirection);
		return $validationResult;
	}

	protected function validatePersistQuery($validationResult, Zero1_Seoredirects_Model_Redirection &$redirection)
	{
        //Mage::log(__METHOD__.'START', Zend_Log::DEBUG, 'seo.log', true);
		if($redirection->getFromType() != $redirection::FROM_TYPE_OPEN_ENDED_QUERY_VALUE){
			$redirection->setPersistQuery(false);
		}
		return $validationResult;
	}

	protected function validateTo($validationResult, Zero1_Seoredirects_Model_Redirection &$redirection)
	{
        //Mage::log(__METHOD__.'::START', Zend_Log::DEBUG, 'seo.log', true);
        //Mage::log('url: '.$redirection->getToUrlInstance(true)->getUrl(), Zend_Log::DEBUG, 'seo.log', true);
		$redirecter = $this->getRedirecter();
        list($id, $url) = $redirecter->getRedirectId(
			$redirection->getStoreId(),
			$redirection->getToUrlInstance()->getUrl(),
			true
		);
		if($id){
			$validationResult['result'] = false;
			$validationResult['errors'][] = $this->getHelper()->__('A redirect for the location you are redirecting to already exists ('.$url.').');
		}
		return $validationResult;
	}

	protected function validateFrom($validationResult, Zero1_Seoredirects_Model_Redirection &$redirection)
	{
        //Mage::log(__METHOD__.'START', Zend_Log::DEBUG, 'seo.log', true);
		$redirecter = $this->getRedirecter();
        list($id, $url) = $redirecter->getRedirectId(
			$redirection->getStoreId(),
			$redirection->getFromUrlPath(),
			true
		);

		if($id && $id != $redirection->getId()){
			$validationResult['result'] = false;
			$validationResult['errors'][] = $this->getHelper()->__('A redirect for this location already exists ('.$url.').');
		}
		return $validationResult;
	}

	protected function validateToAndFrom($validationResult, Zero1_Seoredirects_Model_Redirection &$redirection)
	{
        //Mage::log(__METHOD__.'START', Zend_Log::DEBUG, 'seo.log', true);
		if($redirection->getToUrl() == $redirection->getFromUrlInstance()->getUrl(false, false, true, true)){
			$validationResult['result'] = false;
			$validationResult['errors'][] = $this->getHelper()->__('A redirect cannot have a to and from that are the same');
		}
		return $validationResult;
	}

	/**
	 * @return Zero1_Seoredirects_Model_Redirecter
	 */
	protected function getRedirecter()
	{
        //Mage::log(__METHOD__.'START', Zend_Log::DEBUG, 'seo.log', true);
		if(!$this->redirecter){
			$this->redirecter = Mage::getModel('zero1_seo_redirects/redirecter');
		}
		return $this->redirecter;
	}

	/**
	 * @return Zero1_Seoredirects_Helper_Data
	 */
	protected function getHelper()
	{
		return Mage::helper('zero1_seo_redirects');
	}
}
<?php
/**
 * Class Zero1_Seoredirects_Model_Observer
 */
class Zero1_Seoredirects_Model_Observer
{
    protected function checkForRedirection($observer)
	{
        // There is a valid route, nothing to do
		if(Mage::app()->getRequest()->getActionName() != 'noRoute'){
            return;
        }

        //Mage::log('registry: '.Mage::registry('zero1_seo_redirects'), Zend_Log::DEBUG, 'seo.log', true);
        if(Mage::registry('zero1_seo_redirects')){
            return;
        }
        /* @var $redirector Zero1_Seoredirects_Model_Redirecter */
        $redirector = Mage::getModel('zero1_seo_redirects/redirecter');

		//Mage::log('core/url: '.Mage::helper('core/url')->getCurrentUrl(), Zend_Log::DEBUG, 'seo.log', true);

        $redirection = $redirector->redirect(
                Mage::app()->getStore()->getId(),
                Mage::helper('core/url')->getCurrentUrl()
            );

        if($redirection){
            Mage::register('zero1_seo_redirects', $redirection->getId());
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controller_front_send_response_before(Varien_Event_Observer $observer)
	{
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		$this->checkForRedirection($observer);
	}

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controller_front_send_response_after(Varien_Event_Observer $observer)
	{
        //Mage::log(__METHOD__, Zend_Log::DEBUG, 'seo.log', true);
		$this->checkForRedirection($observer);
	}
}
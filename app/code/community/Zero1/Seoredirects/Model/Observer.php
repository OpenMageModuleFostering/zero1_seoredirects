<?php
/**
 * Class Zero1_Seoredirects_Model_Observer
 */
class Zero1_Seoredirects_Model_Observer
{
    protected function checkForRedirection($observer)
	{
        // There is a valid route, nothing to do
		if(Mage::app()->getRequest()->getActionName() != 'noRoute'){ return; }

        /* @var $redirector Zero1_Seoredirects_Model_Redirector */
        $redirector = Mage::getModel('zero1_seo_redirects/redirector');

        $result = $redirector->redirect(
                Mage::app()->getStore()->getId(),
                Mage::helper('core/url')->getCurrentUrl()
            );

        if(Mage::helper('zero1_seo_redirects')->canDebug()){
			Mage::helper('zero1_seo_redirects')->debug('Result: '.json_encode($result));
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controller_front_send_response_before(Varien_Event_Observer $observer)
	{
		$this->checkForRedirection($observer);
	}

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controller_front_send_response_after(Varien_Event_Observer $observer)
	{
		$this->checkForRedirection($observer);
	}

    public function cronImport(){

    }
}
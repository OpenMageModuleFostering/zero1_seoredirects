<?php
class Zero1_Seoredirects_Block_Adminhtml_Import_StatusSummary extends Mage_Adminhtml_Block_Template{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zero1/seoredirects/import/status_summary.phtml');
    }

    /**
     * @return Zero1_Seoredirects_Model_Resource_ImportStatus_Collection
     */
    public function getStatusCollection()
    {
        return Mage::getModel('zero1_seo_redirects/importStatus')->getCollection();
    }
}
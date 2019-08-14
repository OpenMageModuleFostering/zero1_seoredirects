<?php
class Zero1_Seoredirects_Block_Adminhtml_Import_Status extends Mage_Adminhtml_Block_Template{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zero1/seoredirects/import/status.phtml');
    }

    public function getStatus()
    {
        return Zero1_Seoredirects_Model_Importer::getStatus();
    }
}
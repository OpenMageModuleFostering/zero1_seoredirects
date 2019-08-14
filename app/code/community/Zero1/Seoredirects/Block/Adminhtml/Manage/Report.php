<?php
class Zero1_Seoredirects_Block_Adminhtml_Manage_Report extends Mage_Adminhtml_Block_Template{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zero1/seoredirects/manage/report.phtml');
    }

    public function getLicenseCounts(){
		/* @var $redirection Zero1_Seoredirects_Model_Redirection */
		$redirection = Mage::getModel('zero1_seo_redirects/redirection');
		$resource = $redirection->getResource();
		$con = $resource->getReadConnection();

		$select = $con->select();
		$select->from('core_store as store_table', array(
			'store_table.store_id',
			'store_table.name',
			new Zend_Db_Expr('IF(store_table.store_id = 0,
        (SELECT
            COUNT(*)
        FROM
            zero1_seoredirects_redirection
        where
            status = 1),
        (SELECT
            COUNT(*)
        FROM
            zero1_seoredirects_redirection
        where
            store_id = store_table.store_id and status = 1)) as count')));

        return $res = $con->fetchAssoc($select);
    }

    public function getLicenseLimit(){
        return Mage::helper('zero1_seo_redirects/license')->getLicenceLimit();
    }

}
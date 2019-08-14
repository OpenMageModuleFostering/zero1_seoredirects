<?php
class Zero1_Seoredirects_Block_Adminhtml_Manage_Grid_Renderer_Url extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text {

    /**
     * Render column for export
     *
     * @param Varien_Object $row
     * @return string
     */
    public function renderExport(Varien_Object $row)
    {
       return
           preg_replace('/\/$/','',Mage::app()->getStore($row->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false))
           .'/'.
           preg_replace('/^\//','',$row->getData($this->getColumn()->getIndex()));
    }
}
<?php
class Zero1_Seoredirects_Block_Adminhtml_Manage_Grid_Renderer_Options extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Options {

    /**
    * Render column for export
    *
    * @param Varien_Object $row
    * @return string
    */
    public function renderExport(Varien_Object $row)
    {
        return $row->getData($this->getColumn()->getIndex());
    }
}
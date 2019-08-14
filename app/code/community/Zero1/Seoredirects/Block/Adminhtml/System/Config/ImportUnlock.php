<?php
class Zero1_Seoredirects_Block_Adminhtml_System_Config_ImportUnlock
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('zero1/seoredirects/system/config/import_unlock.phtml');
    }

    /**
     * Remove scope label
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getUnlockUrl()
    {
        return Mage::getSingleton('adminhtml/url')->getUrl('*/seoredirects_import/unlock');
    }

    /**
     * Generate synchronize button html
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'unlock_button',
                'label'     => $this->helper('adminhtml')->__('Unlock Import'),
                'onclick'   => 'javascript:unlockImport(); return false;'
            ));

        return $button->toHtml();
    }

    public function getTrueImportStatus()
    {
        $result = exec('ps aux | grep Seoredirects/scripts/import.php | grep -v grep');

        if($result){
            return Zero1_Seoredirects_Model_Importer::RUNLOCK_RUNNING;
        }else{
            return Zero1_Seoredirects_Model_Importer::RUNLOCK_NOT_RUNNING;
        }
    }

    public function getDBImportStatus()
    {
        return Zero1_Seoredirects_Model_Importer::getStatus();
    }
}

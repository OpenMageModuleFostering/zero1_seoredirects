<?php

/**
 * System config form block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Zero1_Seoredirects_Block_Adminhtml_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
    /**
     * Enter description here...
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        $array = parent::_getAdditionalElementTypes();
        $array['zero1_seo_redirects_file'] = Mage::getConfig()->getBlockClassName('zero1_seo_redirects/adminhtml_system_config_form_field_file');
        return $array;
    }
}

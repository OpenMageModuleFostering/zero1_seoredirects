<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * File config field renderer
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Zero1_Seoredirects_Block_Adminhtml_System_Config_Form_Field_File extends Varien_Data_Form_Element_File
{
    /* @var $file Zero1_Seoredirects_Model_File */
    private $file;
    /**
     * Get element html
     *
     * @return string
     */
    public function getElementHtml()
    {
        $website = Mage::app()->getRequest()->getParam('website', null);
        if($website !== null){
            $website = Mage::app()->getWebsite($website)->getId();
        }
        $store = Mage::app()->getRequest()->getParam('store', null);
        if($store !== null){
            $store = Mage::app()->getstore($store)->getId();
        }
        $this->file  = Mage::helper('zero1_seo_redirects/files')->getFile($store, $website);


        if($this->file->getFileName() !== null){
            $html = $this->getFileLinkHTML();
        }else{
            $html = $this->getTemplateFileLinkHTML();
        }
        $html .= $this->getNewFileHTML();
        $html .= $this->getButtonsHTML();

        $this->setData('onchange', 'update'.$this->getHtmlId().'value(this.value)');
        $this->setData('style', 'display: none;');

        $html .= parent::getElementHtml();
        $html .='<script>
        function update'.$this->getHtmlId().'value(value){
            document.getElementById(\''.$this->getHtmlId().'_new_file_label\').innerHTML = \'New File: \'+value;
            document.getElementById(\''.$this->getHtmlId().'_new_file_container\').style.display = \'block\';
            document.getElementById(\''.$this->getHtmlId().'_delete\').disabled = \'disabled\';
            document.getElementById(\''.$this->getHtmlId().'_change_button\').disabled = \'disabled\';
        }
        function '.$this->getHtmlId().'delete_onchange(element){
            if(element.checked){
                document.getElementById(\''.$this->getHtmlId().'_change_button\').disabled = \'disabled\';
            }else{
                document.getElementById(\''.$this->getHtmlId().'_change_button\').removeAttribute(\'disabled\');
            }
        }
        </script>';
        return $html;
    }

    protected function getButtonsHTML(){
        $html = '<div>';
        $html .= $this->getChangeButtonHTML();
        if($this->file->getFileName() !== null){
            $html .= $this->getDeleteCheckboxHTML();
        }
        $html .= '<div style="clear: both;"></div>';
        $html .= '</div>';
        return $html;
    }

    protected function getDeleteCheckboxHTML()
    {
        $html = '';
        $label = Mage::helper('adminhtml')->__('Delete File');
        $html .= '<div style="display: inline-block; float: right; margin-right: 30px;">';
        $html .= '<input type="checkbox" name="'.parent::getName().'[delete]" value="1" class="checkbox" id="'.$this->getHtmlId().'_delete" onchange="'.$this->getHtmlId().'delete_onchange(this);"/>';
        $html .= '<label for="'.$this->getHtmlId().'_delete"'.'> '.$label.'</label>';
        $html .= '</div>';
        return $html;
    }

    protected function getChangeButtonHTML(){
        if($this->file->getFileName() != null){
            $changeButtonText = 'Change File...';
        }else{
            $changeButtonText = 'Add File...';
        }
        $html = '<input type="button" id="'.$this->getHtmlId().'_change_button" value="'.$changeButtonText.'" onclick="document.getElementById(\''.$this->getHtmlId().'\').click();" />';
        return $html;
    }

    protected function getNewFileHTML(){
        $html = '<div id="'.$this->getHtmlId().'_new_file_container" style="display:none;">';
        $html .= '<div id="'.$this->getHtmlId().'_new_file_label">New File: </div>';
        $html .= '</div>';
        return $html;
    }

    protected function getFileLinkHTML(){
        return 'Current File: <a href="'.$this->file->getExternalPath().'">'.$this->file->getFileName().'</a><br />';
    }

    protected function getTemplateFileLinkHTML(){
        /* @var $templateFile Zero1_Seoredirects_Model_File */
        $templateFile = Mage::helper('zero1_seo_redirects/files')->getTemplateFile();
        return 'No File Set: <a href="'.$templateFile->getExternalPath().'">'.$templateFile->getFileName().'</a><br />';
    }
}

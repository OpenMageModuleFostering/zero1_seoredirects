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


class Zero1_Seoredirects_Model_Adminhtml_System_Config_Backend_Number extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $fieldConfig = $this->getFieldConfig();
        $value = $this->getValue();
        if($value == null){
            Mage::getSingleton('core/session')->addNotice($fieldConfig->label.': No input, defaulted to 100.');
            $this->setValue('100');
        }else{
            if(!is_numeric($value)){
                Mage::throwException($fieldConfig->label.': Entry must be numberic.');
            }
            if($value < 0){
                Mage::throwException('Entry must be greater than 0');
            }
        }
        return parent::_beforeSave();
    }

    protected function _afterSave(){
        $value = $this->getValue();
        if($value == 0){
            Mage::getSingleton('core/session')->addNotice('Now logging all 404\'s requests');
        }
        return parent::_afterSave();
    }
}

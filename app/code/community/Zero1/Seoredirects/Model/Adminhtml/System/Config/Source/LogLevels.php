<?php
class Zero1_Seoredirects_Model_Adminhtml_System_Config_Source_LogLevels
{

    /**
     * Options getter
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => Zend_Log::WARN, 'label'=>Mage::helper('adminhtml')->__('Warning')),
            array('value' => Zend_Log::NOTICE, 'label'=>Mage::helper('adminhtml')->__('Notice')),
			array('value' => Zend_Log::INFO, 'label'=>Mage::helper('adminhtml')->__('Info')),
			array('value' => Zend_Log::DEBUG, 'label'=>Mage::helper('adminhtml')->__('Debug')),
        );
    }

    /**
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        return array(
			Zend_Log::WARN => Mage::helper('adminhtml')->__('Warning'),
            Zend_Log::NOTICE => Mage::helper('adminhtml')->__('Notice'),
			Zend_Log::INFO => Mage::helper('adminhtml')->__('Info'),
			Zend_Log::DEBUG => Mage::helper('adminhtml')->__('Debug'),
        );
    }

}

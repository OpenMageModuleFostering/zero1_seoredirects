<?php
class Zero1_Seoredirects_Block_Adminhtml_Import_Report extends Mage_Adminhtml_Block_Template{

    protected $_template = 'zero1/seoredirects/import/report.phtml';

    public function getSeverityLabel($sev){
        switch($sev){
            case Zend_Log::DEBUG:
                return 'Debug';
                break;
            case Zend_Log::INFO:
                return 'Info';
                break;
            case Zend_Log::WARN:
                return 'Warning';
                break;
            case Zend_Log::NOTICE:
                return 'Notice';
                break;
            default:
                return 'Not setup: '.$sev;
        }
    }

    public function getSeverityColor($sev){
        switch($sev){
            case Zend_Log::DEBUG:
                return '#00b0b8';
                break;
            case Zend_Log::INFO:
                return '#ffffff';
                break;
            case Zend_Log::WARN:
                return '#f6602e';
                break;
            case Zend_Log::NOTICE:
                return '#ee7f5a';
                break;
            default:
                return '#fff68f';
        }
    }
}
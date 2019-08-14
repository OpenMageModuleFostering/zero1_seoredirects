<?php
class Zero1_Seoredirects_Helper_Hints extends Mage_Core_Helper_Abstract
{
    //TODO: sort out correct url
    public function getFromTypeHintHtml(){
        $html = '<a'
            . ' href="'. $this->escapeUrl('http://www.google.co.uk') . '"'
            . ' onclick="this.target=\'_blank\'"'
            . ' title="' . $this->__('What is this?') . '"'
            . ' class="link-store-scope">'
            . $this->__('What is this?')
            . '</a>';
        return $html;
    }

    //TODO: sort out correct url
    public function getToTypeHintHtml(){
        $html = '<a'
            . ' href="'. $this->escapeUrl('http://www.google.co.uk') . '"'
            . ' onclick="this.target=\'_blank\'"'
            . ' title="' . $this->__('What is this?') . '"'
            . ' class="link-store-scope">'
            . $this->__('What is this?')
            . '</a>';
        return $html;
    }
}
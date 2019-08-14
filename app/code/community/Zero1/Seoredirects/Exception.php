<?php
class Zero1_Seoredirects_Exception extends Exception//Mage_Core_Exception
{
    protected $_messages = array();

    public function getErrorsAsString($seperator = '<br />'){
        return implode($seperator, $this->_messages);
    }
    // overiding superclass method cos not needed
    public function addMessage($msg = ''){
        $this->_messages[] = $msg;
    }

    public function getMessages(){
        return $this->_messages;
    }

}

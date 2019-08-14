<?php
/**
 * Class Zero1_Seoredirects_Model_Importer_Google
 */
class Zero1_Seoredirects_Model_Importer_Google extends Zero1_Seoredirects_Model_Importer_Abstract
{
	protected $_zendHttpClient;

	public function run()
	{
		$url = $this->getHelper()->getRemoteFileUrl($this->storeId, $this->websiteId);
		$this->log(sprintf('Importing Google Doc From: %s', $url));
		preg_match('/output=csv/', $url, $results, PREG_OFFSET_CAPTURE);
		if(!empty($results)){
			$this->runCsv($url);
		}else{
			preg_match('/pubhtml/', $url, $results, PREG_OFFSET_CAPTURE);
			if(!empty($results)){
				$this->runHtml($url);
			}else{
				$this->log('File type could not be verified, aborting.', null, Zend_Log::WARN);
			}
		}
	}

	/**
	 * @return Zend_Http_Client
	 */
	protected function getZendClient()
	{
		if (!$this->_zendHttpClient instanceof Zend_Http_Client) {
			$this->_zendHttpClient = new Zend_Http_Client();
		}
		return $this->_zendHttpClient;
	}

	protected function runHtml($url)
	{
		$this->log('Importing remote html: "'.$url.'"');
		$content = '';
		try {
			$this->getZendClient()->setUri($url);
			$content = $this->getZendClient()->request()->getBody();
		} catch(Exception $e) {
			$this->log('Failed to retrieve content from remote document "'.$url.'" Error: '.$e->getMessage(), null, Zend_Log::WARN);
			return;
		}
		if(preg_match('~<!DOCTYPE html>~', $content) != 1){
			$this->log('Content from remote url, does not appear to html "'.$url.'"', null, Zend_Log::WARN);
		}

		$this->lineNumber = 0;
		$totalLines = 0;
		$doc = new DOMDocument();
        try {
            @$doc->loadHTML($content);
        }catch(Exception $e){
            //file_put_contents('temp.html', $content);
            throw $e;
        }

		/* @var $tables DOMNodeList */
		$tables = $doc->getElementsByTagName('table');
		$table = $tables->item(0);

		/* @var $child DOMElement */
		foreach($table->childNodes as $child){
			if($child->nodeName !== 'tbody'){
				continue;
			}else{
				/* @var $row DOMElement */
				/* @var $cell DOMElement */
				foreach($child->childNodes as $row){
					$data = array();
					foreach($row->childNodes as $cell){
						if($cell->nodeName === 'td'){
							$data[] = $cell->textContent;
						}
					}
					$totalLines++;
				}
				break;
			}
		}

		$this->getStatus()->setToBeImported($totalLines)->save();

		/* @var $child DOMElement */
		foreach($table->childNodes as $child){
			if($child->nodeName !== 'tbody'){
				continue;
			}else{
				/* @var $row DOMElement */
				/* @var $cell DOMElement */
				foreach($child->childNodes as $row){
					$data = array();
					foreach($row->childNodes as $cell){
						if($cell->nodeName === 'td'){
							$data[] = $cell->textContent;
						}
					}
					if($this->lineNumber == 0){
						$this->importHeaders($data);
						if(!$this->hasHeaders($data)){
							$this->importRow($data);
						}
					}else{
						$this->importRow($data);
					}

                    $this->lineNumber++;
					$this->updateStatus();
				}
				break;
			}
		}
        $this->updateStatus(true);
	}

	protected function runCsv($url)
	{
		$this->log('Importing remote csv: "'.$url.'"');
		try {
			$this->getZendClient()->setUri($url);
			$content = $this->getZendClient()->request()->getBody();
		} catch(Exception $e) {
			$this->log('Failed to retrieve content from remote document "'.$url.'" Error: '.$e->getMessage(), null, Zend_Log::WARN);
			return;
		}

		$file = explode(PHP_EOL, $content);
		$totalLines = 0;
		foreach ($file as $line) {
			$totalLines++;
		}
		$this->getStatus()->setToBeImported($totalLines)->save();
		reset($file);

		$this->lineNumber = 0;
		foreach ($file as $line) {
			$line = str_getcsv($line);
			if($this->lineNumber == 0){
				$this->importHeaders($line);
				if(!$this->hasHeaders($line)){
					$this->importRow($line);
				}
			}else{
				$this->importRow($line);
			}
			$this->updateStatus();
			$this->lineNumber++;
		}
        $this->updateStatus(true);
	}
}
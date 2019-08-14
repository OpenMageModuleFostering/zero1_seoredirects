<?php
/**
 * Class Zero1_Seoredirects_Model_Importer_Local
 */
class Zero1_Seoredirects_Model_Importer_Local extends Zero1_Seoredirects_Model_Importer_Abstract
{
	public function run()
	{
		/** @var Zero1_Seoredirects_Model_File $file */
		$file = $this->getFileHelper()->getFile($this->storeId, $this->websiteId);
		$fileHandle = fopen($file->getInternalPath(), 'r');
		$this->log(sprintf('Importing: %s', $file->getInternalPath()));

		$totalLines = 0;
		while(!feof($fileHandle)){
			$line = fgets($fileHandle);
			$totalLines++;
		}
		$this->getStatus()->setToBeImported($totalLines)->save();
		rewind($fileHandle);

		while(!feof($fileHandle)){
			$line = fgetcsv($fileHandle);
			if($this->lineNumber == 0){
				$this->importHeaders($line);
				if(!$this->hasHeaders($line)){
					$this->importRow($line);
				}
			}else{
				$this->importRow($line);
			}
            $this->lineNumber++;
			$this->updateStatus();
		}
		fclose($fileHandle);
        $this->updateStatus(true);
	}

	/**
	 * @return Zero1_Seoredirects_Helper_Files
	 */
	protected function getFileHelper()
	{
		return Mage::helper('zero1_seo_redirects/files');
	}
}
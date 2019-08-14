<?php
class Zero1_Seoredirects_Model_Adminhtml_System_Config_Backend_File extends Mage_Core_Model_Config_Data
{
    /**
     * Upload max file size in kilobytes
     *
     * @var int
     */
    protected $_maxFileSize = 0;
    protected $uploadPath;
    /**
     * Save uploaded file before saving config value
     *
     * @return Mage_Adminhtml_Model_System_Config_Backend_File
     */
    protected function _beforeSave()
    {
        $this->uploadPath = Mage::helper('zero1_seo_redirects/files')->getRelativeImportPath($this->getStoreCode(), $this->getWebsiteCode());
        $value = $this->getValue();
        if(is_array($value) && isset($value['delete'])){
            $this->deleteFile($this->getOldValue());
            $this->delete();
        }else{
            $this->saveFile();
        }

        return $this;
    }

    protected function deleteFile($oldFileName){
        $this->setValue('');
        if($oldFileName != null && file_exists($this->uploadPath.$oldFileName)){
            try{
                unlink($this->uploadPath.$oldFileName);
            }catch(Exception $e){
                Mage::throwException($e->getMessage());
                return $this;
            }
        }
        return $this;
    }

    protected  function saveFile(){
        if ($_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value']){
            $uploadDir = $this->uploadPath;
            try {
                $file = array();
                $tmpName = $_FILES['groups']['tmp_name'];
                $file['tmp_name'] = $tmpName[$this->getGroupId()]['fields'][$this->getField()]['value'];
                $name = $_FILES['groups']['name'];
                $file['name'] = $name[$this->getGroupId()]['fields'][$this->getField()]['value'];
                $uploader = new Mage_Core_Model_File_Uploader($file);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                //delete the old file before saving the new one
                if(!$uploader->checkAllowedExtension($uploader->getFileExtension())){
                    Mage::throwException('File extension ('.$uploader->getFileExtension().') not allowed, .csv only.');
                }else{
                    $this->deleteFile($this->getOldValue());
                }
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);

            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
                return $this;
            }

            $filename = $result['file'];
            if ($filename) {
                $this->setValue($filename);
            }
        }
        return $this;
    }

    /**
     * Validation callback for checking max file size
     *
     * @param  string $filePath Path to temporary uploaded file
     * @throws Mage_Core_Exception
     */
    public function validateMaxSize($filePath)
    {
        if ($this->_maxFileSize > 0 && filesize($filePath) > ($this->_maxFileSize * 1024)) {
            throw Mage::exception('Mage_Core', Mage::helper('adminhtml')->__('Uploaded file is larger than %.2f kilobytes allowed by server', $this->_maxFileSize));
        }
    }

    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        return array('csv');
    }
}

<?php
class Zero1_Seoredirects_Adminhtml_Seoredirects_ConfigController extends Mage_Adminhtml_Controller_Action
{	
	public function indexAction()
	{
		$path = urldecode($this->getRequest()->getParam('path', ''));
		$name = urldecode($this->getRequest()->getParam('name', 'filename'));

		if(file_exists($path) && !is_dir($path)){
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.$name);
			header('Content-Length: '.filesize($path));

			ob_clean();
			flush();
			readfile($path);
		}else{
			echo 'ERROR<br />';
			echo $path.'<br />';
		}
		die;
	}
}

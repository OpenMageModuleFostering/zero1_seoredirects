<?php
require_once('app/Mage.php');
Mage::app('admin');
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
umask(0);
set_time_limit(0);

/** @var Zero1_Seoredirects_Model_Importer $importer */
$importer = Mage::getModel('zero1_seo_redirects/importer');
$importer->import();
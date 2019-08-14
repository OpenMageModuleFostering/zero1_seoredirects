<?php
$installer = $this;
 
$installer->startSetup();

$installer->run("
 
DROP TABLE IF EXISTS {$this->getTable('zero1_seoredirects_redirection')};
CREATE TABLE {$this->getTable('zero1_seoredirects_redirection')} (
  `entity_id` int(11) unsigned NOT NULL auto_increment,
  `redirect_from` varchar(2047) NOT NULL,
  `redirect_to` varchar(2047) NOT NULL,
  `store` int(4) NOT NULL,
  PRIMARY KEY (`entity_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

");
 
$installer->endSetup();

<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS `{$this->getTable('zebu_adminlog')}` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) DEFAULT NULL,
  `controller` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `http_user_agent` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `server_addr` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `remote_addr` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `access_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci

    ");

$installer->endSetup(); 
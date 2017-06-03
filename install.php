<?php

/**
 * Copyright (c) 2012, Zarif Safiullin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

$dbPrefix = OW_DB_PREFIX;

$sql = <<<EOT

CREATE TABLE `{$dbPrefix}whatsnew_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `accessKey` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`accessKey`,`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


EOT;

OW::getDbo()->query($sql);
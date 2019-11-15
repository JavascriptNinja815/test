<?php
/**
 * Created by JetBrains PhpStorm.
 * User: KL
 * To change this template use File | Settings | File Templates.
 */
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE `{$this->getTable('chframes/frames')}` (
 `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity ID',
  `reel_id` int(10) unsigned NOT NULL,
  `frame_index` int(1) DEFAULT NULL COMMENT 'frame id 1 - 14 0 - for center',
  `final_frame_status` tinyint(1) DEFAULT '0' COMMENT '0 - nothing, 1 regenerate, 2 ...   3 saved.. 10 generation_complete',
  `frame_data` text NOT NULL,
  `source_file` varchar(500) DEFAULT NULL,
  `rendered_file` varchar(500) DEFAULT NULL,
  `viewport_file` varchar(500) DEFAULT NULL,
  `left_file` varchar(500) DEFAULT NULL,
  `right_file` varchar(500) DEFAULT NULL,
  `background_file` varchar(500) DEFAULT NULL,
  `text_file` varchar(500) DEFAULT NULL,
  `thumb_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Creation Time',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Update Time',
  `legacy_frame_id` int(11) DEFAULT NULL,
  `legacy_frame_data` text,
  PRIMARY KEY (`entity_id`),
  KEY `IDX_REEL_ENTITY_FRAME_ENTITY_ENTITY_REEL_ID` (`reel_id`),
  KEY `frame_index` (`frame_index`),
  CONSTRAINT `FK_REEL_ENTT_ID` FOREIGN KEY (`reel_id`) REFERENCES `ch_reels` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB AUTO_INCREMENT=1  DEFAULT CHARSET=utf8 COMMENT='Reel Frames';
");

$installer->endSetup();

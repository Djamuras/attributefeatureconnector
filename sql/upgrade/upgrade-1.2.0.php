<?php
// Create mapping categories table
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping_category` (
    `id_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(128) NOT NULL,
    `description` text,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_category`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Add category_id to mapping table
$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'attribute_feature_mapping` 
    ADD COLUMN `id_category` int(10) unsigned DEFAULT NULL AFTER `id_feature_value`,
    ADD INDEX `idx_category` (`id_category`)';

// Create performance logs table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_performance_log` (
    `id_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `operation` varchar(64) NOT NULL,
    `id_mapping` int(10) unsigned DEFAULT NULL,
    `products_processed` int(10) unsigned NOT NULL DEFAULT 0,
    `products_updated` int(10) unsigned NOT NULL DEFAULT 0,
    `execution_time` float NOT NULL,
    `memory_usage` int(10) unsigned DEFAULT NULL,
    `batch_size` int(10) unsigned NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_log`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Create attribute suggestions table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_suggestion` (
    `id_suggestion` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_product` int(10) unsigned NOT NULL,
    `text` varchar(255) NOT NULL,
    `confidence` float NOT NULL,
    `processed` tinyint(1) NOT NULL DEFAULT 0,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_suggestion`),
    INDEX `idx_product` (`id_product`),
    INDEX `idx_processed` (`processed`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Create default category
$sql[] = "INSERT INTO `" . _DB_PREFIX_ . "attribute_feature_mapping_category` 
    (`name`, `description`, `date_add`, `date_upd`) 
    VALUES ('Default', 'Default category for mappings', NOW(), NOW())";

foreach ($sql as $query) {
    try {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    } catch (Exception $e) {
        // Skip error if column already exists
        if (!strpos($e->getMessage(), 'Duplicate column')) {
            return false;
        }
    }
}

// Update existing mappings to use default category
$id_default_category = Db::getInstance()->getValue("SELECT id_category FROM `" . _DB_PREFIX_ . "attribute_feature_mapping_category` WHERE name = 'Default'");
if ($id_default_category) {
    Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "attribute_feature_mapping` SET id_category = " . (int)$id_default_category . " WHERE id_category IS NULL");
}

return true;
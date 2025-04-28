<?php
$sql = array();

// Create mapping table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping` (
    `id_mapping` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_feature_value` int(10) unsigned NOT NULL,
    `id_category` int(10) unsigned DEFAULT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_mapping`),
    INDEX `idx_feature_value` (`id_feature_value`),
    INDEX `idx_category` (`id_category`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Create mapping attributes relation table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes` (
    `id_mapping` int(10) unsigned NOT NULL,
    `id_attribute` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_mapping`, `id_attribute`),
    INDEX `idx_attribute` (`id_attribute`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Create mapping categories table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping_category` (
    `id_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(128) NOT NULL,
    `description` text,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_category`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

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
    PRIMARY KEY (`id_log`),
    INDEX `idx_date` (`date_add`)
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

// Create category feature mapping table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'category_feature_mapping` (
    `id_mapping` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_feature_value` int(10) unsigned NOT NULL,
    `id_category` int(10) unsigned NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_mapping`),
    UNIQUE KEY `unique_mapping` (`id_feature_value`, `id_category`),
    INDEX `idx_feature_value` (`id_feature_value`),
    INDEX `idx_category` (`id_category`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Insert default category
$sql[] = "INSERT INTO `" . _DB_PREFIX_ . "attribute_feature_mapping_category` 
    (`name`, `description`, `date_add`, `date_upd`) 
    VALUES ('Default', 'Default category for mappings', NOW(), NOW())";

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
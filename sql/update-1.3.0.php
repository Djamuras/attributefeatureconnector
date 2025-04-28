<?php
$sql = array();

// Create category mapping table
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

foreach ($sql as $query) {
    try {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    } catch (Exception $e) {
        // Skip error if table already exists
        if (!strpos($e->getMessage(), 'already exists')) {
            return false;
        }
    }
}

return true;
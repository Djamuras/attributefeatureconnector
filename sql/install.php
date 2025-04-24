<?php
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping` (
    `id_mapping` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_feature_value` int(10) unsigned NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_mapping`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes` (
    `id_mapping` int(10) unsigned NOT NULL,
    `id_attribute` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_mapping`, `id_attribute`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
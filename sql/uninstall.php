<?php
$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'attribute_feature_mapping_category`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'attribute_feature_performance_log`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'attribute_feature_suggestion`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
<?php

$settings = [];

$settings['shopping_cart.debug'] = $modx->newObject('modSystemSetting');
$settings['shopping_cart.debug']->fromArray([
    'key' => 'shopping_cart.debug',
    'value' => '0',
    'xtype' => 'textfield',
    'namespace' => 'shopping_cart',
    'area' => '',
    'editedon' => null,
], '', true, true);

$settings['shopping_cart.debug'] = $modx->newObject('modSystemSetting');
$settings['shopping_cart.debug']->fromArray([
    'key' => 'shopping_cart.debug',
    'value' => '0',
    'xtype' => 'textfield',
    'namespace' => 'shopping_cart',
    'area' => '',
    'editedon' => null,
], '', true, true);

return $settings;

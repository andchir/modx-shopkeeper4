<?php

$shopkeeper4 = $modx->getService(
    'shopkeeper4','Shopkeeper4',
    $modx->getOption('shopkeeper4.core_path',null,$modx->getOption('core_path').'components/shopkeeper4/')
    . 'model/shopkeeper4/',
    $scriptProperties
);
if (!($shopkeeper4 instanceof Shopkeeper4)) return '';

$output = '';



return $output;

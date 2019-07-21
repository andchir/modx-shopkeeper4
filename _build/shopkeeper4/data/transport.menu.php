<?php

/**
 * Adds modActions and modMenus into package
 *
 * @package doodles
 * @subpackage build
 */
$menu = $modx->newObject('modMenu');

$menu->fromArray([
    'text' => 'shopkeeper4',
    'parent' => 'components',
    'description' => 'shopkeeper4_menu_desc',
    'icon' => 'images/icons/plugin.gif',
    'namespace' => PKG_NAME_LOWER,
    'menuindex' => 0,
    'params' => '',
    'handler' => '',
    'action' => 'index'
], '', true, true);

return $menu;
<?php
$xpdo_meta_map['ShoppingCartItem']= array (
  'package' => 'shopping_cart',
  'version' => '1.0',
  'table' => 'shopping_cart_item',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'session_id' => '',
    'currency' => '',
    'createdon' => NULL,
    'createdby' => 0,
    'editedon' => NULL,
  ),
  'fieldMeta' => 
  array (
    'session_id' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '128',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'currency' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '64',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'createdon' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => true,
    ),
    'createdby' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => true,
      'default' => 0,
    ),
    'editedon' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => true,
    ),
  ),
  'composites' => 
  array (
    'Content' => 
    array (
      'class' => 'ShoppingCartContent',
      'local' => 'id',
      'foreign' => 'shoppingcart_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
  'aggregates' => 
  array (
    'Owner' => 
    array (
      'class' => 'modUser',
      'local' => 'createdby',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);

<?php
$xpdo_meta_map['ShoppingCartContent']= array (
  'package' => 'shopping_cart',
  'version' => '1.0',
  'table' => 'shopping_cart_content',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'item_id' => 0,
    'title' => '',
    'name' => '',
    'price' => 0.0,
    'count' => 0,
    'options' => '',
    'shoppingcart_id' => 0,
  ),
  'fieldMeta' => 
  array (
    'item_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'title' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'price' => 
    array (
      'dbtype' => 'float',
      'precision' => '15',
      'phptype' => 'float',
      'null' => true,
      'default' => 0.0,
    ),
    'count' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'null' => true,
      'default' => 0,
    ),
    'options' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'array',
      'null' => true,
      'default' => '',
    ),
    'shoppingcart_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
      'index' => 'index',
    ),
  ),
  'indexes' => 
  array (
    'shoppingcart_id' => 
    array (
      'alias' => 'shoppingcart_id',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'shoppingcart_id' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
  'aggregates' => 
  array (
    'ShoppingCart' => 
    array (
      'class' => 'ShoppingCartItem',
      'local' => 'shoppingcart_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);

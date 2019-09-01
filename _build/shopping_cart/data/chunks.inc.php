<?php

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'page_shoppingCartEdit',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/page_shoppingCartEdit.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'page_shoppingCartFormCheckout',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/page_shoppingCartFormCheckout.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shoppingCart_emptyTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/emptyTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shoppingCart_mailOrderOuterTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/mailOrderOuterTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shoppingCart_mailOrderRowTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/mailOrderRowTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shoppingCart_orderReport',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/orderReport.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shoppingCart_outerTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/outerTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shoppingCart_rowTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/rowTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shopCartSmall_emptyTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shopCartSmall_emptyTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shopCartSmall_outerTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shopCartSmall_outerTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

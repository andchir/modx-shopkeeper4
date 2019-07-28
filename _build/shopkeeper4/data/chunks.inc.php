<?php

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_productsRowTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shk4_productsRowTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_menuRowTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shk4_menuRowTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_menuOuterTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shk4_menuOuterTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_breadcrumbs',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shk4_breadcrumbs.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_filterBlockTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shk4_filterBlockTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_filterOuterTpl',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/shk4_filterOuterTpl.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_filter_text',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/default/filters/default.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_filter_default',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/default/filters/default.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array(
    'name' => 'shk4_filter_number',
    'description' => '',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/chunks/default/filters/number.html'),
    'static' => 0,
    'source' => 1,
    'static_file' => '',
),'',true,true);
$category->addMany($chunk);

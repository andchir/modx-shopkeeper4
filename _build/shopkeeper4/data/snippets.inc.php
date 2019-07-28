<?php

$snippet = $modx->newObject('modSnippet');
$snippet->fromArray(array(
    'name' => 'shopkeeper4',
    'description' => 'Output products, categories, filters, etc.',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/snippets/snippet.shopkeeper4.php'),
    'static' => 0,
    'source' => 1,
    'static_file' => ''
),'',true,true);
$properties = [
    'action' => 'products',
    'rowTpl' => 'shk4_productsRowTpl'
];
$snippet->setProperties($properties);
$category->addMany($snippet);

$snippet = $modx->newObject('modSnippet');
$snippet->fromArray(array(
    'name' => 'shk4ImageUrl',
    'description' => 'Output images with filter.',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/snippets/snippet.shk4imageurl.php'),
    'static' => 0,
    'source' => 1,
    'static_file' => ''
),'',true,true);
$properties = [
    'filter' => 'thumb_small',
    'imagePath' => ''
];
$snippet->setProperties($properties);
$category->addMany($snippet);

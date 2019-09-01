<?php

$snippet = $modx->newObject('modSnippet');
$snippet->fromArray(array(
    'name' => 'shoppingCart',
    'description' => 'Shopping cart',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/snippets/snippet.shopping_cart.php'),
    'static' => 0,
    'source' => 1,
    'static_file' => ''
),'',true,true);
$properties = [
    'action' => 'print',
    'rowTpl' => 'shoppingCart_rowTpl',
    'outerTpl' => 'shoppingCart_outerTpl',
    'contentType' => 'shop'
];
$snippet->setProperties($properties);
$category->addMany($snippet);

$snippet = $modx->newObject('modSnippet');
$snippet->fromArray(array(
    'name' => 'numFormat',
    'description' => 'Number format.',
    'snippet' => getSnippetContent($sources['source_core'] . 'elements/snippets/snippet.num_format.php'),
    'static' => 0,
    'source' => 1,
    'static_file' => ''
),'',true,true);
$properties = [

];
$snippet->setProperties($properties);
$category->addMany($snippet);

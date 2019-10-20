<?php

/**
 * shopping_cart build script
 *
 * @package shopping_cart
 * @subpackage build
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$tstart = explode(' ', microtime());
$tstart = $tstart[1] + $tstart[0];
set_time_limit(0);

/* define package names */
define('PKG_NAME', 'shopping_cart');
define('PKG_NAME_LOWER', 'shopping_cart');
define('PKG_VERSION', '1.0.1');
define('PKG_RELEASE', 'pl1');

/* define build paths */
$root = dirname(dirname(dirname(__FILE__))) . '/';
$sources = array(
    'root' => $root,
    'build' => $root . '_build/' . PKG_NAME_LOWER . '/',
    'data' => $root . '_build/' . PKG_NAME_LOWER . '/data/',
    'resolvers' => $root . '_build/' . PKG_NAME_LOWER . '/resolvers/',
    'chunks' => $root . 'core/components/' . PKG_NAME_LOWER . '/chunks/',
    'lexicon' => $root . 'core/components/' . PKG_NAME_LOWER . '/lexicon/',
    'docs' => $root . 'core/components/' . PKG_NAME_LOWER . '/docs/',
    'elements' => $root . 'core/components/' . PKG_NAME_LOWER . '/elements/',
    'source_assets' => $root . 'assets/components/' . PKG_NAME_LOWER . '/',
    'source_core' => $root . 'core/components/' . PKG_NAME_LOWER . '/',
);
unset($root);

/* override with your own defines here (see build.config.sample.php) */
// require_once $sources['build'] . 'build.config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
require_once $sources['build'] . 'functions.php';

$modx = new modX();
$modx->initialize('mgr');
echo '<pre>'; /* used for nice formatting of log messages */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');
$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(
    PKG_NAME_LOWER,
    false,
    true,
    '{core_path}components/' . PKG_NAME_LOWER . '/',
    '{assets_path}components/' . PKG_NAME_LOWER . '/'
);

/* create category */
$category = $modx->newObject('modCategory');
$category->set('id', 1);
$category->set('category', PKG_NAME);

/* add plugins */
$plugins = include $sources['data'] . 'plugins.inc.php';
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in ' . count($plugins) . ' plugins.'); flush();

/* add snippet */
include $sources['data'] . 'snippets.inc.php';
$modx->log(modX::LOG_LEVEL_INFO,'Packaged snippets.'); flush();

/* add chunks */
include $sources['data'].'chunks.inc.php';
$modx->log(modX::LOG_LEVEL_INFO,'Packaged chunks.'); flush();

/* create category vehicle */
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'Snippets' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name'
        ),
        'Chunks' => array (
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name'
        ),
        'TemplateVars' => array (
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name'
        ),
        'Plugins' => array (
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'PluginEvents' => array(
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => array('pluginid','event')
                )
            )
        )
    )
);
$vehicle = $builder->createVehicle($category, $attr);

/* tables */
$modx->log(modX::LOG_LEVEL_INFO,'Adding in PHP resolvers...');
$vehicle->resolve('php',array(
    'source' => $sources['resolvers'] . 'resolve.tables.inc.php',
));

/* resolvers */
$modx->log(modX::LOG_LEVEL_INFO,'Adding file resolvers to category...');
$vehicle->resolve('file', array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
));
$vehicle->resolve('file', array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
//$vehicle->resolve('php', array(
//    'source' => $sources['resolvers'] . 'setupoptions.resolver.php',
//));

$builder->putVehicle($vehicle);

/* load plugin events */
$events = include $sources['data'] . 'events.inc.php';

$attributes= array(
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
);
foreach ($events as $event) {
    $vehicle = $builder->createVehicle($event,$attributes);
    $builder->putVehicle($vehicle);
}
unset($events, $event, $attributes);
$modx->log(modX::LOG_LEVEL_INFO,'Packaged plugin events.'); flush();

/* load system settings */
$settings = include $sources['data'] . 'settings.inc.php';

$attributes= array(
    xPDOTransport::UNIQUE_KEY => 'key',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
);
foreach ($settings as $setting) {
    $vehicle = $builder->createVehicle($setting,$attributes);
    $builder->putVehicle($vehicle);
}
unset($settings, $setting, $attributes);
$modx->log(modX::LOG_LEVEL_INFO,'Packaged settings.'); flush();

/* now pack in the license file, readme and setup options */
$modx->log(modX::LOG_LEVEL_INFO, 'Adding package attributes and setup options...');
$builder->setPackageAttributes([
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
    'setup-options' => [
        // 'source' => $sources['build'] . 'setup.options.php',
    ],
]);

/* zip up package */
$modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');

$builder->pack();
$tend = explode(" ", microtime());
$tend = $tend[1] + $tend[0];
$totalTime = sprintf("%2.4f s", ($tend - $tstart));

$modx->log(modX::LOG_LEVEL_INFO, "\nPackage Built.\nExecution time: {$totalTime}\n");

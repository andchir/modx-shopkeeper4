<?php

/**
 * Resolves setup-options settings.
 *
 * @var modX $modx
 * @var xPDOTransport $transport
 * @var array $options
 * @package shopkeeper4
 * @subpackage build
 */

if (empty($modx)) $modx =& $transport->xpdo;

$success = false;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $settings = array(
            'mongodb_url',
            'mongodb_database',
            'installation_url',
        );
        foreach ($settings as $key) {
            if (isset($options[$key])) {
                $setting = $modx->getObject('modSystemSetting', ['key' => 'shopkeeper4.' . $key]);
                if (!$setting) {
                    $setting = $modx->newObject('modSystemSetting');
                    $setting->fromArray([
                        'key' => 'shopkeeper4.' . $key,
                        'xtype' => 'textfield',
                        'namespace' => 'shopkeeper4',
                        'area' => '',
                        'editedon' => null,
                    ], '', true, true);
                }
                $setting->set('value', $options[$key]);
                $setting->save();
            }
        }

        if (!empty($options['install_templates'])) {

            $category = $modx->getObject('modCategory', ['category' => 'shopkeeper4']);

            // Templates
            $template = $modx->getObject('modTemplate', ['templatename' => 'shk4_catalog']);
            if (!$template) {
                $template = $modx->newObject('modTemplate');
                $template->fromArray([
                    'templatename' => 'shk4_homepage',
                    'description' => 'Template for the Shopkeeper 4 home page.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'icon' => '',
                    'template_type' => 0,
                    'content' => '',
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/templates/default/homepage.html'
                ]);
                $template->save();

                $template = $modx->newObject('modTemplate');
                $template->fromArray([
                    'templatename' => 'shk4_category',
                    'description' => 'Template for the Shopkeeper 4 catalog category.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'icon' => '',
                    'template_type' => 0,
                    'content' => '',
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/templates/default/category.html'
                ]);
                $template->save();

                $template = $modx->newObject('modTemplate');
                $template->fromArray([
                    'templatename' => 'shk4_catalog_root',
                    'description' => 'Template for the Shopkeeper 4 catalog root category.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'icon' => '',
                    'template_type' => 0,
                    'content' => '',
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/templates/default/catalog.html'
                ]);
                $template->save();

                $template = $modx->newObject('modTemplate');
                $template->fromArray([
                    'templatename' => 'shk4_content_page',
                    'description' => 'Template for the Shopkeeper 4 catalog content page.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'icon' => '',
                    'template_type' => 0,
                    'content' => '',
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/templates/default/content-page.html'
                ]);
                $template->save();

                // Chunks
                $chunk = $modx->newObject('modChunk');
                $chunk->fromArray([
                    'name' => 'shk4_head',
                    'description' => 'Chunk for the Shopkeeper 4 head.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'cache_type' => 0,
                    'snippet' => NULL,
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/chunks/default/head.html'
                ]);
                $chunk->save();

                $chunk = $modx->newObject('modChunk');
                $chunk->fromArray([
                    'name' => 'shk4_header',
                    'description' => 'Chunk for the Shopkeeper 4 header.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'cache_type' => 0,
                    'snippet' => NULL,
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/chunks/default/header.html'
                ]);
                $chunk->save();

                $chunk = $modx->newObject('modChunk');
                $chunk->fromArray([
                    'name' => 'shk4_footer',
                    'description' => 'Chunk for the Shopkeeper 4 footer.',
                    'editor_type' => 0,
                    'category' => $category ? $category->get('id') : 0,
                    'cache_type' => 0,
                    'snippet' => NULL,
                    'locked' => 0,
                    'properties' => NULL,
                    'static' => 1,
                    'static_file' => 'core/components/shopkeeper4/elements/chunks/default/footer.html'
                ]);
                $chunk->save();
            }

        }

        $success = true;
        break;
    case xPDOTransport::ACTION_UNINSTALL:

//        $query = $modx->newQuery('modTemplate');
//        $query->where(array('templatename:like' => 'shk4_%'));
//        $templates = $modx->getCollection('modTemplate', $query);
//
//        /** @var modTemplate $template */
//        foreach ($templates as $template) {
//            $template->remove();
//        }
//
//        $query = $modx->newQuery('modChunk');
//        $query->where(array('name:like' => 'shk4_%'));
//        $chunks = $modx->getCollection('modChunk', $query);
//
//        /** @var modChunk $chunk */
//        foreach ($chunks as $chunk) {
//            $chunk->remove();
//        }

        $success = true;
        break;
}
return $success;

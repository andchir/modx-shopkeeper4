<?php

/**
 * Resolves setup-options settings.
 *
 * @package shopkeeper4
 * @subpackage build
 */

/** @var array $options */

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
                $setting = $object->xpdo->getObject('modSystemSetting', ['key' => 'shopkeeper4.' . $key]);
                if (!$setting) {
                    $setting = $object->xpdo->newObject('modSystemSetting');
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
        $success = true;
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        $success = true;
        break;
}
return $success;

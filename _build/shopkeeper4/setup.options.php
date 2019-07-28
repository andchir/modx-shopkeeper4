<?php
/**
 * Build the setup options form.
 *
 * @package shopkeeper4
 * @subpackage build
 */

/** @var array $options */

$output = '';

$values = array(
    'mongodb_url' => 'mongodb://user:password@127.0.0.1:27017',
    'mongodb_database' => 'shopkeeper4',
    'installation_url' => '/shopkeeper4/',
    'install_templates' => false
);

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:

        $output .= '<label for="shk4_mongodb_url">MongoDB URL:</label>
<input type="text" name="mongodb_url" id="shk4_mongodb_url" width="300" value="'.$values['mongodb_url'].'" />
<br /><br />';

        $output .= '<label for="shk4_mongodb_database">MongoDB Database Name:</label>
<input type="text" name="mongodb_database" id="shk4_mongodb_database" width="300" value="'.$values['mongodb_database'].'" />
<br /><br />';

        $output .= '<label for="shk4_installation_url">Shopkeeper 4 Installation URL:</label>
<input type="text" name="installation_url" id="shk4_installation_url" width="300" value="'.$values['installation_url'].'" />
<br /><br />';

        $output .= '<input type="checkbox" name="install_templates" id="shk4_install_templates" value="1" checked="checked">
<label for="shk4_install_templates" style="display: inline-block; position: relative; top: -2px; margin-left: 4px;">Install Templates</label>
<br /><br />';

        break;
    case xPDOTransport::ACTION_UPGRADE:

        $output .= '<input type="checkbox" name="update_templates" id="shk4_update_templates" value="1" checked="checked">
<label for="shk4_update_templates">Update Templates</label>
<br /><br />';

        break;
    case xPDOTransport::ACTION_UNINSTALL:

        $output .= '<input type="checkbox" name="remove_templates" id="shk4_remove_templates" value="1" checked="checked">
<label for="shk4_remove_templates">Remove Shopkeeper 4 Templates</label>
<br /><br />';

        break;
}

return $output;
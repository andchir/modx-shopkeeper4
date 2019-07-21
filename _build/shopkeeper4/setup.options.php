<?php
/**
 * Build the setup options form.
 *
 * @package shopkeeper4
 * @subpackage build
 */

$output = '';


switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:

        $output = '<input type="checkbox" name="install_templates" id="shk4_install_templates" value="1" checked="checked">
<label for="shk4_install_templates">Install templates</label>
<br /><br />';

        break;
    case xPDOTransport::ACTION_UPGRADE:

        break;
    case xPDOTransport::ACTION_UNINSTALL: break;
}

return $output;
<?php

/**
 * @var modX $modx
 * @var xPDOManager $manager
 */

if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:

            $modx =& $object->xpdo;
            $modelPath = $modx->getOption('core_path') . 'components/shopping_cart/model/';
            $modx->addPackage('shopping_cart', $modelPath);

            $manager = $modx->getManager();

            $manager->createObjectContainer('ShoppingCartItem');
            $manager->createObjectContainer('ShoppingCartContent');

            break;
        case xPDOTransport::ACTION_UPGRADE:



            break;
        case xPDOTransport::ACTION_UNINSTALL:

            $modx =& $object->xpdo;
            $modelPath = $modx->getOption('core_path') . 'components/shopping_cart/model/';
            $modx->addPackage('shopping_cart', $modelPath);

            $manager = $modx->getManager();

            $manager->removeObjectContainer('ShoppingCartItem');
            $manager->removeObjectContainer('ShoppingCartContent');

            break;
    }
}
return true;

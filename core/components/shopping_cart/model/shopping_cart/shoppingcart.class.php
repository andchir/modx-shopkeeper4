<?php

class ShoppingCart {

    const EVENT_OnShoppingCartAddProduct = 'OnShoppingCartAddProduct';

    public $modx;
    public $config = array();

    public function __construct(modX &$modx, array $config = []) {

        $this->modx =& $modx;
        $basePath = $this->modx->getOption('shopping_cart.core_path', $config,$this->modx->getOption('core_path') . 'components/shopping_cart/');
        $assetsUrl = $this->modx->getOption('shopping_cart.assets_url', $config,$this->modx->getOption('assets_url') . 'components/shopping_cart/');
        $this->config = array_merge([
            'basePath' => $basePath,
            'corePath' => $basePath,
            'modelPath' => $basePath . 'model/',
            'processorsPath' => $basePath . 'processors/',
            'templatesPath' => $basePath . 'templates/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl . 'connector.php',
        ], $config);

        $this->modx->addPackage('shopping_cart', $this->config['modelPath']);
    }

    public function actionResponse()
    {
        $output = '';
        $action = $_POST['action'] ?? '';
        if (!$action && !empty($_POST['item_id'])) {
            $action = 'add_to_cart';
        }
        switch ($action) {
            case 'add_to_cart':

                $itemData = $this->modx->invokeEvent( self::EVENT_OnShoppingCartAddProduct, ['data' => $_POST]);

                // var_dump($action, self::EventOnShoppingCartAddProduct, $itemData);

                break;
            case 'print':

                $cartItems = $this->modx->getCollection('ShoppingCartItem');
                $output = count($cartItems);

                break;
        }
        return $output;
    }

}

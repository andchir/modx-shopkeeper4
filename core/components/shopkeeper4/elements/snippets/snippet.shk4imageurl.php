<?php

/*
Example of use:

[[shk4ImageUrl?
&imagePath=`[[+image.dirPath]]/[[+image.fileName]].[[+image.extension]]`
&filter=`thumb_small`
]]
*/

/** @var array $scriptProperties */

$defaultImageUrl = $modx->getOption('shopkeeper4.product_image_default', null, '');
$shopkeeperInstallationUrl = $modx->getOption('shopkeeper4.installation_url', null, '');
$filter = $modx->getOption('filter', $scriptProperties, 'thumb_small');
$imagePath = $modx->getOption('imagePath', $scriptProperties, '');
$placeholderPattern = '/\[\[\+(.*)\]\]/';

if (empty($imagePath)
    || !$shopkeeperInstallationUrl
    || preg_match($placeholderPattern, $imagePath)) {
        return $defaultImageUrl;
}

$imagePath = "media/cache/resolve/{$filter}/uploads/" . $imagePath;

return $shopkeeperInstallationUrl . $imagePath;
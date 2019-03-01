<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class CartNoticesAsset
 *
 * @author  Ether Creative
 * @package ether\cartnotices\web\assets
 */
class CartNoticesAsset extends AssetBundle
{

	public function init ()
	{
		$this->sourcePath = __DIR__;

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'CartNotices.js',
		];

		\Craft::$app->view->registerTranslations('cart-notices', [
			'New notice',
		]);

		parent::init();
	}

}
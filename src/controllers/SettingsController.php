<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\controllers;

use craft\web\Controller;
use ether\cartnotices\CartNotices;
use ether\cartnotices\elements\Notice;

/**
 * Class SettingsController
 *
 * @author  Ether Creative
 * @package ether\cartnotices\controllers
 */
class SettingsController extends Controller
{

	public function actionIndex ()
	{
		return $this->renderTemplate(
			'cart-notices/settings/index',
			[
				'plugin' => CartNotices::getInstance(),
			]
		);
	}

	public function actionFields ()
	{
		return $this->renderTemplate(
			'cart-notices/settings/fields',
			[
				'plugin' => CartNotices::getInstance(),
				'fieldLayout' => \Craft::$app->getFields()->getLayoutByType(
					Notice::class
				),
			]
		);
	}

}
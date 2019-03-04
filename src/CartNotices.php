<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices;

use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use ether\cartnotices\elements\Notice;
use ether\cartnotices\web\twig\CraftVariableBehavior;
use yii\base\Event;
use yii\base\Model;

/**
 * Class CartNotices
 *
 * @author  Ether Creative
 * @package ether\cartnotices
 */
class CartNotices extends Plugin
{

	// Properties
	// =========================================================================

	public $hasCpSection = true;
	public $hasCpSettings = true;

	// Craft
	// =========================================================================

	public function init ()
	{
		parent::init();

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			[$this, 'onVariableInit']
		);

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			[$this, 'onRegisterCpUrlRules']
		);
	}

	public function getCpNavItem ()
	{
		$item = parent::getCpNavItem();
		$item['subnav'] = [
			'notices' => ['label' => 'Notices', 'url' => 'cart-notices'],
			'settings' => ['label' => 'Settings', 'url' => 'cart-notices/settings'],
		];

		return $item;
	}


	// Settings
	// =========================================================================

	protected function createSettingsModel ()
	{
		return new Model();
	}

	/**
	 * @return mixed|\yii\web\Response
	 */
	public function getSettingsResponse ()
	{
		$url = UrlHelper::cpUrl('cart-notices/settings');

		return \Craft::$app->controller->redirect($url);
	}

	/**
	 * @return bool
	 * @throws \yii\base\Exception
	 */
	public function beforeSaveSettings (): bool
	{
		$fieldLayout       = \Craft::$app->getFields()->assembleLayoutFromPost();
		$fieldLayout->type = Notice::class;
		\Craft::$app->getFields()->saveLayout($fieldLayout);

		return parent::beforeSaveSettings();
	}

	// Events
	// =========================================================================

	/**
	 * @return bool
	 * @throws \Exception
	 */
	protected function beforeInstall (): bool
	{
		if (!\Craft::$app->getPlugins()->isPluginInstalled('commerce'))
			throw new \Exception('Commerce is required for this plugin to be installed!');

		return parent::beforeInstall();
	}

	public function onVariableInit (Event $e)
	{
		/** @var CraftVariable $variable */
		$variable = $e->sender;

		$variable->attachBehaviors([
			CraftVariableBehavior::class,
		]);
	}

	public function onRegisterCpUrlRules (RegisterUrlRulesEvent $event)
	{
		$event->rules['cart-notices/settings'] = 'cart-notices/settings/index';
		$event->rules['cart-notices/settings/fields'] = 'cart-notices/settings/fields';

		$event->rules['cart-notices/new'] = 'cart-notices/notice/edit';
		$event->rules['cart-notices/new/<siteHandle:{handle}>'] = 'cart-notices/notice/edit';
		$event->rules['cart-notices/<noticeId:\d+>'] = 'cart-notices/notice/edit';
		$event->rules['cart-notices/<noticeId:\d+>/<siteHandle:{handle}>'] = 'cart-notices/notice/edit';

		$event->rules['cart-notices'] = 'cart-notices/notice/index';
		$event->rules['cart-notices/<type>'] = 'cart-notices/notice/index';
	}

	// Helpers
	// =========================================================================

	public static function t ($message, $params = [])
	{
		return \Craft::t('cart-notices', $message, $params);
	}

}
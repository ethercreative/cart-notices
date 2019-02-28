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
use craft\web\Controller;
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
		/** @var Controller $controller */
		$controller = \Craft::$app->controller;

		return $controller->renderTemplate(
			'cart-notices/_settings',
			[
				'plugin' => $this,
				'fieldLayout' => \Craft::$app->getFields()->getLayoutByType(Notice::class),
			]
		);
	}

	public function beforeSaveSettings (): bool
	{
		$fieldLayout       = \Craft::$app->getFields()->assembleLayoutFromPost();
		$fieldLayout->type = Notice::class;
		\Craft::$app->getFields()->saveLayout($fieldLayout);

		return parent::beforeSaveSettings();
	}

	// Events
	// =========================================================================

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
		$event->rules['cart-notices/new'] = 'cart-notices/notice/edit';
		$event->rules['cart-notices/new/<siteHandle:{handle}>'] = 'cart-notices/notice/edit';
		$event->rules['cart-notices/<noticeId:\d+><slug:(?:-{slug})?>'] = 'cart-notices/notice/edit';
		$event->rules['cart-notices/<noticeId:\d+><slug:(?:-{slug})?>/<siteHandle:{handle}>'] = 'cart-notices/notice/edit';
	}

	// Helpers
	// =========================================================================

	public static function t ($message, $params = [])
	{
		return \Craft::t('cart-notices', $message, $params);
	}

}
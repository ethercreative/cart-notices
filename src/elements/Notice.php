<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\elements;

use craft\base\Element;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use ether\cartnotices\CartNotices;
use ether\cartnotices\elements\db\NoticeQuery;
use ether\cartnotices\enums\Types;

/**
 * Class Notice
 *
 * @author  Ether Creative
 * @package ether\cartnotices\elements
 */
class Notice extends Element
{

	// Properties
	// =========================================================================

	/** @var string */
	public $type = Types::MinimumAmount;

	// Methods
	// =========================================================================

	public static function hasContent (): bool
	{
		return true;
	}

	public static function hasTitles (): bool
	{
		return true;
	}

	public static function isLocalized (): bool
	{
		return true;
	}

	public static function hasStatuses (): bool
	{
		return true;
	}

	/**
	 * @return NoticeQuery|ElementQueryInterface
	 */
	public static function find (): ElementQueryInterface
	{
		return new NoticeQuery(static::class);
	}

	public function getEditorHtml (): string
	{
		$html = \Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
			[
				'label'     => \Craft::t('app', 'Title'),
				'siteId'    => $this->siteId,
				'id'        => 'title',
				'name'      => 'title',
				'value'     => $this->title,
				'errors'    => $this->getErrors('title'),
				'first'     => true,
				'autofocus' => true,
				'required'  => true
			]
		]);

		// ...

		$html .= parent::getEditorHtml();

		return $html;
	}

	public function getFieldLayout ()
	{
		return \Craft::$app->getFields()->getLayoutByType(Notice::class);
	}

	public function getCpEditUrl ()
	{
		return 'cart-notices/' . $this->id . '-' . $this->slug;
	}

	protected static function defineActions (string $source = null): array
	{
		return [
			Restore::class,
		];
	}

	protected static function defineTableAttributes (): array
	{
		return [
			'title' => \Craft::t('app', 'Title'),
			'type' => CartNotices::t('Type'),
		];
	}

	public static function defaultTableAttributes (string $source): array
	{
		return ['title', 'type'];
	}

	protected function tableAttributeHtml (string $attribute): string
	{
		switch ($attribute)
		{
			case 'type':
				return Types::toLabel($this->type);
		}

		return parent::tableAttributeHtml($attribute);
	}

	protected static function defineSearchableAttributes (): array
	{
		return ['type'];
	}

	// Events
	// =========================================================================

	public function afterSave (bool $isNew)
	{
		if ($isNew)
		{
			\Craft::$app->db->createCommand()
				->insert('{{%products}}', [
					'id'   => $this->id,
					'type' => $this->type,
				])
				->execute();
		}
		else
		{
			\Craft::$app->db->createCommand()
				->update('{{%products}}', [
					'type' => $this->type,
				], ['id' => $this->id])
				->execute();
		}

		parent::afterSave($isNew);
	}


}
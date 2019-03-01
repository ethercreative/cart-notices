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
use MongoDB\BSON\Type;

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

	/** @var float - Minimum amount: total must be <= this */
	public $target;

	/** @var float - Minimum amount: total must be >= this */
	public $threshold;

	/** @var int - Deadline: deadline hour, can be 1 - 24 */
	public $hour;

	/** @var array - Deadline: days the notice is active */
	public $days;

	/** @var string - Referer: the referring site, can be PCRE */
	public $referer;

	/** @var int - Products in Cart: Min quantity of any selected product */
	public $minQty;

	/** @var int - Products in Cart: Max quantity of any selected product */
	public $maxQty;

	// Getters
	// =========================================================================

	public function getProducts ()
	{
		return []; // TODO: this
	}

	public function getCategories ()
	{
		return []; // TODO: this
	}

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
		$url = 'cart-notices/' . $this->id;
		$url .= '/' . \Craft::$app->getSites()->getSiteById($this->siteId)->handle;

		return $url;
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
			'title'       => \Craft::t('app', 'Title'),
			'type'        => CartNotices::t('Type'),
			'target'      => CartNotices::t('Target'),
			'threshold'   => CartNotices::t('Threshold'),
			'hour'        => CartNotices::t('Hour'),
			'days'        => CartNotices::t('Days'),
			'referer'     => CartNotices::t('Referer'),
			'minQty'      => CartNotices::t('Min Qty'),
			'maxQty'      => CartNotices::t('Max Qty'),
			'products'    => CartNotices::t('Products'),
			'categories'  => CartNotices::t('Categories'),
			'dateCreated' => \Craft::t('app', 'Date Created'),
			'dateUpdated' => \Craft::t('app', 'Date Updated'),
		];
	}

	public static function defaultTableAttributes (string $source): array
	{
		$attrs = ['title'];

		switch ($source)
		{
			case Types::MinimumAmount:
				$attrs[] = 'target';
				$attrs[] = 'threshold';
				break;
			case Types::Deadline:
				$attrs[] = 'hour';
				$attrs[] = 'days';
				break;
			case Types::Referer:
				$attrs[] = 'referer';
				break;
			case Types::ProductsInCart:
				$attrs[] = 'products';
				$attrs[] = 'minQty';
				$attrs[] = 'maxQty';
				break;
			case Types::CategoriesInCart:
				$attrs[] = 'categories';
				break;
			default:
				$attrs[] = 'type';
		}

		$attrs[] = 'dateCreated';

		return $attrs;
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
		return [
			'type',
			'dateCreated',
			'dateUpdated',
			'referer',
		];
	}

	protected static function defineSources (string $context = null): array
	{
		$sources = [
			[
				'key'         => '*',
				'label'       => CartNotices::t('All Notices'),
				'criteria'    => [],
				'defaultSort' => ['dateCreated', 'desc']
			],
			[ 'heading' => CartNotices::t('Types') ]
		];

		foreach (Types::getSelectOptions() as $type => $label)
		{
			$sources[] = [
				'key' => $type,
				'label' => $label,
				'criteria' => [
					'type' => $type,
				],
				'defaultSort' => ['dateCreated', 'desc']
			];
		}

		return $sources;
	}

	// Events
	// =========================================================================

	public function afterSave (bool $isNew)
	{
		if ($isNew)
		{
			\Craft::$app->db->createCommand()
				->insert('{{%cart-notices}}', [
					'id'        => $this->id,
					'siteId'    => $this->siteId,
					'type'      => $this->type,
					'target'    => $this->target,
					'threshold' => $this->threshold,
					'hour'      => $this->hour,
					'days'      => $this->days,
					'referer'   => $this->referer,
					'minQty'    => $this->minQty,
					'maxQty'    => $this->maxQty,
				])
				->execute();
		}
		else
		{
			\Craft::$app->db->createCommand()
				->update('{{%cart-notices}}', [
					'type'      => $this->type,
					'target'    => $this->target,
					'threshold' => $this->threshold,
					'hour'      => $this->hour,
					'days'      => $this->days,
					'referer'   => $this->referer,
					'minQty'    => $this->minQty,
					'maxQty'    => $this->maxQty,
				], [
					'id'     => $this->id,
					'siteId' => $this->siteId,
				])
				->execute();
		}

		parent::afterSave($isNew);
	}


}
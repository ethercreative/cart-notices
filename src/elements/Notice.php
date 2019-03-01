<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\elements;

use craft\base\Element;
use craft\commerce\elements\Product;
use craft\elements\actions\Restore;
use craft\elements\Category;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use ether\cartnotices\CartNotices;
use ether\cartnotices\elements\db\NoticeQuery;
use ether\cartnotices\enums\Types;
use yii\db\Query;

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

	/** @var int[] - Products in Cart: The products to check */
	public $productIds = [];

	/** @var int[] - Categories in Cart: The categories to check */
	public $categoryIds = [];

	/** @var Product[] */
	private $_products;

	/** @var Category[] */
	private $_categories;

	// Getters
	// =========================================================================

	public function getProducts ()
	{
		if ($this->_products)
			return $this->_products;

		return $this->_products = Product::find()->id(
			$this->_getRelationIds('product')
		)->fixedOrder(true)->all();
	}

	public function getCategories ()
	{
		if ($this->_categories)
			return $this->_categories;

		return $this->_categories = Category::find()->id(
			$this->_getRelationIds('category')
		)->fixedOrder(true)->all();
	}

	// Methods
	// =========================================================================

	public function init ()
	{
		parent::init();

		if (is_string($this->days) && $this->days !== '*')
			$this->days = Json::decodeIfJson($this->days);
	}

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
		$url = $this->id . '/' . \Craft::$app->getSites()->getSiteById($this->siteId)->handle;

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
			case 'days':
				return $this->_daysToString();
			case 'products':
				return implode(', ', $this->getProducts());
			case 'categories':
				return implode(', ', $this->getCategories());
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
				'criteria'    => [
					'filter' => false,
				],
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
					'filter' => false,
				],
				'defaultSort' => ['dateCreated', 'desc']
			];
		}

		return $sources;
	}

	public static function eagerLoadingMap (array $sourceElements, string $handle)
	{
		if ($handle === 'products')
			return self::_getRelationEagerMap(
				'product',
				Product::class,
				$sourceElements
			);

		if ($handle === 'categories')
			return self::_getRelationEagerMap(
				'category',
				Category::class,
				$sourceElements
			);

		return parent::eagerLoadingMap($sourceElements, $handle);
	}

	public function setEagerLoadedElements (string $handle, array $elements)
	{
		if ($handle === 'products')
			$this->_products = $elements;
		else if ($handle === 'categories')
			$this->_categories = $elements;
		else
			parent::setEagerLoadedElements($handle, $elements);
	}

	// Events
	// =========================================================================

	/**
	 * @param bool $isNew
	 *
	 * @throws \Throwable
	 */
	public function afterSave (bool $isNew)
	{
		$db = \Craft::$app->getDb();

		$transaction = $db->beginTransaction();

		try
		{
			if ($isNew)
			{
				$db->createCommand()
				   ->insert('{{%cart-notices}}', [
					   'id'        => $this->id,
					   'siteId'    => $this->siteId,
					   'type'      => $this->type,
					   'target'    => $this->target,
					   'threshold' => $this->threshold,
					   'hour'      => $this->hour,
					   'days'      => Json::encode($this->days),
					   'referer'   => $this->referer,
					   'minQty'    => $this->minQty,
					   'maxQty'    => $this->maxQty,
				   ])
				   ->execute();
			}
			else
			{
				$db->createCommand()
				   ->update('{{%cart-notices}}', [
					   'type'      => $this->type,
					   'target'    => $this->target,
					   'threshold' => $this->threshold,
					   'hour'      => $this->hour,
					   'days'      => Json::encode($this->days),
					   'referer'   => $this->referer,
					   'minQty'    => $this->minQty,
					   'maxQty'    => $this->maxQty,
				   ], [
					   'id'     => $this->id,
					   'siteId' => $this->siteId,
				   ])
				   ->execute();
			}

			$this->_saveRelations(
				'product',
				$this->productIds
			);

			$this->_saveRelations(
				'category',
				$this->categoryIds
			);

			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();

			throw $e;
		}

		parent::afterSave($isNew);
	}

	// Helpers
	// =========================================================================

	/**
	 * @param $target
	 * @param $ids
	 *
	 * @throws \yii\db\Exception
	 */
	private function _saveRelations ($target, $ids)
	{
		$db = \Craft::$app->getDb();
		$table = '{{%cart-notices_notice_' . $target . '}}';

		$db->createCommand()
		   ->delete($table, [
			   'noticeId' => $this->id,
			   'siteId'   => $this->siteId,
		   ])
		   ->execute();

		$i = 0;
		foreach ($ids as $id)
		{
			$values[] = [
				$this->id,
				$this->siteId,
				$id,
				$i++
			];
		}

		if (!empty($values))
		{
			$columns = [
				'noticeId',
				'siteId',
				$target . 'Id',
				'sortOrder',
			];

			$db->createCommand()
			   ->batchInsert($table, $columns, $values)
			   ->execute();
		}
	}

	private function _getRelationIds ($target)
	{
		return (new Query())
			->select($target . 'Id')
			->from('{{%cart-notices_notice_' . $target . '}}')
			->where([
				'siteId'   => $this->siteId,
				'noticeId' => $this->id,
			])
			->orderBy('sortOrder asc')
			->column();
	}

	private static function _getRelationEagerMap ($target, $elementType, $sourceElements)
	{
		$sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

		$map = (new Query())
			->select(['noticeId as source', $target . 'Id as target'])
			->from(['{{%cart-notices_notice_' . $target . '}}'])
			->where(
				[
					'and',
					[$target . 'Id' => $sourceElementIds],
				]
			)
			->all();

		return [
			'elementType' => $elementType,
			'map'         => $map
		];
	}

	private function _daysToString ()
	{
		if ($this->days === '*')
			return CartNotices::t('Everyday');

		if (count($this->days) === 5 && count(array_intersect(['6','7'], $this->days)) === 0)
			return CartNotices::t('Weekdays');

		if (count(array_intersect(['6', '7'], $this->days)) === count($this->days))
			return CartNotices::t('Weekends');

		$daysOfWeek = [
			null,
			CartNotices::t('Monday'),
			CartNotices::t('Tuesday'),
			CartNotices::t('Wednesday'),
			CartNotices::t('Thursday'),
			CartNotices::t('Friday'),
			CartNotices::t('Saturday'),
			CartNotices::t('Sunday'),
		];

		return implode(', ', array_map(function ($a) use ($daysOfWeek) {
			return $daysOfWeek[$a];
		}, $this->days));
	}

}
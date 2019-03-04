<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\elements\db;

use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\Site;
use ether\cartnotices\enums\Types;

/**
 * Class NoticeQuery
 *
 * @author  Ether Creative
 * @package ether\cartnotices\elements\db
 */
class NoticeQuery extends ElementQuery
{

	// Properties
	// =========================================================================

	/** @var string */
	public $type;

	/** @var Order */
	public $cart;

	/** @var bool */
	public $filter = false;

	// Setters
	// =========================================================================

	public function type ($value)
	{
		$this->type = $value;

		return $this;
	}

	public function filter (bool $value)
	{
		$this->filter = $value;

		return $this;
	}

	public function cart ($value)
	{
		if ($value instanceof Order)
			$this->cart = $value;
		else
			$this->cart = Order::find($value)->one();

		return $this;
	}

	// Events
	// =========================================================================

	/**
	 * @return bool
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 */
	protected function beforePrepare (): bool
	{
		$this->joinElementTable('cart-notices');

		$this->query->select([
			'cart-notices.*',
		]);

		if ($this->type)
		{
			$this->subQuery->andWhere(
				Db::parseParam('cart-notices.type', $this->type)
			);
		}

		if (!$this->filter)
			return parent::beforePrepare();

		$cart = $this->cart;

		if (!$cart || !($cart instanceof Order))
			$cart = Commerce::getInstance()->getCarts()->getCart();

		// Minimum Amount
		// ---------------------------------------------------------------------

		$sql = <<<SQL
[[cart-notices.type]] = :type1 AND 
:total <= [[cart-notices.target]] AND
:total >= [[cart-notices.threshold]]
SQL;

		$this->subQuery->orWhere(
			$sql,
			[
				'type1' => Types::MinimumAmount,
				'total' => $cart->totalPrice
			]
		);

		// Deadline
		// -------------------------------------------------------------------------

		$sql = <<<SQL
[[cart-notices.type]] = :type2 AND 
:hour < [[cart-notices.hour]] AND
([[cart-notices.days]] LIKE '%*%' OR [[cart-notices.days]] LIKE :day)
SQL;

		$now = (new \DateTime());
		$w = $now->format('w');
		if ($w === '0') $w = 7;
		$this->subQuery->orWhere(
			$sql,
			[
				'type2' => Types::Deadline,
				'hour' => $now->format('G'),
				'day' => '%' . $w . '%',
			]
		);

		// Referer
		// ---------------------------------------------------------------------

		$referer = \Craft::$app->request->getReferrer();
		$referer = preg_replace('#^https?://#', '', $referer);
		$referer = rtrim($referer, '/');

		if ($referer)
		{
			$sql = <<<SQL
[[cart-notices.type]] = :type3 AND 
([[cart-notices.referer]] LIKE :refererLike OR :referer LIKE CONCAT('%', [[cart-notices.referer]], '%'))
SQL;

			$this->subQuery->orWhere(
				$sql,
				[
					'type3'   => Types::Referer,
					'refererLike' => '%' . $referer . '%',
					'referer' => $referer,
				]
			);
		}

		// Products in Cart
		// ---------------------------------------------------------------------

		$productIds = implode(
			'\',\'',
			$this->_lineItemProductIds($cart->lineItems)
		);

		$sql = <<<SQL
[[cart-notices.type]] = :type4 AND 
[[cart-notices.id]] IN (
	SELECT [[np.noticeId]]
	FROM {{%cart-notices_notice_product}} [[np]]
	INNER JOIN {{%cart-notices}} [[cn]] ON [[cn.id]] = [[np.noticeId]]
	INNER JOIN {{%commerce_variants}} [[cv]] ON [[np.productId]] = [[cv.productId]]
	INNER JOIN {{%commerce_lineitems}} [[li]] ON [[li.purchasableId]] = [[cv.id]]
	WHERE [[np.productId]] IN ('$productIds')
	AND [[li.qty]] >= [[cn.minQty]] AND [[li.qty]] <= [[cn.maxQty]]
)
SQL;

		$this->subQuery->orWhere(
			$sql,
			[
				'type4' => Types::ProductsInCart,
			]
		);


		// Categories in Cart
		// ---------------------------------------------------------------------

		$purchasableIds = implode(
			'\',\'',
			$this->_lineItemPurchasableIds($cart->lineItems)
		);

		$sql = <<<SQL
[[cart-notices.type]] = :type5 AND
[[cart-notices.id]] IN (
	SELECT [[nc.noticeId]]
	FROM {{%relations}} [[r]]
	INNER JOIN {{%cart-notices_notice_category}} [[nc]] ON [[r.targetId]] = [[nc.categoryId]]
	WHERE [[r.sourceId]] IN ('$productIds','$purchasableIds')
)
SQL;

		$this->subQuery->orWhere(
			$sql,
			[
				'type5' => Types::CategoriesInCart,
			]
		);

		return parent::beforePrepare();
	}

	// Helpers
	// =========================================================================

	private function _lineItemProductIds (array $lineItems)
	{
		return array_reduce(
			$lineItems,
			function (array $a, LineItem $item) {
				/** @var Variant $variant */
				$variant = $item->purchasable;

				if (!($variant instanceof Variant))
					return $a;

				$a[] = $variant->productId;

				return $a;
			},
			[]
		);
	}

	private function _lineItemPurchasableIds (array $lineItems)
	{
		return array_map(function (LineItem $item) {
			return $item->purchasableId;
		}, $lineItems);
	}

}
<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\elements\db;

use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use ether\cartnotices\enums\Types;
use yii\db\Expression;

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
	public $filter = true;

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

		// TODO: line item purchasable product ids related to notices

		// Categories in Cart
		// ---------------------------------------------------------------------

		// TODO: line item purchasable ids related to categories related to notices
		//  and: line item purchasable product ids related to categories related to notices

		return parent::beforePrepare();
	}

}
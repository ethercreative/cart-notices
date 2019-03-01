<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

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

	// Setters
	// =========================================================================

	public function type ($type)
	{
		$this->type = $type;

		return $this;
	}

	// Events
	// =========================================================================

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

		return parent::beforePrepare();
	}

}
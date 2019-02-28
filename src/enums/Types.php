<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\enums;

use ether\cartnotices\CartNotices;

/**
 * Class Types
 *
 * @author  Ether Creative
 * @package ether\cartnotices\enums
 */
abstract class Types
{

	// Consts
	// =========================================================================

	const MinimumAmount    = 'minimum-amount';
	const Deadline         = 'deadline';
	const Referer          = 'referer';
	const ProductsInCart   = 'products-in-cart';
	const CategoriesInCart = 'categories-in-cart';

	// Helpers
	// =========================================================================

	public static function getSelectOptions ()
	{
		return [
			self::MinimumAmount    => self::toLabel(self::MinimumAmount),
			self::Deadline         => self::toLabel(self::Deadline),
			self::Referer          => self::toLabel(self::Referer),
			self::ProductsInCart   => self::toLabel(self::ProductsInCart),
			self::CategoriesInCart => self::toLabel(self::CategoriesInCart),
		];
	}

	public static function toLabel ($type)
	{
		switch ($type)
		{
			case self::MinimumAmount:
				return CartNotices::t('Minimum Amount');
			case self::Deadline:
				return CartNotices::t('Deadline');
			case self::Referer:
				return CartNotices::t('Referer');
			case self::ProductsInCart:
				return CartNotices::t('Products in Cart');
			case self::CategoriesInCart:
				return CartNotices::t('Categories in Cart');
		}

		return null;
	}

}
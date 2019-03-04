<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\web\twig;

use ether\cartnotices\elements\db\NoticeQuery;
use ether\cartnotices\elements\Notice;
use yii\base\Behavior;

/**
 * Class CraftVariableBehavior
 *
 * @author  Ether Creative
 * @package ether\cartnotices\web\twig
 */
class CraftVariableBehavior extends Behavior
{

	public function notices ($criteria = null): NoticeQuery
	{
		$query = Notice::find()->filter(true);

		if ($criteria)
			\Craft::configure($query, $criteria);

		return $query;
	}

}
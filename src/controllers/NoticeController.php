<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\controllers;

use craft\web\Controller;
use ether\cartnotices\elements\Notice;
use yii\web\NotFoundHttpException;

/**
 * Class NoticeController
 *
 * @author  Ether Creative
 * @package ether\cartnotices\controllers
 */
class NoticeController extends Controller
{
	
	public function actionEdit (
		int $noticeId = null,
		string $siteHandle = null,
		Notice $notice = null
	) {
		$variables = [
			'noticeId' => $noticeId,
			'notice'   => $notice,
		];

		if ($siteHandle !== null)
		{
			$variables['site'] = \Craft::$app->getSites()->getSiteByHandle($siteHandle);

			if (!$variables['site'])
				throw new NotFoundHttpException(
					'Invalid site handle: ' . $siteHandle
				);
		}

		// TODO: this
	}

}
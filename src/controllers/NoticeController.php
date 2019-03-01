<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\controllers;

use craft\base\Element;
use craft\errors\InvalidElementException;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use ether\cartnotices\CartNotices;
use ether\cartnotices\elements\Notice;
use ether\cartnotices\enums\Types;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class NoticeController
 *
 * @author  Ether Creative
 * @package ether\cartnotices\controllers
 */
class NoticeController extends Controller
{

	public function actionIndex ()
	{
		$types = [];

		foreach (Types::getSelectOptions() as $handle => $name)
			$types[] = compact('handle', 'name');

		return $this->renderTemplate('cart-notices/_index', [
			'defaultType' => Types::MinimumAmount,
			'noticeTypes' => $types,
		]);
	}

	/**
	 * @param int|null    $noticeId
	 * @param string|null $siteHandle
	 * @param Notice|null $notice
	 *
	 * @return string
	 * @throws NotFoundHttpException
	 * @throws \craft\errors\SiteNotFoundException
	 */
	public function actionEdit (
		int $noticeId = null,
		string $siteHandle = null,
		Notice $notice = null
	) {
		$variables = [
			'noticeId' => $noticeId,
			'fullPageForm' => true,
			'fieldLayout' => \Craft::$app->getFields()->getLayoutByType(Notice::class),
			'typeOptions' => Types::getSelectOptions(),
		];

		if ($siteHandle !== null)
		{
			$variables['site'] = \Craft::$app->getSites()->getSiteByHandle($siteHandle);

			if (!$variables['site'])
				throw new NotFoundHttpException(
					'Invalid site handle: ' . $siteHandle
				);
		}
		else
		{
			$variables['site'] = \Craft::$app->getSites()->getPrimarySite();
		}

		// Breadcrumbs
		$variables['crumbs'] = [
			[
				'label' => CartNotices::t('Cart Notices'),
				'url'   => UrlHelper::url('cart-notices'),
			]
		];

		// Notice
		if ($notice)
		{
			$variables['notice'] = $notice;
		}
		else
		{
			if ($noticeId)
			{
				$variables['notice'] = $this->_notice(
					$noticeId,
					$variables['site']->id
				);

				if (!$variables['notice'])
					throw new NotFoundHttpException('Notice not found');

				$variables['title'] = $variables['notice']->title;
			}
			else
			{
				$variables['notice']         = new Notice();
				$variables['notice']->siteId = $variables['site']->id;

				$variables['title'] = CartNotices::t('Create a new notice');
			}
		}

		// Type
		if ($noticeId || $notice)
		{
			$variables['type'] = $variables['notice']->type;
		}
		else
		{
			$variables['type'] = \Craft::$app->request->getQueryParam(
				'type',
				$variables['notice']->type
			);
		}

		// Urls
		$variables['nextNoticeUrl'] = UrlHelper::url('cart-notices/new');
		$variables['continueEditingUrl'] = 'cart-notices/{id}';

		if (\Craft::$app->isMultiSite)
		{
			$variables['continueEditingUrl'] .= '/{site.handle}';
			$variables['nextNoticeUrl']      .= '/' . $variables['site']->handle;
		}

		$variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

		return $this->renderTemplate(
			'cart-notices/_edit',
			$variables
		);
	}

	public function actionSave ()
	{
		$this->requirePostRequest();
		$request = \Craft::$app->request;

		$noticeId = $request->getBodyParam('noticeId');
		$siteId = $request->getBodyParam(
			'siteId',
			\Craft::$app->getSites()->getPrimarySite()->id
		);

		// Get Notice
		if ($noticeId)
		{
			$notice = $this->_notice($noticeId, $siteId);

			if (!$notice)
				throw new NotFoundHttpException('Notice not found');
		}
		else
		{
			$notice = new Notice();
			$notice->siteId = $siteId;
		}

		// Duplicate?
		if ((bool) $request->getBodyParam('duplicate'))
		{
			try
			{
				$notice = \Craft::$app->elements->duplicateElement($notice);
			} catch (InvalidElementException $e) {
				/** @var Notice $clone */
				$clone = $e->element;

				if ($request->getAcceptsJson())
					return $this->asJson(
						[
							'success' => false,
							'errors'  => $clone->getErrors(),
						]
					);

				\Craft::$app->session->setError(
					CartNotices::t('Couldn\'t duplicate notice')
				);

				$notice->addErrors($clone->getErrors());

				\Craft::$app->urlManager->setRouteParams([
					'notice' => $notice,
				]);

				return null;
			} catch (\Throwable $e) {
				throw new ServerErrorHttpException(
					'An error occurred when duplicating the notice.',
					0,
					$e
				);
			}
		}

		// Populate
		$notice->title = $request->getBodyParam('title', $notice->title);
		$notice->setFieldValuesFromRequest(
			$request->getParam('fieldsLocation', 'fields')
		);

		$notice->type        = $request->getParam('type');
		$notice->target      = $request->getParam('target');
		$notice->threshold   = $request->getParam('threshold');
		$notice->hour        = $request->getParam('hour');
		$notice->days        = $request->getParam('days');
		$notice->referer     = $request->getParam('referer');
		$notice->minQty      = $request->getParam('minQty');
		$notice->maxQty      = $request->getParam('maxQty');
		$notice->productIds  = $request->getParam('products', []);
		$notice->categoryIds = $request->getParam('categories', []);

		if (!is_array($notice->productIds)) $notice->productIds = [];
		if (!is_array($notice->categoryIds)) $notice->categoryIds = [];

		// Save
		if (!\Craft::$app->elements->saveElement($notice))
		{
			if ($request->getAcceptsJson())
				return $this->asJson([
					'errors' => $notice->getErrors(),
				]);

			\Craft::$app->getSession()->setError(
				CartNotices::t('Couldn\'t save notice')
			);

			// Send the entry back to the template
			\Craft::$app->getUrlManager()->setRouteParams([
				'notice' => $notice
			]);

			return null;
		}

		if ($request->getAcceptsJson())
		{
			return $this->asJson([
				'success' => true,
				'notice' => $notice,
			]);
		}
		\Craft::$app->getSession()->setNotice(
			CartNotices::t('Notice saved')
		);

		return $this->redirectToPostedUrl(compact('notice'));
	}

	/**
	 * @return \yii\web\Response|null
	 * @throws NotFoundHttpException
	 * @throws \Throwable
	 * @throws \yii\web\BadRequestHttpException
	 */
	public function actionDelete ()
	{
		$this->requirePostRequest();
		$request = \Craft::$app->request;

		$noticeId = $request->getBodyParam('noticeId');
		$siteId   = $request->getBodyParam('siteId');

		$notice = $this->_notice($noticeId, $siteId);

		if (!$notice)
			throw new NotFoundHttpException('Notice not found');

		if (!\Craft::$app->elements->deleteElement($notice))
		{
			if ($request->getAcceptsJson())
				return $this->asJson(['success' => false]);

			\Craft::$app->session->setError(
				CartNotices::t('Couldn\'t delete notice.')
			);

			// Send the entry back to the template
			\Craft::$app->getUrlManager()->setRouteParams([
				'notice' => $notice
			]);

			return null;
		}

		if ($request->getAcceptsJson())
			return $this->asJson(['success' => true]);

		\Craft::$app->session->setNotice(
			CartNotices::t('Notice deleted')
		);

		return $this->redirectToPostedUrl($notice);
	}

	// Helpers
	// =========================================================================

	private function _notice ($id, $siteId = null)
	{
		$notice = Notice::find()
			->id($id)
			->filter(false);

		if ($siteId)
			$notice->siteId($siteId);

		return $notice->one();
	}

}
<?php
/**
 * Cart Notices for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\cartnotices\migrations;

use craft\db\Migration;

/**
 * Class Install
 *
 * @author  Ether Creative
 * @package ether\cartnotices\migrations
 */
class Install extends Migration
{

	public function safeUp ()
	{
		if ($this->db->tableExists('{{%cart-notices}}'))
			return false;

		$this->createTable('{{%cart-notices}}', [
			'id'     => $this->primaryKey(),
			'siteId' => $this->integer()->notNull(),

			'type'      => $this->string()->notNull(),
			'target'    => $this->float(),
			'threshold' => $this->float(),
			'hour'      => $this->integer(),
			'days'      => $this->string(),
			'referer'   => $this->string(255),
			'minQty'    => $this->integer(),
			'maxQty'    => $this->integer(),

			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid'         => $this->uid(),
		]);

		$this->createTable('{{%cart-notices_notice_product}}', [
			'id'        => $this->primaryKey(),
			'siteId'    => $this->integer()->notNull(),

			'noticeId'  => $this->integer()->notNull(),
			'productId' => $this->integer()->notNull(),
			'sortOrder' => $this->smallInteger()->null(),

			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid'         => $this->uid(),
		]);

		$this->createTable('{{%cart-notices_notice_category}}', [
			'id'        => $this->primaryKey(),
			'siteId'    => $this->integer()->notNull(),

			'noticeId'   => $this->integer()->notNull(),
			'categoryId' => $this->integer()->notNull(),
			'sortOrder'  => $this->smallInteger()->null(),

			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid'         => $this->uid(),
		]);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%cart-notices}}', 'id'),
			'{{%cart-notices}}',
			'id',
			'{{%elements}}',
			'id',
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%cart-notices}}', 'siteId'),
			'{{%cart-notices}}',
			'siteId',
			'{{%sites}}',
			'id',
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%cart-notices_notice_product}}', 'noticeId'),
			'{{%cart-notices_notice_product}}',
			'noticeId',
			'{{%cart-notices}}',
			'id',
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%cart-notices_notice_product}}', 'productId'),
			'{{%cart-notices_notice_product}}',
			'productId',
			'{{%commerce_products}}',
			'id',
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%cart-notices_notice_category}}', 'noticeId'),
			'{{%cart-notices_notice_category}}',
			'noticeId',
			'{{%cart-notices}}',
			'id',
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%cart-notices_notice_category}}', 'categoryId'),
			'{{%cart-notices_notice_category}}',
			'categoryId',
			'{{%categories}}',
			'id',
			'CASCADE',
			null
		);

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%cart-notices_notice_product}}');
		$this->dropTableIfExists('{{%cart-notices_notice_category}}');
		$this->dropTableIfExists('{{%cart-notices}}');

		return true;
	}

}
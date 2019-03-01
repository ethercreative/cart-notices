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

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%cart-notices}}');

		return true;
	}

}
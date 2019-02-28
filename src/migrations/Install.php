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
			'id' => $this->primaryKey(),

			'type' => $this->string()->notNull(),

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

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%cart-notices}}');

		return true;
	}

}
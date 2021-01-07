<?php

namespace ether\cartnotices\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210107_092937_add_exclude_columns_to_notices_table migration.
 */
class m210107_092937_add_exclude_columns_to_notices_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
        	'{{%cart-notices}}',
	        'excludeTax',
	        $this->boolean()
        );
        $this->addColumn(
        	'{{%cart-notices}}',
	        'excludeShipping',
	        $this->boolean()
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%cart-notices}}', 'excludeTax');
        $this->dropColumn('{{%cart-notices}}', 'excludeShipping');
    }
}

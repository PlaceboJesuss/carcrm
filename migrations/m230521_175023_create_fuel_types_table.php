<?php

use yii\db\Schema;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%fuel_types}}`.
 */
class m230521_175023_create_fuel_types_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%fuel_types}}', [
            'id' => $this->primaryKey(),
            'name' => $this->text()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%fuel_types}}');
    }
}

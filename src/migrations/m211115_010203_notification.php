<?php

use yii\db\Schema;
use yii\db\Migration;
use portalium\notification\Module;


class m211115_010203_notification extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';

        $this->createTable(
            Module::$tablePrefix . "read",
            [
                'id_read'=> $this->primaryKey(),
                'id_user'=> $this->integer()->notNull(),
                'created_at' => $this->dateTime()->notNull(),
            ],
            $tableOptions
        );

        $this->createTable(
            Module::$tablePrefix . "notification",
            [
                'id_notification'=> $this->primaryKey(),
                'type'=> $this->integer()->notNull(),
                'id_to'=> $this->integer()->notNull(),
                'text'=> $this->string()->notNull(),
                'title'=> $this->string()->notNull(),
                
            ],
            $tableOptions
        );

        $this->addForeignKey(
            'fk-id_read',
            'notification_read',
            'id_user',
            'user_user',
            'id_user',
            'CASCADE'
        );

    }

    public function safeDown()
    {
        $this->dropTable('{{%' . Module::$tablePrefix . 'notification}}');
    }
}
?>
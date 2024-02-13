<?php

use yii\db\Migration;
use portalium\user\Module as UserModule;
use portalium\notification\Module;


class m211115_010203_notification extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';

        $this->createTable(
            Module::$tablePrefix . "notification",
            [
                'id_notification'=> $this->primaryKey(),
                'id_to'=> $this->integer()->notNull(),
                'text'=> $this->string()->notNull(),
                'title'=> $this->string()->notNull(),
                'status'=> $this->integer()->notNull()->defaultValue(0),
            ],
            $tableOptions
        );


        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'id_user}}',
            '{{%' . Module::$tablePrefix . 'notification}}',
            'id_to',
            '{{%' . UserModule::$tablePrefix . 'user}}',
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
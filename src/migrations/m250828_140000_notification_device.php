<?php

use yii\db\Migration;
use portalium\notification\Module;
use portalium\user\Module as UserModule;
use portalium\workspace\Module as WorkspaceModule;

/**
 * Notification Device tablosunu oluşturan migration
 * Kullanıcıların cihazlarının FCM token bilgilerini tutar
 */
class m250828_140000_notification_device extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';

        $this->createTable(
            Module::$tablePrefix . "notification_device",
            [
                'id_notification_device' => $this->primaryKey(),
                'id_user' => $this->integer()->notNull(),
                'id_workspace' => $this->integer()->notNull(),
                'device_token' => $this->string(1024)->notNull()->comment('FCM Device Token'),
                'device_type' => $this->string(50)->notNull()->comment('android, ios, web'),
                'device_name' => $this->string(255)->comment('Cihaz adı (isteğe bağlı)'),
                'device_model' => $this->string(255)->comment('Cihaz modeli (isteğe bağlı)'),
                'app_version' => $this->string(50)->comment('Uygulama versiyonu'),
                'os_version' => $this->string(50)->comment('İşletim sistemi versiyonu'),
                'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1)->comment('Cihaz aktif mi?'),
                'last_used_at' => $this->dateTime()->comment('Son kullanım tarihi'),
                'date_create' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'date_update' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
            ],
            $tableOptions
        );

        // User tablosu ile foreign key
        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'notification_device_user}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            'id_user',
            '{{%' . UserModule::$tablePrefix . 'user}}',
            'id_user',
            'CASCADE',
            'CASCADE'
        );

        // Workspace tablosu ile foreign key
        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'notification_device_workspace}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            'id_workspace',
            '{{%' . WorkspaceModule::$tablePrefix . 'workspace}}',
            'id_workspace',
            'CASCADE',
            'CASCADE'
        );

        // Device token'ın unique olması için index
        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'notification_device_token}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            ['device_token', 'id_user', 'id_workspace'],
            true // unique
        );

        // Performans için indexler
        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'notification_device_user}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            'id_user'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'notification_device_workspace}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            'id_workspace'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'notification_device_active}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            'is_active'
        );

        // User + Workspace + Device Type için composite index
        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'notification_device_composite}}',
            '{{%' . Module::$tablePrefix . 'notification_device}}',
            ['id_user', 'id_workspace', 'device_type', 'is_active']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%' . Module::$tablePrefix . 'notification_device}}');
    }
}

<?php

use portalium\db\Migration;
use portalium\menu\models\Menu;
use portalium\menu\models\MenuItem;

/**
 * Class m240731_153143_notification_menu
 */
class m240731_153143_notification_menu extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $id_item = MenuItem::find()->where(['slug' => 'notification'])->one();

        if(!$id_item){
            $this->insert('menu_item', [
                'id_item' => NULL,
                'label' => 'Notification',
                'slug' => 'notification',
                'type' => '2',
                'style' => '{"icon":"fa fa-bell","color":"black","iconSize":"","display":"1","childDisplay":"1", "placement":"1"}',
                'data' => '{"data":{"module":"notification","routeType":"widget","route":"portalium\\\\notification\\\\widgets\\\\Notification","model":"","menuRoute":null,"menuType":null}}',
                'sort' => '16',
                'id_menu' => '1',
                'name_auth' => 'user',
                'id_user' => '1',
                'date_create' => '2024-01-30 12:33:19',
                'date_update' => '2024-05-07 16:30:22',
            ]);
        } else {
            $id_item = $id_item->id_item;
        }
        $id_item = MenuItem::find()->where(['slug' => 'notification'])->one()->id_item;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        $id_item = MenuItem::find()->where(['slug' => 'notification'])->one()->id_item;
        $this->delete('menu_item', ['id_item' => $id_item]);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240731_153143_notification_menu cannot be reverted.\n";

        return false;
    }
    */
}

<?php

use yii\db\Migration;
use portalium\site\Module;
use portalium\site\models\Form;

/**
 * Class m240930_183451_notification_site_setting
 */
class m240930_183451_notification_site_setting extends Migration
{
    public function up()
    {
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'site',
            'name' => 'notification::email_enabled',
            'label' => 'Enable Email Notifications',
            'value' => '0',
            'type' => Form::TYPE_RADIOLIST,
            'config' => json_encode([1 => 'Yes', 0 => 'No']),
            'is_preference' => 1, 
        ]);
    }

    public function down()
    {
        $this->delete(Module::$tablePrefix . 'setting', [
            'name' => 'notification::email_enabled',
        ]);
    }
}

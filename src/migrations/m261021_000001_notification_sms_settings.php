<?php

use yii\db\Migration;
use yii\db\Query;
use portalium\site\models\Form;
use portalium\site\Module as SiteModule;

class m261021_000001_notification_sms_settings extends Migration
{
    public function safeUp()
    {
        $settings = [
            [
                'name' => 'notification::sms_enabled',
                'label' => 'SMS Enabled',
                'value' => '0',
                'type' => Form::TYPE_CHECKBOX,
                'config' => json_encode(['help' => 'Enable SMS sending']),
            ],
            [
                'name' => 'notification::sms_provider',
                'label' => 'SMS Provider',
                'value' => 'netgsm',
                'type' => Form::TYPE_DROPDOWNLIST,
                'config' => json_encode([
                    'netgsm' => 'Netgsm',
                    'verimor' => 'Verimor',
                ]),
            ],
            [
                'name' => 'notification::sms_netgsm_usercode',
                'label' => 'Netgsm User Code',
                'value' => '',
                'type' => Form::TYPE_INPUTTEXT,
                'config' => '',
            ],
            [
                'name' => 'notification::sms_netgsm_password',
                'label' => 'Netgsm Password',
                'value' => '',
                'type' => Form::TYPE_INPUTPASSWORD,
                'config' => '',
            ],
            [
                'name' => 'notification::sms_netgsm_msgheader',
                'label' => 'Netgsm Sender Header',
                'value' => '',
                'type' => Form::TYPE_INPUTTEXT,
                'config' => '',
            ],
            [
                'name' => 'notification::sms_verimor_username',
                'label' => 'Verimor Username',
                'value' => '',
                'type' => Form::TYPE_INPUTTEXT,
                'config' => '',
            ],
            [
                'name' => 'notification::sms_verimor_password',
                'label' => 'Verimor API Password',
                'value' => '',
                'type' => Form::TYPE_INPUTPASSWORD,
                'config' => '',
            ],
            [
                'name' => 'notification::sms_verimor_source_addr',
                'label' => 'Verimor Sender Address',
                'value' => '',
                'type' => Form::TYPE_INPUTTEXT,
                'config' => '',
            ],
        ];

        foreach ($settings as $setting) {
            $exists = (new Query())->from(SiteModule::$tablePrefix . 'setting')
                ->where(['name' => $setting['name']])
                ->exists($this->db);

            if (!$exists) {
                $this->insert(SiteModule::$tablePrefix . 'setting', [
                    'module' => 'notification',
                    'name' => $setting['name'],
                    'label' => $setting['label'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'config' => $setting['config'],
                ]);
            }
        }
    }

    public function safeDown()
    {
        $this->delete(SiteModule::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => [
                'notification::sms_enabled',
                'notification::sms_provider',
                'notification::sms_netgsm_usercode',
                'notification::sms_netgsm_password',
                'notification::sms_netgsm_msgheader',
                'notification::sms_verimor_username',
                'notification::sms_verimor_password',
                'notification::sms_verimor_source_addr',
            ],
        ]);
    }
}

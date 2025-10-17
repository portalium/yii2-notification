<?php

use yii\db\Migration;
use portalium\site\Module;
use portalium\site\models\Form;

/**
 * FCM (Firebase Cloud Messaging) ayarlarını setting tablosuna ekleyen migration
 */
class m250828_120000_fcm_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // FCM Sender ID
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_sender_id',
            'label' => 'FCM Sender ID',
            'value' => '',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'Firebase Cloud Messaging Sender ID',
                'help' => 'Firebase Console > Project Settings > Cloud Messaging > Sender ID'
            ])
        ]);

        // FCM v1 API URL
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_api_url',
            'label' => 'FCM v1 API URL',
            'value' => 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'FCM v1 API URL Template',
                'help' => 'v1 API Template: https://fcm.googleapis.com/v1/projects/{project_id}/messages:send'
            ])
        ]);

        // FCM Project ID
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_project_id',
            'label' => 'FCM Project ID',
            'value' => '',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'Firebase Project ID',
                'help' => 'Firebase Console > Project Settings > General > Project ID'
            ])
        ]);

        // FCM Web API Key
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_web_api_key',
            'label' => 'FCM Web API Key',
            'value' => '',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'Firebase Web API Key',
                'help' => 'Firebase Console > Project Settings > General > Web API Key'
            ])
        ]);

        // FCM Service Account JSON Path
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_service_account_json',
            'label' => 'FCM Service Account JSON',
            'value' => '',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'Firebase Service Account JSON content',
                'help' => 'Firebase Console > Project Settings > Service accounts > Generate private key'
            ])
        ]);

        // FCM Enabled/Disabled
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_enabled',
            'label' => 'FCM Enabled',
            'value' => '0',
            'type' => Form::TYPE_CHECKBOX,
            'config' => json_encode([
                'help' => 'Enable/Disable Firebase Cloud Messaging'
            ])
        ]);

        // FCM Default Sound
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_default_sound',
            'label' => 'FCM Default Sound',
            'value' => 'default',
            'type' => Form::TYPE_DROPDOWNLIST,
            'config' => json_encode([
                'default' => 'Default',
                'notification' => 'Notification',
                'alarm' => 'Alarm',
                'ringtone' => 'Ringtone'
            ])
        ]);

        // FCM Default Priority
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_default_priority',
            'label' => 'FCM Default Priority',
            'value' => 'high',
            'type' => Form::TYPE_DROPDOWNLIST,
            'config' => json_encode([
                'high' => 'High Priority',
                'normal' => 'Normal Priority'
            ])
        ]);

        // FCM Notification Icon
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_icon',
            'label' => 'FCM Notification Icon',
            'value' => 'ic_notification',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'Notification icon name',
                'help' => 'Android notification icon resource name (e.g., ic_notification)'
            ])
        ]);

        // FCM Notification Color
        $this->insert(Module::$tablePrefix . 'setting', [
            'module' => 'notification',
            'name' => 'notification::fcm_color',
            'label' => 'FCM Notification Color',
            'value' => '#FF6600',
            'type' => Form::TYPE_INPUTTEXT,
            'config' => json_encode([
                'placeholder' => 'Notification color (hex)',
                'help' => 'Android notification color in hex format (e.g., #FF6600)'
            ])
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // FCM ayarlarını sil
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_sender_id']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_api_url']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_project_id']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_web_api_key']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_service_account_json']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_enabled']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_default_sound']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_default_priority']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_icon']);
        $this->delete(Module::$tablePrefix . 'setting', ['module' => 'notification', 'name' => 'notification::fcm_color']);
    }
}

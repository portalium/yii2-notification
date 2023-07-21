<?php

namespace portalium\notification\models;

use portalium\user\models\User;
use Yii;
use portalium\notification\Module;

/**
 * This is the model class for table "notification_notification".
 *
 * @property int $id_notification
 * @property int $type
 * @property int $id_to
 * @property string $text
 * @property string $title
 */
class Notification extends \yii\db\ActiveRecord
{
    const TYPE = [
        'user' => '1',
        'group' => '2'
    ];
    public static function getTypes()
    {
        return [
            '1' => Module::t('User'),
            '2' => Module::t('Group')
        ];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{' . Module::$tablePrefix . 'notification}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_to', 'text', 'title'], 'required'],
            [['id_to'], 'integer'],
            [['text', 'title'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_notification' => 'Id Notification',
            'id_to' => 'Id To',
            'text' => 'Text',
            'title' => 'Title',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id_user' => 'id_to']);
    }

    public static function getUserList()
    {
        $items = [];
        $users = User::find()->all();
        foreach ($users as $user) {
            $items[$user->id_user] = $user->first_name. " " .$user->last_name;
        }
        return $items;
    }
}
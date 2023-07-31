<?php

namespace portalium\notification\models;

use portalium\notification\models\Notification as notificationModel;
use portalium\user\models\User;
use Yii;
use portalium\notification\Module;
use yii\web\NotFoundHttpException;

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

    public static function getRelatedNotifications(){
        return notificationModel::find()->where([ 'id_to'  => Yii::$app->user->id])->all();
    }

    public static function getAllNotifications(){
        return notificationModel::find()->all();
    }

    public static function findModel($id)
    {
        if (($model = Notification::findOne(['id_notification' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested page does not exist.'));
    }
}
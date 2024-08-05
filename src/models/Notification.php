<?php

namespace portalium\notification\models;

use portalium\user\models\User;
use Yii;
use portalium\notification\Module;
use yii\web\NotFoundHttpException;

class Notification extends \yii\db\ActiveRecord
{
    const STATUS_UNREAD = 0;
    const STATUS_READ = 1;

    public static function tableName()
    {
        return '{{' . Module::$tablePrefix . 'notification}}';
    }

    public function rules()
    {
        return [
            [['id_to', 'text', 'title'], 'required'],
            [['id_to', 'status'], 'integer'],
            [['text', 'title'], 'string'],
            [['id_to'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_to' => 'id_user']],
        ];
    }
    public function attributeLabels()
    {
        return [
            'id_notification' => 'Id Notification',
            'id_to' => 'Id To',
            'text' => 'Text',
            'title' => 'Title',
            'status' => 'Status',
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
        return self::find()->where([ 'id_to'  => Yii::$app->user->id])->all();
    }

    public static function getAllNotifications(){
        return self::find()->all();
    }

    public static function getUnreadNotifications(){
        //var_dump(self::find()->where([ 'id_to'  => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])->createCommand()->getRawSql());
        return self::find()->where([ 'id_to'  => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])->all();
    }

    public static function getReadNotifications(){
        return self::find()->where([ 'id_to'  => Yii::$app->user->id, 'status' => self::STATUS_READ])->all();
    }

    public static function getStatusList()
    {
        return [
            self::STATUS_UNREAD => Module::t('Unread'),
            self::STATUS_READ => Module::t('Read'),
        ];
    }

    public static function findModel($id)
    {
        if (($model = Notification::findOne(['id_notification' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested page does not exist.'));
    }
}
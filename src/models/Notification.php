<?php

namespace portalium\notification\models;

use portalium\content\models\Category as ModelsCategory;
use portalium\user\models\User;
use portalium\workspace\models\Workspace;
use Yii;
use portalium\notification\Module;
use portalium\rbac\models\Assignment;
use portalium\rbac\models\AuthItemSearch;
use portalium\user\models\Group;
use portalium\user\models\UserGroup;
use yii\web\NotFoundHttpException;




class Notification extends \yii\db\ActiveRecord
{
    public $recipients;
    const STATUS_UNREAD = 0;
    const STATUS_READ = 1;

    const NOTIFICATION_TYPE_USER = 1;
    const NOTIFICATION_TYPE_ROLE = 2;
    const NOTIFICATION_TYPE_GROUP = 3;

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
            'id_notification' => Module::t('Id Notification'),
            'id_to' =>  Module::t('Receiver'),
            'text' => Module::t('Text'),
            'title' => Module::t('Title'),
            'status' => Module::t('Status'),
            'user' => Module::t('User'),
            'workspace' => Module::t('Workspace'),
            'role' => Module::t('Role'),
            'group' => Module::t('Group'),


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
            $items[$user->id_user] = $user->first_name . " " . $user->last_name;
        }
        return $items;
    }

    public static function getUserListNotification()
    {
        $users = User::find()
            ->select(['id_user', 'username'])
            ->asArray()
            ->all();
        if (!$users) {
            return null;
        }
        return $users;
    }

    public static function getWorkspaceList()
    {
        $workspaces = Workspace::find()
            ->select(['id_workspace', 'name'])
            ->asArray()
            ->all();
        if (!$workspaces) {
            return null;
        }
        return $workspaces;
    }
    public static function getRolesList()
    {
        //$assigment = new Assignment();
        //$manager = Yii::$app->authManager;
        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();
        return $roles;
    }
    public static function getGroupList()
    {
        $groups = Group::find()
            ->select(['id_group', 'name'])
            ->asArray()
            ->all();
        if (!$groups) {
            return null;
        }
        return $groups;
    }
    public static function getRelatedNotifications()
    {
        return self::find()->where(['id_to'  => Yii::$app->user->id])->all();
    }

    public static function getAllNotifications()
    {
        return self::find()->all();
    }

    public static function getNotificationType()
    {
        return [
           
            self::NOTIFICATION_TYPE_USER => 'User',
            self::NOTIFICATION_TYPE_ROLE => 'Role',
            self::NOTIFICATION_TYPE_GROUP => 'Group',
        ];
    }

    public static function getUnreadNotifications()
    {
        //var_dump(self::find()->where([ 'id_to'  => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])->createCommand()->getRawSql());
        return self::find()->where(['id_to'  => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])->all();
    }

    public static function getReadNotifications()
    {
        return self::find()->where(['id_to'  => Yii::$app->user->id, 'status' => self::STATUS_READ])->all();
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

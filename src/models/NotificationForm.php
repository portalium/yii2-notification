<?php

namespace portalium\notification\models;

use portalium\notification\models\Notification as NotificationModel;
use portalium\notification\Module;
use portalium\user\models\User;
use portalium\user\models\UserGroup;
use Yii;
use yii\web\NotFoundHttpException;

use yii\base\Model;

class NotificationForm extends Model
{

    public $title;
    public $text;
    public $users = [];
    public $notificationType;
    public $receiver_id;
    public $id_notification;

    public function rules()
    {
        return [
            [['notificationType', 'receiver_id', 'text', 'title'], 'required'],
            [['notificationType',  'status'], 'integer'],
            [['text', 'title'], 'string'],
            [['receiver_id'], 'each', 'rule' => ['string']],
        ];
    }
    public function attributeLabels()
    {
        return [
            'notificationType' => Module::t('Notification Type'),
            'id_notification' => Module::t('Id Notification'),
            'receiver_id' =>  Module::t('Receiver'),
            'text' => Module::t('Text'),
            'title' => Module::t('Title'),
            'status' => Module::t('Status'),
            'user' => Module::t('User'),
            'workspace' => Module::t('Workspace'),
            'role' => Module::t('Role'),
            'group' => Module::t('Group'),
        ];
    }
    public static function getNotificationType()
    {
        return [
            '1' => 'User',
            '2' => 'Role',
            '3' => 'Group',
        ];
    }
    public function save()
    {
        $this->getUserList();
        foreach ($this->users as $user) {
            $model = new NotificationModel();
            $model->id_to = (int)$user['id_user'];
            $model->text = $this->text;
            $model->title = $this->title;
            $model->save();
        }
    }

    public function getUserList()
    {
        if ($this->notificationType == 1) {
            $this->users = array_map(function ($id) {
                return ['id_user' => $id];
            }, $this->receiver_id);
            return $this->users;
        } elseif ($this->notificationType == 2) {
            $this->users = (new \yii\db\Query())
                ->select(' a.user_id')
                ->distinct()
                ->from('auth_assignment a')
                ->where(['a.item_name' => $this->receiver_id])
                ->all();
            $this->users = array_map(function ($user) {
                return ['id_user' => $user['user_id']];
            }, $this->users);

            return $this->users;
        } elseif ($this->notificationType == 3) {
            $this->users = (new \yii\db\Query())
                ->select(' u.id_user')
                ->distinct()
                ->from('user_user_group u')
                ->where(['u.id_group' => $this->receiver_id])
                ->all();
            return $this->users;
        }
    }
}

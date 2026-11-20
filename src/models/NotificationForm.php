<?php

namespace portalium\notification\models;

use portalium\notification\models\Notification as NotificationModel;
use portalium\notification\Module;
use portalium\user\models\User;
use portalium\user\models\UserGroup;
use Yii;
use yii\base\Model;
use yii\db\Query;

/**
 * Toplu bildirim gönderimi: alıcılar kullanıcı, rol, grup ya da tüm aktif kullanıcılar olarak seçilir.
 * Yalnızca aktif (STATUS_ACTIVE) kullanıcılara bildirim yazılır.
 */
class NotificationForm extends Model
{
    public $title;
    public $text;
    public $users = [];
    public $notificationType;
    public $receiver_id = [];
    public $id_notification;
    public $send_email;
    public $send_push;

    const NOTIFICATİON_TYPE = [
        'user' => '1',
        'role' => '2',
        'group' => '3',
        'all' => '4',
    ];

    public function rules()
    {
        return [
            [['notificationType'], 'filter', 'filter' => function ($value) {
                return $this->typeCode($value) ?? $value;
            }],
            [['receiver_id'], 'filter', 'filter' => function ($value) {
                if ($value === null || $value === '') {
                    return [];
                }
                return array_map(function ($item) {
                    return is_scalar($item) ? (string) $item : $item;
                }, is_array($value) ? array_values($value) : [$value]);
            }],
            [['notificationType', 'text', 'title'], 'required'],
            [['notificationType'], 'in', 'range' => array_values(self::NOTIFICATİON_TYPE)],
            [['receiver_id'], 'required', 'when' => function ($model) {
                return !$model->isAll();
            }],
            [['receiver_id'], 'each', 'rule' => ['string']],
            [['text', 'title'], 'string', 'max' => 255],
            [['send_email', 'send_push'], 'boolean'],
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
            self::NOTIFICATİON_TYPE['user'] => 'User',
            self::NOTIFICATİON_TYPE['role'] => 'Role',
            self::NOTIFICATİON_TYPE['group'] => 'Group',
            self::NOTIFICATİON_TYPE['all'] => 'All Users',
        ];
    }

    public function isAll()
    {
        return $this->typeCode($this->notificationType) === self::NOTIFICATİON_TYPE['all'];
    }

    /**
     * Tip adını (user, role, group, all) ya da kodunu ('1'-'4') koda çevirir; tanınmazsa null.
     */
    private function typeCode($value)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = (string) $value;
        if (isset(self::NOTIFICATİON_TYPE[$value])) {
            return self::NOTIFICATİON_TYPE[$value];
        }
        return in_array($value, self::NOTIFICATİON_TYPE, true) ? $value : null;
    }

    /**
     * Doğrulanmış formdaki alıcılara bildirim yazar; istenirse e-posta ve push da gönderir.
     *
     * @return array|false gönderim özeti; alıcı bulunamazsa false (hata receiver_id üzerindedir)
     */
    public function send()
    {
        $ids = $this->getUserList();
        if (empty($ids)) {
            $this->addError($this->isAll() ? 'notificationType' : 'receiver_id', Module::t('No active users found for the selected receivers.'));
            return false;
        }

        $rows = [];
        foreach ($ids as $id) {
            $rows[] = [$id, $this->title, $this->text, NotificationModel::STATUS_UNREAD];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach (array_chunk($rows, 500) as $chunk) {
                Yii::$app->db->createCommand()
                    ->batchInsert(NotificationModel::tableName(), ['id_to', 'title', 'text', 'status'], $chunk)
                    ->execute();
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $result = ['count' => count($ids)];
        if ($this->send_email) {
            $result['email'] = $this->sendEmails($ids);
        }
        if ($this->send_push) {
            $push = Yii::$app->notification->sendPushToMultipleUsers($ids, $this->title, $this->text);
            $result['push'] = $push['summary'] ?? ['error' => $push['error'] ?? null];
        }
        return $result;
    }

    /**
     * Alıcı kimliklerini döndürür (yalnızca aktif kullanıcılar).
     *
     * @return int[]
     */
    public function getUserList()
    {
        $type = $this->typeCode($this->notificationType);
        $receivers = is_array($this->receiver_id) ? $this->receiver_id : [$this->receiver_id];
        $query = User::find()->select('id_user')->where(['status' => User::STATUS_ACTIVE]);

        if ($type === self::NOTIFICATİON_TYPE['user']) {
            $query->andWhere(['id_user' => $receivers]);
        } elseif ($type === self::NOTIFICATİON_TYPE['role']) {
            $query->andWhere(['id_user' => (new Query())
                ->select('user_id')
                ->from(Yii::$app->authManager->assignmentTable)
                ->where(['item_name' => $receivers])]);
        } elseif ($type === self::NOTIFICATİON_TYPE['group']) {
            $query->andWhere(['id_user' => (new Query())
                ->select('id_user')
                ->from(UserGroup::tableName())
                ->where(['id_group' => $receivers])]);
        } elseif ($type !== self::NOTIFICATİON_TYPE['all']) {
            return $this->users = [];
        }

        return $this->users = array_map('intval', $query->column());
    }

    private function sendEmails(array $ids)
    {
        $sent = 0;
        $failed = 0;
        foreach (User::find()->where(['id_user' => $ids])->each() as $user) {
            try {
                Yii::$app->notification->sendEmail($user, $this->text, $this->title) ? $sent++ : $failed++;
            } catch (\Throwable $e) {
                Yii::warning('Notification email failed for user ' . $user->id_user . ': ' . $e->getMessage(), __METHOD__);
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }
}

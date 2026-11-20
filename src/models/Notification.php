<?php

namespace portalium\notification\models;

use portalium\user\models\User;
use Yii;
use portalium\notification\Module;
use yii\web\NotFoundHttpException;

/**
 * @property string|null $date_create UTC; null for notifications written before the column existed
 * @property string|null $url a path of this site the notification leads to (opening it goes there)
 */
class Notification extends \yii\db\ActiveRecord
{
    const STATUS_UNREAD = 0;
    const STATUS_READ = 1;

    public static function tableName()
    {
        return '{{' . Module::$tablePrefix . 'notification}}';
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['date_create'],
                ],
                // UTC from PHP: the database clock and the site's time zone may differ
                'value' => function () {
                    return gmdate('Y-m-d H:i:s');
                },
            ],
        ];
    }

    public function rules()
    {
        return [
            [['id_to', 'text', 'title'], 'required'],
            [['id_to', 'status'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_UNREAD, self::STATUS_READ]],
            [['text', 'title'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 512],
            [['id_to'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_to' => 'id_user']],
        ];
    }
    public function attributeLabels()
    {
        return [
            'id_notification' => Module::t('Id Notification'),
            'id_to' => Module::t('Receiver'),
            'text' => Module::t('Text'),
            'title' => Module::t('Title'),
            'status' => Module::t('Status'),
            'date_create' => Module::t('Date'),
        ];
    }

    /**
     * A path of this site ("/task/default/detail?id=5"), never another host ("//x", "http://x").
     */
    public static function isLocalUrl($url): bool
    {
        return is_string($url) && strlen($url) > 1 && $url[0] === '/' && $url[1] !== '/' && $url[1] !== '\\';
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

    public static function getRelatedNotifications()
    {
        return self::find()->where(['id_to' => Yii::$app->user->id])->all();
    }

    public static function getAllNotifications()
    {
        return self::find()->all();
    }

    public static function getUnreadNotifications()
    {
        return self::find()->where(['id_to' => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])->all();
    }

    /**
     * The current user's latest notifications, read and unread, newest first. Unread ones come first in the limit:
     * the badge counts all of them, so newer read ones must not push them out of the list.
     */
    public static function getRecentNotifications(int $limit = 30)
    {
        $unread = self::find()
            ->where(['id_to' => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])
            ->orderBy(['id_notification' => SORT_DESC])
            ->limit($limit)
            ->all();
        $read = [];
        if (count($unread) < $limit) {
            $read = self::find()
                ->where(['id_to' => Yii::$app->user->id])
                ->andWhere(['<>', 'status', self::STATUS_UNREAD])
                ->orderBy(['id_notification' => SORT_DESC])
                ->limit($limit - count($unread))
                ->all();
        }
        $list = array_merge($unread, $read);
        usort($list, function ($a, $b) {
            return (int) $b->id_notification <=> (int) $a->id_notification;
        });

        return $list;
    }

    /**
     * How many notifications the current user has not read yet.
     */
    public static function countUnread(): int
    {
        return (int) self::find()->where(['id_to' => Yii::$app->user->id, 'status' => self::STATUS_UNREAD])->count();
    }

    /**
     * Marks every unread notification of the user as read, the ones beyond the panel's list included.
     * Returns how many changed.
     */
    public static function markAllRead(int $userId): int
    {
        return self::updateAll(['status' => self::STATUS_READ], ['id_to' => $userId, 'status' => self::STATUS_UNREAD]);
    }

    public static function getReadNotifications()
    {
        return self::find()->where(['id_to' => Yii::$app->user->id, 'status' => self::STATUS_READ])->all();
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

<?php

namespace portalium\notification\models;

use Yii;
use yii\db\ActiveRecord;
use portalium\notification\Module;
use portalium\user\models\User;
use portalium\workspace\models\Workspace;
use yii\behaviors\TimestampBehavior;

/**
 * NotificationDevice model
 * 
 * @property int $id_notification_device
 * @property int $id_user
 * @property int $id_workspace
 * @property string $device_token FCM Device Token
 * @property string $device_type android, ios, web
 * @property string|null $device_name Cihaz adı (isteğe bağlı)
 * @property string|null $device_model Cihaz modeli (isteğe bağlı)
 * @property string|null $app_version Uygulama versiyonu
 * @property string|null $os_version İşletim sistemi versiyonu
 * @property int $is_active Cihaz aktif mi?
 * @property string|null $last_used_at Son kullanım tarihi
 * @property string $date_create
 * @property string $date_update
 *
 * @property User $user
 * @property Workspace $workspace
 */
class NotificationDevice extends ActiveRecord
{
    const DEVICE_TYPE_ANDROID = 'android';
    const DEVICE_TYPE_IOS = 'ios';
    const DEVICE_TYPE_WEB = 'web';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%' . Module::$tablePrefix . 'notification_device}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['device_token', 'device_type', 'id_user', 'id_workspace'], 'required'],
            [['id_user', 'id_workspace', 'is_active'], 'integer'],
            [['device_token'], 'string', 'max' => 1024],
            [['device_type'], 'string', 'max' => 50],
            [['device_name', 'device_model'], 'string', 'max' => 255],
            [['app_version', 'os_version'], 'string', 'max' => 50],
            [['last_used_at', 'date_create', 'date_update', 'id_user', 'id_workspace'], 'safe'],
            [['device_type'], 'in', 'range' => [self::DEVICE_TYPE_ANDROID, self::DEVICE_TYPE_IOS, self::DEVICE_TYPE_WEB]],
            [['is_active'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['device_token', 'id_user', 'id_workspace'], 'unique', 'targetAttribute' => ['device_token', 'id_user', 'id_workspace']],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id_user']],
            [['id_workspace'], 'exist', 'skipOnError' => true, 'targetClass' => Workspace::class, 'targetAttribute' => ['id_workspace' => 'id_workspace']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'date_create',
                'updatedAtAttribute' => 'date_update',
                'value' => date("Y-m-d H:i:s"),
            ],
            [
                'class' => 'yii\behaviors\BlameableBehavior',
                'createdByAttribute' => 'id_user',
                'updatedByAttribute' => 'id_user',
                'value' => Yii::$app->user->id,
            ],
            [
                'class' => 'yii\behaviors\BlameableBehavior',
                'createdByAttribute' => 'id_workspace',
                'updatedByAttribute' => 'id_workspace',
                'value' => Yii::$app->workspace->id,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_notification_device' => Module::t('ID'),
            'id_user' => Module::t('User'),
            'id_workspace' => Module::t('Workspace'),
            'device_token' => Module::t('Device Token'),
            'device_type' => Module::t('Device Type'),
            'device_name' => Module::t('Device Name'),
            'device_model' => Module::t('Device Model'),
            'app_version' => Module::t('App Version'),
            'os_version' => Module::t('OS Version'),
            'is_active' => Module::t('Is Active'),
            'last_used_at' => Module::t('Last Used At'),
            'date_create' => Module::t('Created At'),
            'date_update' => Module::t('Updated At'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id_user' => 'id_user']);
    }

    /**
     * Gets query for [[Workspace]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkspace()
    {
        return $this->hasOne(Workspace::class, ['id_workspace' => 'id_workspace']);
    }

    /**
     * Device tiplerinin listesi
     */
    public static function getDeviceTypes()
    {
        return [
            self::DEVICE_TYPE_ANDROID => 'Android',
            self::DEVICE_TYPE_IOS => 'iOS',
            self::DEVICE_TYPE_WEB => 'Web',
        ];
    }

    /**
     * Kullanıcının aktif cihazlarını getirir
     */
    public static function getActiveDevicesByUser($userId, $workspaceId = null)
    {
        $query = self::find()
            ->where(['id_user' => $userId, 'is_active' => self::STATUS_ACTIVE]);

        if ($workspaceId !== null) {
            $query->andWhere(['id_workspace' => $workspaceId]);
        }

        return $query->all();
    }

    /**
     * Kullanıcının aktif device token'larını getirir
     */
    public static function getActiveTokensByUser($userId, $workspaceId = null, $deviceType = null)
    {
        $query = self::find()
            ->select('device_token')
            ->where(['id_user' => $userId, 'is_active' => self::STATUS_ACTIVE]);

        if ($workspaceId !== null) {
            $query->andWhere(['id_workspace' => $workspaceId]);
        }

        if ($deviceType !== null) {
            $query->andWhere(['device_type' => $deviceType]);
        }

        return $query->column();
    }

    /**
     * Device'ı deaktive et
     */
    public static function deactivateDevice($deviceToken, $userId = null, $workspaceId = null)
    {
        $userId = $userId ?? Yii::$app->user->id;
        $workspaceId = $workspaceId ?? Yii::$app->workspace->id;

        return self::updateAll(
            ['is_active' => self::STATUS_INACTIVE],
            [
                'device_token' => $deviceToken,
                'id_user' => $userId,
                'id_workspace' => $workspaceId
            ]
        );
    }

    /**
     * Kullanıcının tüm cihazlarını deaktive et
     */
    public static function deactivateAllUserDevices($userId, $workspaceId = null)
    {
        $condition = ['id_user' => $userId, 'is_active' => self::STATUS_ACTIVE];

        if ($workspaceId !== null) {
            $condition['id_workspace'] = $workspaceId;
        }

        return self::updateAll(['is_active' => self::STATUS_INACTIVE], $condition);
    }

    /**
     * Eski/kullanılmayan cihazları temizle
     */
    public static function cleanupOldDevices($days = 30)
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return self::updateAll(
            ['is_active' => self::STATUS_INACTIVE],
            [
                'and',
                ['<', 'last_used_at', $threshold],
                ['is_active' => self::STATUS_ACTIVE]
            ]
        );
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if ($this->isNewRecord) {
                $this->id_user = Yii::$app->user->id;
                $this->id_workspace = Yii::$app->workspace->id;
            }
            return true;
        }
        return false;
    }
}

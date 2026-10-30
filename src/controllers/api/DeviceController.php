<?php

namespace portalium\notification\controllers\api;

use portalium\developer\attributes as Doc;
use portalium\notification\Module;
use portalium\notification\models\NotificationDevice;
use portalium\rest\ActiveController as RestActiveController;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * Kullanıcının kendi bildirim cihazları (FCM token). Başkasının cihazı listelenmez ve
 * okunamaz/değiştirilemez; token'lar başka kullanıcıya bildirim göndermeye yarar.
 * Cihaz her zaman çağırana ve onun aktif çalışma alanına kaydedilir; yoksa biri
 * başkasının push bildirimlerini alabilirdi.
 */
#[Doc\Tag('Notification devices', 'Devices (FCM tokens) of the signed-in user that receive push notifications.')]
#[Doc\Operation(action: ['index', 'view', 'create', 'update', 'delete'], audience: Doc\Operation::PUBLIC)]
#[Doc\Operation(action: 'index', summary: 'List my devices', description: 'Only the devices of the signed-in user.')]
#[Doc\Operation(action: 'view', summary: 'View a device', description: 'Another user\'s device returns 403.')]
#[Doc\Operation(
    action: 'create',
    summary: 'Register a device',
    description: 'The device is registered to the caller and the caller\'s active workspace; `id_user` and `id_workspace` in the body are ignored.',
)]
#[Doc\Body(NotificationDevice::class, action: 'create', exclude: ['id_user', 'id_workspace', 'date_create', 'date_update'])]
#[Doc\Operation(
    action: 'update',
    summary: 'Update a device',
    description: 'For example set `is_active: 0` on sign-out. `id_user` and `id_workspace` cannot be changed.',
)]
#[Doc\Body(NotificationDevice::class, action: 'update', exclude: ['id_user', 'id_workspace', 'date_create', 'date_update'], partial: true)]
#[Doc\Operation(
    action: 'delete',
    summary: 'Remove a device',
    description: 'The device stops receiving push notifications. Another user\'s device returns 403.',
)]
class DeviceController extends RestActiveController
{
    use \portalium\traits\RecordAccessTrait;

    public $modelClass = 'portalium\notification\models\NotificationDevice';

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['dataFilter'] = [
            'class' => \yii\data\ActiveDataFilter::class,
            'searchModel' => $this->modelClass,
        ];
        $actions['index']['prepareSearchQuery'] = function ($query) {
            return $query->andWhere(['id_user' => Yii::$app->user->id]);
        };
        return $actions;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if ($action->id === 'create') {
            $this->forceBodyParams(['id_user' => Yii::$app->user->id, 'id_workspace' => Yii::$app->workspace->id]);
        } elseif ($action->id === 'update') {
            $this->stripBodyParams(['id_user', 'id_workspace']);
        }
        return true;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if ($model !== null && (int) $model->id_user !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
    }
}

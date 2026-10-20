<?php

namespace portalium\notification\controllers\api;

use portalium\notification\Module;
use portalium\rest\ActiveController as RestActiveController;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * The user's own notification devices (FCM token). Other users' devices are
 * neither listed nor readable/writable; tokens are used to push notifications
 * to that specific user.
 */
class DeviceController extends RestActiveController
{
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

    public function checkAccess($action, $model = null, $params = [])
    {
        if ($model !== null && (int) $model->id_user !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
    }
}

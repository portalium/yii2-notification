<?php

namespace portalium\notification\controllers\api;

use Yii;
use portalium\rest\ActiveController as RestActiveController;

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
        return $actions;
    }
}

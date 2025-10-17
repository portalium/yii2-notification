<?php

namespace portalium\notification\controllers\api;

use portalium\notification\models\NotificationDevice;
use portalium\notification\models\NotificationSearch;
use portalium\notification\Module;
use Yii;
use portalium\rest\ActiveController as RestActiveController;

class DefaultController extends RestActiveController
{
    public $modelClass = 'portalium\notification\models\Notification';

    public function actions()
    {
        
        $actions = parent::actions();
        $actions['index']['dataFilter'] = [
            'class' => \yii\data\ActiveDataFilter::class,
            'searchModel' => NotificationSearch::class
        ];
        $actions['index']['prepareDataProvider'] = function ($action) {
            $searchModel = new NotificationSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            $dataProvider->query->andWhere(['notification_notification.id_to' => Yii::$app->user->id]);

            return $dataProvider;
        };
        return $actions;
    }


}

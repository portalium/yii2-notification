<?php

namespace portalium\notification\controllers\web;

use portalium\notification\models\Notification;
use portalium\notification\models\NotificationSearch;
use portalium\web\Controller;
use portalium\notification\Module;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }
    public function actionIndex()
    {
        if (!\Yii::$app->user->can('notificationWebDefaultIndex') && !\Yii::$app->user->can('notificationWebDefaultIndexOwn')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $searchModel = new NotificationSearch;
        $dataProvider = $searchModel->search($this->request->queryParams);
        if(!\Yii::$app->user->can('notificationWebDefaultIndex'))
            $dataProvider->query->andWhere(['id_to'=>\Yii::$app->user->id]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id_notification)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultView', ['model'=>$this->findModel($id_notification)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $notification= Notification::find()->where([ 'id_to'  => Yii::$app->user->id,'id_notification'=>$id_notification ])->one();

     return $this->render('view', [
                'model'=>$notification,
            ]);
    }

    public function actionCreate()
    {
        if (!\Yii::$app->user->can('notificationWebDefaultCreate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new Notification();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id_notification' => $model->id_notification]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id_notification)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultUpdate' , ['model'=>$this->findModel($id_notification)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = $this->findModel($id_notification);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', Module::t('Notification has been updated'));
            return $this->redirect(['view', 'id_notification' => $model->id_notification]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id_notification)
    {
        if(!\Yii::$app->user->can('notificationWebDefaultDelete' , ['model'=>$this->findModel($id_notification)]))
        {
            throw new \yii\web\ForbiddenHttpException( Module::t('You are not allowed to access this page.'));
        }

        if($this->findModel($id_notification)->delete()){
            Yii::$app->session->addFlash('info', Module::t('Notification has been deleted'));
        }
        return $this->redirect(['index']);

    }

    protected function findModel($id_notification)
    {
        if (($model = Notification::findOne(['id_notification' => $id_notification])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested page does not exist.'));
    }
}

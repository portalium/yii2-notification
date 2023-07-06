<?php

use portalium\notification\models\Notification;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var portalium\notification\models\NotificationSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notification-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Notification'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id_notification',
            'type',
            'id_to',
            'text',
            'title',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Notification $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id_notification' => $model->id_notification]);
                 }
            ],
        ],
    ]); ?>
</div>

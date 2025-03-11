<?php

use portalium\notification\models\Notification;
use portalium\notification\Module;
use portalium\theme\widgets\ActionColumn;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\GridView;
use portalium\theme\widgets\Panel;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var portalium\notification\models\NotificationSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Module::t('Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
/*
echo $this->render('@vendor/portalium/yii2-notification/src/widgets/views/notification', [
    'notifications' => $notifications
]); */
?>

<?php


Panel::begin([
    'title' => Module::t('Notification'),
    'actions' => [
        'header' => [
            Html::a('', ['create'], ['class' => 'fa fa-plus btn btn-success', 'title' => Module::t('Create')]),
        ]
    ]
]) ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'portalium\grid\SerialColumn'],

        'user.username',
        [
            'attribute' => 'text',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->text;
            }
        ],
        'title',
        [
            'attribute' => 'status',
            'value' => function ($model) {
                return Notification::getStatusList()[$model->status];
            },
            'filter' => Notification::getStatusList()

        ],
        [
            'class' => ActionColumn::class, 'header' => Module::t('Actions'),
            'template' => '{view} {update} {assignment} {delete}',
            'buttons' => [

                'urlCreator' => function ($action, Notification $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id_notification' => $model->id_notification]);
                }
            ]
        ],
    ],
]); ?>
<?php Panel::end();
?>
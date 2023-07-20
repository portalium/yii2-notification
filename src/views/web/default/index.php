<?php

use portalium\notification\models\Notification;
use portalium\notification\Module;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Panel;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var portalium\notification\models\NotificationSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Module::t('Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>
<?php

$form = ActiveForm::begin();
Panel::begin([
    'title' => Module::t('Notification'),
    'actions' => [
        'header' => [
            Html::submitButton(Module::t(''), [
                'class' => 'fa fa-trash btn btn-danger', 'id' => 'delete-select',
                'data' => [
                    'confirm' => Module::t('If you continue, all your data will be reset. Do you want to continue?'),
                    'method' => 'post'

                ]
            ]),
            Html::a(Module::t(''), ['create'], ['class' => 'fa fa-plus btn btn-success']),
        ]
    ]
]) ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        'type',
        'user.username',
        'text',
        'title',
        [
            'class' => ActionColumn::class,
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
ActiveForm::end();
?>
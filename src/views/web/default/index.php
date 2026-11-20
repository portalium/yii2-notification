<?php

use portalium\notification\bundles\NotificationAsset;
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

NotificationAsset::register($this);

$this->title = Module::t('Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>
<?php Panel::begin([
    'title' => Module::t('Notification'),
    'actions' => [
        'header' => [
            Html::button('', [
                'class' => 'fa fa-trash btn btn-danger',
                'id' => 'notification-delete-select',
                'title' => Module::t('Delete selected'),
                'aria-label' => Module::t('Delete selected'),
                'data' => [
                    'url' => Url::to(['delete-selected']),
                    'confirm-text' => Module::t('Delete the selected notifications?'),
                ],
            ]),
            Html::a(Module::t(''), ['create'], ['class' => 'fa fa-plus btn btn-success']),
        ],
    ],
]) ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'portalium\grid\CheckboxColumn'],
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
            'class' => ActionColumn::class,
            'template' => '{view} {update} {delete}',
            'buttons' => [
                'urlCreator' => function ($action, Notification $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id_notification' => $model->id_notification]);
                },
            ],
        ],
    ],
]); ?>
<?php Panel::end();
?>

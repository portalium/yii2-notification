<?php

use portalium\theme\helpers\Html;
use yii\widgets\DetailView;
use portalium\theme\widgets\Panel;
use portalium\notification\Module;



/** @var yii\web\View $this */
/** @var portalium\notification\models\Notification $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Notifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<?php Panel::begin([
    'title' => $model->title,
    'actions' => [
        'header' => [
            Html::a(Module::t(''), ['update', 'id_notification' => $model->id_notification], ['class' => 'fa fa-pencil btn btn-primary']),
            Html::a(Module::t(''), ['delete', 'id_notification' => $model->id_notification], [
                'class' => 'fa fa-trash btn btn-danger',
                'data' => [
                    'confirm' => Module::t( 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]),
        ]
    ]
]) ?>


<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'user.username',
        /* 'text', */
        [
            'attribute' => 'text',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->text;
            }
        ],
        'title',
    ],
]) ?>
<?php Panel::end() ?>
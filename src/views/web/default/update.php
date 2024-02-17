<?php

use yii\helpers\Html;
use portalium\notification\Module;

/** @var yii\web\View $this */
/** @var portalium\notification\models\Notification $model */

$this->title = Module::t('Update Notification: {name}', ['name' => $model->title,]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Notifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id_notification' => $model->id_notification]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<?= $this->render('_form', [
    'model' => $model,
]) ?>

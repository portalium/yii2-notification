<?php

use portalium\site\helpers\Route;
use yii\helpers\Html;
use Yii;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\User $user */

?>
<div class="verify-email">
    <p>Hello <?= Html::encode($user->username) ?>,</p>
    <p>you have a new notification:</p>

    <h3><?= Html::encode($title) ?></h3>
    <p><?= Html::encode($text) ?></p>
</div>
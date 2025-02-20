<?php

use yii\helpers\Url;

use portalium\site\helpers\Route;

/** @var yii\web\View $this */
/** @var common\models\User $user */


?>
    Hello <?= $user->username ?>,

    you have a new notification:
       
<?= $title ?>
<?= $text ?>
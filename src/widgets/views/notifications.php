<?php
use portalium\notification\bundles\NotificationAsset;
use portalium\notification\Module;
use yii\helpers\Html;
$this->title = Module::t('Notification');
NotificationAsset::register($this);
?>

<?php
if (count($notifications) > 0)
{ ?>


    <ul class="card_box" id="notification">
        <li class="dropdown nav-item">
            <a href="#" class="dropdown-toggle" style="background-color: #212529" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-bell"><span class="position-absolute top-0 translate-middle badge rounded-pill bg-danger"><?=  count($notifications) ?>
           <span class="visually-hidden">unread messages</span></span></i>
            </a>

            <ul class="dropdown-menu notify-drop">
                <div class="notification-heading">
                    <h4 class="menu-title">Notifications</h4>
                </div>
                <hr class="dropdown-divider">

                <div>
                    <div class="notifications-wrapper">
                        <div class="card" role="presentation">
                            <?php foreach ($notifications as $notification) { ?>
                                <a class="card" role="presentation" href="/notification/default/view?id_notification=<?= $notification->id_notification?>">
                                    <h4 class="item-title"><?php echo $notification -> title ?></h4>
                                    <p class="item-info"><?php if (strlen($notification->text) > 19) { echo substr($notification -> text, 0, 22).'...';}
                                        else echo($notification->text) ?></p>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <hr class="dropdown-divider">
                <div class="notification-footer">
                    <a class="a" href="/notification/default/index?"><h4 class="menu-title-footer">View all</h4></a>
                </div>
            </ul>

        </li>
    </ul>

<?php

}
else
{
    echo Html::tag('i', '', ['class' => 'fa fa-bell-slash', 'style' => 'margin-right: 22px; color: white; margin-top: 10px;']);
}
?>
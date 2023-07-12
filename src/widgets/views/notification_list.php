<?php
$this->title = Module::t('Notification');
NotificationAsset::register($this);
?>

<div class="dropdown">
    <a id="dLabel" role="button" data-toggle="dropdown" data-target="#">
        <i class="glyphicon glyphicon-bell"></i>
    </a>

    <ul class="dropdown-menu notifications" role="menu" aria-labelledby="dLabel">

        <div class="notification-heading">
            <h4 class="menu-title">Notifications</h4>
            <h4 class="menu-title pull-right">View all<i class="glyphicon glyphicon-circle-arrow-right"></i></h4>
        </div>
        <li class="divider"></li>
        <div class="notifications-wrapper">
            <?php for ($i = 0; $i < 6; $i++) { ?>
                <a class="content" href="#">
                    <div class="notification-item">
                        <h4 class="item-title">Evaluation Deadline 1 · day ago</h4>
                        <p class="item-info">Marketing 101, Video Assignment</p>
                    </div>
                </a>
            <?php } ?>
        </div>
        <li class="divider"></li>
        <div class="notification-footer">
            <h4 class="menu-title">View all<i class="glyphicon glyphicon-circle-arrow-right"></i></h4>
        </div>
    </ul>
</div>
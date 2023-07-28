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
    <?php
     echo Html::beginTag('ul', ['class' => 'card_box', 'id' => 'notification']);
        echo Html::beginTag('li',['class' => 'dropdown nav-item']);

                 //icon
            echo Html::beginTag('a', ['class' => 'dropdown-toggle','style' => 'background-color:#212529', 'data-bs-toggle'=>'dropdown', 'role'=>'button', 'aria-haspopup'=>'true', 'aria-expanded'=>'false', 'href'=>'#'])
                    .'<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    '.count($notifications).'
                    <span class="visually-hidden">unread messages</span>
                    </span>'.
                 Html::endTag("a");

                echo Html::beginTag('ul', $options);
                    //header
                    echo Html::beginTag('div',['class'=>'notification-heading']);
                        echo Html::beginTag('h4',['class'=>'menu-title']).'Notifications'.
                             Html::endTag('h4');
                    echo Html::endTag('div');

                    //divider
                    echo Html::tag('hr',['class'=>'dropdown-divider']);

                    //body of dropdown list
                    echo Html::beginTag('div');
                        echo Html::beginTag('div',['class'=>'notification-wrapper']);
                            echo Html::beginTag('div',['class'=>'card', 'role'=>'presentation']);
?>
                                <?php foreach ($notifications as $notification) { ?>
                                    <a class="card" role="presentation" href="/notification/default/view?id_notification=<?= $notification->id_notification?>">
                                    <h4 class="item-title"><?php echo $notification -> title ?></h4>
                                    <p class="item-info"><?php if (strlen($notification->text) > 19) { echo substr($notification -> text, 0, 22).'...';}
                                    else echo($notification->text) ?></p>
                                    </a>
                                <?php } ?>
<?php
                            echo Html::endTag('div');
                        echo Html::endTag('div');
                    echo Html::endTag('div');

                    //divider
                    echo Html::tag('hr',['class'=>'dropdown-divider']);

                    //footer
                    echo Html::beginTag('div',['notification-footer']);
                        echo Html::beginTag('a',['class'=>'a','href'=>'/notification/default/index?']);
                            echo Html::beginTag('h4',['class'=>'menu-title-footer']).'View all'.
                                 Html::endTag('h4');
                        echo Html::endTag('a');
                    echo Html::endTag('div');

            echo Html::endTag('ul');
        echo Html::endTag('li');
    echo Html::endTag('ul');
?>
<?php

}
else
{
    echo Html::tag('i', '', ['class' => 'fa fa-bell-slash', 'style' => 'margin-right: 22px; color: white; margin-top: 10px;']);
}
?>
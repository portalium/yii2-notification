# <p align="center">Portalium-Notification</p>
This module created for sending and receiving notifications to specific users based on permissions
that is given to related user.

## Requirements
  PHP 7.3.31 or higher

## How to use or develop

 You should add the following blocks of code into your composer.json file of your portalium module.
 
 to repositories part of your composer.json
<br>
 `{
 "type": "git",
 "url": "https://github.com/portalium/yii2-notification.git"
 }`
<br>
<br>
to require part of your composer.json
<br>
 `"portalium/yii2-notification": "dev-develop",`

and in web\config\main.php path you should add the following block of code in module part
<br>
`{
"type": "git",
"url": "https://github.com/portalium/yii2-notification.git"
}`
<br><br>
and finally, you should run `composer update` command in the terminal of your php container in Docker.


## How does the Notification Module work?
Both creating and viewing the notification is done base on the permissions that is given to users.<br>
In notification model there is id_to attribute which specifies to whom the notification is going to be sent.
If a user have any new notification, the number of notifications is displayed beside the bell icon in the menu bar of home page.
<br>

![notification-widget](https://github.com/portalium/yii2-notification/assets/91452487/df3b87cd-a801-410c-ad27-30b4fc236c2c)
<br>

There is a link for every notification which is displayed in the dropdown list of bell icon, and you can go to that link and see the whole content of related notification. 
<br>


Also if you want to see the whole new notifications in one page, you can press the viewAll button that is located at the end of dropdown list which will
redirect you to a page that you can see the whole notifications that sent to you. like the following,
![notification__](https://github.com/portalium/yii2-notification/assets/91452487/6af4c3aa-e0b6-4de9-9e79-00f5e4ccf800)

But if you have permission to see all user's notification, then you will see all user's notification.
<br>
<?php

use portalium\notification\Module;
use yii\db\Migration;

class m261107_000001_notification_url extends Migration
{
    public function safeUp()
    {
        $table = Module::$tablePrefix . 'notification';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('url') !== null) {
            return;
        }
        $this->addColumn($table, 'url', $this->string(512)->null()->defaultValue(null));
    }

    public function safeDown()
    {
        $table = Module::$tablePrefix . 'notification';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema !== null && $schema->getColumn('url') !== null) {
            $this->dropColumn($table, 'url');
        }
    }
}

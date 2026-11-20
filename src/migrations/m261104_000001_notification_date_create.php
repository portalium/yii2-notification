<?php

use portalium\notification\Module;
use yii\db\Migration;

class m261104_000001_notification_date_create extends Migration
{
    public function safeUp()
    {
        $table = Module::$tablePrefix . 'notification';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('date_create') !== null) {
            return;
        }
        $this->addColumn($table, 'date_create', $this->dateTime()->null()->defaultValue(null));
    }

    public function safeDown()
    {
        $table = Module::$tablePrefix . 'notification';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema !== null && $schema->getColumn('date_create') !== null) {
            $this->dropColumn($table, 'date_create');
        }
    }
}

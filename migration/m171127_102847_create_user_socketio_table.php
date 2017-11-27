<?php

use yii\db\Migration;

/**
 * Handles the creation of table `user_socketio`.
 */
class m171127_102847_create_user_socketio_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('user_socketio', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(11)->notNull(),
            'socket_id' => $this->string(255)->notNull(),
        ]);

        $this->createIndex('user_socketio-user_id', 'user_socketio', 'user_id');
        $this->createIndex('user_socketio-socket_id', 'user_socketio', 'socket_id');
        $this->addForeignKey('user_socketio_user_id-user_id_fk', 'user_socketio', 'user_id', 'user', 'id');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('user_socketio');
    }
}

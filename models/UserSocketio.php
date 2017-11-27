<?php

namespace yiicod\socketio\models;

use common\models\User;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "user_socketio".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $socket_id
 *
 * @property User $user
 */
class UserSocketio extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_socketio';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'socket_id'], 'required'],
            [['user_id'], 'integer'],
            [['socket_id'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'socket_id' => Yii::t('app', 'Socket ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public static function truncateTable() {
        Yii::$app->db->createCommand()->truncateTable(self::tableName())->execute();
    }
}

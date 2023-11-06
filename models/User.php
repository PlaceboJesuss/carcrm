<?php

namespace app\models;

use PDO;
use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class User extends ActiveRecord implements IdentityInterface
{
    public $is_self;

    public static function tableName()
    {
        return '{{users}}';
    }

    public function getActiveTasks()
    {
        return $this->hasMany(Task::class, ["user_id" => "id"])->where(["closed_at" => null]);
    }

    public function getLeads()
    {
        return $this->hasMany(Lead::class, ["responsible_id" => "id"]);
    }

    public function getOpenLeads()
    {
        return $this->hasMany(Lead::class, ["responsible_id" => "id"])->where("status_id != 200")->andWhere("status_id != 400");
    }

    public function getEndTasks()
    {
        return $this->hasMany(Task::class, ["user_id" => "id"])->where(["not", ["closed_at" => null]]);
    }

    public function getIsActive()
    {
        $time = time();
        return Vacation::find()->where(["user_id" => $this->id])->andWhere(["<=", "date_from", $time])->andWhere([">=", "date_to", $time])->count() == 0;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();
            }
            return true;
        }
        return false;
    }

    public static function isFreeLogin($login)
    {
        return static::find()->where(["login" => $login])->count() == 0;
    }

    public static function isCorrectPassword($password)
    {
        return strlen($password) >= 8;
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function getId()
    {
        return $this->id;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
}

<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class Vacation extends ActiveRecord
{
    public static function tableName()
    {
        return '{{vacations}}';
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ["id" => "user_id"]);
    }
}

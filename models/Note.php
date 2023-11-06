<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class Note extends ActiveRecord
{
    public static function tableName()
    {
        return '{{notes}}';
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ["id" => "user_id"]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->created_at = time();
        }
        return true;
    }

    public function getClass(){
        return "note";
    }
}

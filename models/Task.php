<?php

namespace app\models;

use yii\db\ActiveRecord;

class Task extends ActiveRecord
{
    public static function tableName()
    {
        return '{{tasks}}';
    }

    public static function getActiveTasks()
    {
        return Task::find()->where(["closed_at" => null])->all();
    }

    public static function getDoneTasks()
    {
        return Task::find()->where(["not", ["closed_at" => null]])->all();
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ["id"=>"user_id"]);
    }

    public function getDirector(){
        return $this->hasOne(User::class, ["id"=>"director_id"]);
    }

    public function getClass(){
        return "task";
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
}

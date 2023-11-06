<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class Status extends ActiveRecord
{
    public static function tableName()
    {
        return '{{statuses}}';
    }

    public static function getReservedStatuses()
    {
        return self::find()->where(["id" => [200, 400]])->all();
    }

    public function getLeads()
    {
        return $this->hasMany(Lead::class, ["status_id" => "id"]);
    }

    public function getPipeline()
    {
        return $this->hasOne(Pipeline::class, ["id" => "pipeline_id"]);
    }

    public static function haveCopy($pipeline_id, $status_name)
    {
        return static::find()->where(["pipeline_id" => $pipeline_id, "name" => $status_name])->count() > 0;
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        $ids = array_column($this->leads, "id");
        Lead::deleteAll(["id" => $ids]);
        return true;
    }
}

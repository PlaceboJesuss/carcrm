<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class Pipeline extends ActiveRecord
{
    public static function tableName()
    {
        return '{{pipelines}}';
    }

    public function getStatuses()
    {
        return $this->hasMany(Status::class, ["pipeline_id" => "id"])->orderBy(["position"=>SORT_ASC]);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        $ids = array_column($this->statuses, "id");
        Status::deleteAll(["id"=>$ids]);
        return true;
    }
}

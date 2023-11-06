<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class HookField extends ActiveRecord
{
    public static function tableName()
    {
        return '{{hook_fields}}';
    }

    public function getCustomField()
    {
        if (is_numeric($this->field_id)) {
            return $this->hasOne(CustomField::class, ["id" => "field_id"]);
        } else {
            switch ($this->field_id) {
                case "name":
                    return [
                        "type" => ["type" => "text"]
                    ];
                case "discount":
                    return [
                        "type" => ["type" => "money"]
                    ];
            }
        }
    }

    public static function contactField()
    {
        return static::find()->where(["or", "field_id='phone'", "field_id='email'",])->all();
    }



    public function getEntity()
    {
        return $this->hasOne(EntityType::class, ["id" => "entity_type_id"])->one()->name;
    }
}

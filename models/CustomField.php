<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class CustomField extends ActiveRecord
{
    public static function tableName()
    {
        return '{{custom_fields}}';
    }

    public function getType()
    {
        return $this->hasOne(CustomFieldType::class, ["id" => "type_id"]);
    }

    public function getOptions()
    {
        return $this->hasMany(SelectOptions::class, ["custom_field_id" => "id"]);
    }

    public function getValues()
    {
        return $this->hasMany(CustomFieldValue::class, ["custom_field_id" => "id"]);
    }

    public function getEntity()
    {
        return $this->hasOne(EntityType::class, ["id" => "entity_id"]);
    }

    public static function getLeadField()
    {
        $type_lead_id = EntityType::findOne(["name" => "lead"])->id;
        return static::findAll(["entity_id" => $type_lead_id]);
    }

    public static function getContactField()
    {
        $type_contact_id = EntityType::findOne(["name" => "contact"])->id;
        return static::findAll(["entity_id" => $type_contact_id]);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        CustomFieldValue::deleteAll(["id"=>array_column($this->values, "id")]);
        SelectOptions::deleteAll(["id"=>array_column($this->options, "id")]);
        return true;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        // if (!$insert)
        //  && $this->type->type != "select") {
        //     $this->options->delete();
        // }
        return true;
    }
}

<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Contact extends ActiveRecord
{
    public static function tableName()
    {
        return '{{contacts}}';
    }

    public function getNotes()
    {
        return $this->hasMany(
            Note::class,
            [
                "entity_id" => "id",
            ]
        )->where(["entity_type" => 2]);
    }

    public function getCustomFields()
    {
        return $this->hasMany(CustomFieldValue::class, ["entity_id" => "id"])->where(["custom_field_id" => array_column(CustomField::getContactField(), "id")]);
    }

    public function getLeads()
    {
        return $this->hasMany(Lead::class, ["contact_id" => "id"]);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        Note::deleteAll(["id" => array_column($this->notes, "id")]);
        CustomFieldValue::deleteAll(["id" => array_column($this->customFields, "id")]);
        return true;
    }
}

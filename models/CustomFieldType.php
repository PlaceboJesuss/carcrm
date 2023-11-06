<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class CustomFieldType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{custom_field_type}}';
    }

    public function getCustomFields()
    {
        return $this->hasMany(CustomField::class, ["type" => "id"]);
    }
}

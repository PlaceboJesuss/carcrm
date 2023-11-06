<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class SelectOptions extends ActiveRecord
{
    public static function tableName()
    {
        return '{{select_variants}}';
    }

    public function getCustomField()
    {
        return $this->hasOne(CustomField::class, ["id" => "custom_field_id"]);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        $values = $this->customField->values;
        foreach ($values as $value) {
            if ($value->value == $this->id) {
                $value->delete();
            }
        }
        return true;
    }
}

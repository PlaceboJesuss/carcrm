<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class CustomFieldValue extends ActiveRecord
{
    public static function tableName()
    {
        return '{{custom_field_values}}';
    }
}

<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class ApiField extends ActiveRecord
{
    public static function tableName()
    {
        return '{{api_fields}}';
    }
}

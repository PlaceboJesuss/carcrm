<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class BodyType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{body_types}}';
    }
}

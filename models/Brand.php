<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Brand extends ActiveRecord
{
    public static function tableName()
    {
        return '{{brands}}';
    }

}

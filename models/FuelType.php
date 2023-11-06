<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class FuelType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{fuel_types}}';
    }

}

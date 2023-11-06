<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Model extends ActiveRecord
{
    public static function tableName()
    {
        return '{{models}}';
    }

}

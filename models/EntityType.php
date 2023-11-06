<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class EntityType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{entity_types}}';
    }


}

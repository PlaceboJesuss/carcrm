<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Complectation extends ActiveRecord
{
    public static function tableName()
    {
        return '{{complectations}}';
    }

    public function getBrand()
    {
        return $this->hasOne(Brand::class, ["id" => "brand_id"])->one()->name;
    }

    public function setBrand($value)
    {
        $brand = Brand::find()->where(["name" => $value])->one();
        if ($brand === null) {
            $brand = new Brand();
            $brand->name = $value;
            $brand->save();
        }
        $this->brand_id = $brand->id;
        $this->save();
        return $value;
    }

    public function getModel()
    {
        return $this->hasOne(Model::class, ["id" => "model_id"])->one()->name;
    }

    public function setModel($value)
    {
        $model = Model::find()->where(["name" => $value])->one();
        if ($model === null) {
            $model = new Model();
            $model->name = $value;
            $model->save();
        }
        $this->model_id = $model->id;
        $this->save();
        return $value;
    }

    public function getBody()
    {
        return $this->hasOne(BodyType::class, ["id" => "body_type_id"])->one()->name;
    }

    public function setBody($value)
    {
        $body = BodyType::find()->where(["name" => $value])->one();
        if ($body === null) {
            $body = new BodyType();
            $body->name = $value;
            $body->save();
        }
        $this->body_type_id = $body->id;
        $this->save();
        return $value;
    }

    public function getFuel()
    {
        return $this->hasOne(FuelType::class, ["id" => "fuel_type_id"])->one()->name;
    }

    public function setFuel($value)
    {
        $fuel = FuelType::find()->where(["name" => $value])->one();
        if ($fuel === null) {
            $fuel = new FuelType();
            $fuel->name = $value;
            $fuel->save();
        }
        $this->fuel_type_id = $fuel->id;
        $this->save();
        return $value;
    }
}

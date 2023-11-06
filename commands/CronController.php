<?php

namespace app\commands;

use app\models\ApiField;
use app\models\Complectation;
use yii\console\Controller;
use app\models\Vacation;

class CronController extends Controller
{
    public function actionTransferLeads()
    {
        $time = time();
        $vacations = Vacation::find()->where(["transfer_lead" => 1])->all();
        foreach ($vacations as $vacation) {
            if ($vacation->date_from <= $time && $time <= $vacation->date_to) {
                $leads = $vacation->user->openLeads;
                foreach ($leads as $lead) {
                    $lead->findResponsible();
                }
            }
        }
    }

    public function actionSyncCars()
    {
        $auto_fields = ApiField::find()->where(["not", ["field" => "URL"]])->andWhere(["not", ["field" => "car_id"]])->all();
        $url = ApiField::find()->where(["field" => "URL"])->one()->value;
        $car_id = ApiField::find()->where(["field" => "car_id"])->one()->value;
        $path_car_id = explode(".", $car_id);

        //получаем данные от API в JSON
        $res = file_get_contents($url);
        $res = json_decode($res, true);

        //переходим по пути с car_id
        $val = $res;
        foreach ($path_car_id as $el) {
            $val = array_column($val, $el);
        }
        $val; //хранит массив car_id
        Complectation::deleteAll(["not", ["id" => $val]]);

        foreach ($res as $data_auto) {
            $val = $data_auto;
            foreach ($path_car_id as $el) {
                $val = $val[$el];
            }
            $complectation = Complectation::findOne($val);
            if ($complectation === null) {
                $complectation = new Complectation();
                $complectation->id = $val;
            }
            foreach ($auto_fields as $field) {
                $f = $field->value;
                if ($f != "") {
                    $f = explode(".", $f);
                    $val = $data_auto;
                    foreach ($f as $el) {
                        $val = $val[$el];
                    }
                    $complectation->{$field->field} = $val;
                }
            }
            $complectation->save();
        }
    }
}

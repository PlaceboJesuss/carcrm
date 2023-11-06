<?php

namespace app\controllers;

use app\models\Contact;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\CustomFieldValue;
use app\models\CustomField;
use app\models\CustomFieldType;
use app\models\Note;
use app\models\ApiField;
use app\models\BodyType;
use app\models\Brand;
use app\models\Pipeline;
use app\models\Complectation;
use app\models\FuelType;
use app\models\Model;
use PDO;
use PharIo\Manifest\Email;

class CarsController extends Controller
{
    private $current_user;
    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            $this->redirect('/login');
        } else {
            $this->current_user = Yii::$app->user->identity;
        }
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }


    public function actionIndex($car_id = -1)
    {
        if ($car_id == -1) {
            $columns = [
                ["name" => "Фирма", "attribute" => "brand"],
                ["name" => "Модель", "attribute" => "model"],
                ["name" => "Комплектация", "attribute" => "name"],
                ["name" => "Цена", "attribute" => "price"],
                ["name" => "Год", "attribute" => "year"],
                ["name" => "Цвет", "attribute" => "color"],
                ["name" => "Тип кузова", "attribute" => "body"],
            ];
            $body = $this->renderPartial('/list/list.twig', ["columns" => $columns, "items" => Complectation::find()->all(), "is_cars" => true]);
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/list.js", "/js/cars/car.js"],
                "need_header" => true,
                "page" => "cars",
                "type" => "cars",
                "body" => $body,
                "user" => $this->current_user,
                "pipelines" => Pipeline::find()->all(),
            ]);
        } else {
            $complectation = Complectation::findOne($car_id);
            $body = $this->renderPartial('car.twig', ["complectation" => $complectation]);
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/cars/car.js"],
                "need_header" => true,
                "page" => "cars",
                "type" => "cars",
                "body" => $body,
                "user" => $this->current_user,
                "pipelines" => Pipeline::find()->all(),
            ]);
        }
    }

    private function prepareResult($status, $text, $result = [], $code = 200)
    {
        $response = Yii::$app->response;
        $response->statusCode = $code;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = [
            "status" => $status,
            "result" => [
                "text" => $text,
            ]
        ];
        $response->data["result"] = array_merge($response->data["result"], $result);
        return $response->data;
    }

    public function actionGetAutocompleates()
    {
        $brands = array_column(Brand::find()->all(), "name");
        $models = array_column(Model::find()->all(), "name");
        $body_types = array_column(BodyType::find()->all(), "name");
        $fuel_types = array_column(FuelType::find()->all(), "name");
        return $this->prepareResult("success", "ok", [
            "autocompleates" => [
                "brand" => $brands,
                "model" => $models,
                "body" => $body_types,
                "fuel" => $fuel_types
            ]
        ]);
    }

    public function actionSave()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        $id = $data["id"];
        unset($data["id"]);
        $comp = Complectation::findOne($id);
        if ($comp === null) {
            $comp = new Complectation();
        }
        foreach ($data as $key => $value) {
            $comp->{$key} = $value;
        }
        $comp->save();
        return $this->prepareResult("success", "Успешно сохранено", ["redirect" => "/cars"]);
    }

    public function actionGetList()
    {
        $post = Yii::$app->request->post();
        $columns = [
            ["name" => "Фирма", "attribute" => "brand"],
            ["name" => "Модель", "attribute" => "model"],
            ["name" => "Комплектация", "attribute" => "name"],
            ["name" => "Цена", "attribute" => "price"],
            ["name" => "Год", "attribute" => "year"],
            ["name" => "Цвет", "attribute" => "color"],
            ["name" => "Тип кузова", "attribute" => "body"],
        ];
        $items = Complectation::find()->all();
        if (isset($post["search"]) && $post["search"] != "") {
            $post["search"] = mb_strtolower($post["search"]);
            foreach ($items as $key => $item) {
                $is_need = false;
                foreach ($columns as $column) {
                    if (strpos(mb_strtolower($item->{$column["attribute"]}), $post["search"]) !== false) {
                        $is_need = true;
                        break;
                    }
                }
                if ($is_need === false) {
                    unset($items[$key]);
                }
            }
        }

        $body = $this->renderPartial('/list/list-inner.twig', [
            "columns" => $columns,
            "items" => $items,
            "is_select" => $post["is_select"] ?? false,
            "select_type" => "car",
        ]);
        return $body;
    }

    public function actionGetNewItem()
    {
        return $this->renderPartial("car.twig", ["is_add" => true]);
    }

    public function actionSync()
    {
        $auto_fields = ApiField::find()->where(["not", ["field" => "URL"]])->andWhere(["not", ["field" => "car_id"]])->all();
        $url = ApiField::find()->where(["field" => "URL"])->one()->value;
        $car_id = ApiField::find()->where(["field" => "car_id"])->one()->value;
        if ($url) {
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
        return $this->redirect("/cars");
    }

    public function actionDelete()
    {
        $post = Yii::$app->request->post();
        if (isset($post["data"]["list"])) {
            $data = $post["data"];
            $arr = array_filter($data["list"], function ($checkboxes) {
                return $checkboxes["value"] === "true";
            });
            $arr = array_column($arr, "id");
            Complectation::deleteAll(["id" => $arr]);
            return $this->prepareResult("success", "Успешно удалено");
        }
    }
}

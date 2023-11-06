<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\User;
use app\models\CustomField;
use app\models\CustomFieldType;
use app\models\CustomFieldValue;
use app\models\Pipeline;
use app\models\SelectOptions;

class CustomFieldController extends Controller
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

    public function actionSave()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            $data = $post["data"];
            if (!isset($data["name"]) || $data["name"] == "") {
                return $this->prepareResult("erorr", "Имя не должно быть пустым", ["erorr_fields" => ["name"]]);
            }

            if (isset($data["id"]) && $data["id"] != "") {
                $cf = CustomField::findOne($data["id"]);
            } else {
                $cf = new CustomField();
                $cf->entity_id = $data["entity_id"];
                $cf->type_id = CustomFieldType::findOne(["type" => $data["type"]])->id;
            }
            $is_select = $cf->type->type == "select";
            if ($is_select) {
                if (isset($data["list"]) &&   count($data["list"]) > 0) {
                    $values = array_column($data["list"], "value");
                    foreach ($values as $value) {
                        if (!isset($value) || $value == "") {
                            return $this->prepareResult("erorr", "Вариант ответа не должен быть пустым");
                        }
                    }
                    $unique = array_unique($values);
                    if (count($unique) != count($values)) {
                        return $this->prepareResult("erorr", "Варианты ответов не должны совпадать");
                    }
                } else {
                    return $this->prepareResult("erorr", "Необходимо добавить хотя бы один вариант ответа");
                }
            }

            $cf->name = $data["name"];
            $cf->save();
            if ($is_select) {
                foreach ($data["list"] as $option) {
                    if (isset($option["id"]) && $option["id"] != "") {
                        $op = SelectOptions::findOne($option["id"]);
                    } else {
                        $op = new SelectOptions();
                        $op->custom_field_id = $cf->id;
                    }
                    $op->text = $option["value"];
                    $op->save();
                }
            }
            return $this->prepareResult("success", "Успешно сохранено");
        }
    }

    public function actionGetModal()
    {
        $post = Yii::$app->request->post();
        if (isset($post['id'])) {
            $cf = CustomField::findOne($post['id']);
            return $this->renderPartial("/settings/twigs/custom_fields/modal.twig", ["is_add" => false, "cf" => $cf, "entity_id" => $post["entity_id"]]);
        } else {
            $types = CustomFieldType::find()->all();
            return $this->renderPartial("/settings/twigs/custom_fields/modal.twig", ["is_add" => true, "types" => $types, "entity_id" => $post["entity_id"]]);
        }
    }

    public function actionDelete()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            CustomField::findOne($post["id"])->delete();
            return $this->prepareResult("success", "Успешно удалено");
        }
    }
}

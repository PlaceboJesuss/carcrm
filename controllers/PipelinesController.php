<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Pipeline;
use app\models\Status;

class PipelinesController extends Controller
{
    private $current_user;

    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            $this->redirect('/login');
        } else {
            $this->current_user = Yii::$app->user->identity;
            $this->current_user->is_self = true;
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

    public function actionAddStatus($pipeline_id = -1)
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];

        if ($data['name'] == "") {
            return $this->prepareResult("erorr", "Название не должно быть пустым", ["error_fields" => ["name"]]);
        }
        if (Status::haveCopy($pipeline_id, $data['name'])) {
            return $this->prepareResult("erorr", "Название не должно совпадать", ["error_fields" => ["name"]]);
        }
        $new_status = new Status();
        $new_status->pipeline_id = $pipeline_id;
        $new_status->name = $data["name"];
        $new_status->save();
        return $this->prepareResult("success", "Cтатус добавлен");
    }

    public function actionAddStatusForm($pipeline_id = 0)
    {
        $data = [
            "hidden" => [],
            "show" => [
                [
                    "id" => "name",
                    "text" => "Название",
                    "type" => "text",
                    "value" => ""
                ],
            ],
        ];
        return $this->renderPartial("/main/twigs/form.twig", $data);
    }

    public function actionSettings($pipeline_id = -1)
    {
        if (Yii::$app->user->identity->is_admin) {
            if ($pipeline_id != -1) {
                $pipeline = Pipeline::findOne($pipeline_id);
                $body = $this->renderPartial("twigs/settings.twig", ["pipeline" => $pipeline]);
            } else {
                $body = "";
            }
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/pipelines/settings.js"],
                "need_header" => true,
                "page" => "leads",
                "body" => $body,
                "user" => Yii::$app->user->identity,
                "pipelines" => Pipeline::find()->all(),
            ]);
        }
    }

    public function actionSave($pipeline_id = -1)
    {
        if (!$this->current_user->is_admin) {
            $this->prepareResult("erorr", "Доступ запрещен");
        }

        $post = Yii::$app->request->post();
        $data = $post['data'];
        if (!isset($data["pipeline_name"]) || $data["pipeline_name"] == "") {
            return $this->prepareResult("erorr", "Имя воронки не может быть пустым", ["pipeline_name"]);
        }

        $pipeline_name = $data["pipeline_name"];
        $pipeline = Pipeline::findOne($pipeline_id);
        $pipeline->name = $pipeline_name;

        foreach ($data["list"] as $status) {
            if ($status["value"] == "") {
                return $this->prepareResult("erorr", "Имя статуса не может быть пустым");
            }
        }
        $pipeline->save();
        $i = 0;
        foreach ($data["list"] as $status) {
            if (!empty($status["id"])) {
                $find_status = Status::findOne($status["id"]);
            } else {
                $find_status = new Status();
                $find_status->pipeline_id = $pipeline_id;
            }
            $find_status->position = $i++;
            $find_status->name = $status["value"];
            $find_status->save();
        }
        $this->prepareResult("success", "Успешно сохранено", ["redirect" => "/pipelines/$pipeline_id"]);
    }


    public function actionGetNewStatus()
    {
        return $this->renderPartial("twigs/status_setting.twig");
    }

    public function actionIndex($pipeline_id = -1)
    {
        if ($pipeline_id != -1) {
            $pipeline = Pipeline::findOne($pipeline_id);
            $body = $this->renderPartial("twigs/index.twig", ["pipeline" => $pipeline]);
        } else {
            $body = "";
        }
        return $this->renderPartial("/main/twigs/main.twig", [
            "scripts" => ["/js/pipelines/index.js"],
            "need_header" => true,
            "page" => "leads",
            "body" => $body,
            "id" => $pipeline_id,
            "type" => "pipeline",
            "user" => Yii::$app->user->identity,
            "pipelines" => Pipeline::find()->all(),
        ]);
    }

    public function actionDeleteStatus()
    {
        if ($this->current_user->is_admin) {
            $this->prepareResult("erorr", "Доступ запрещен");
        }
        $post = Yii::$app->request->post();
        if (isset($post["id"])) {
            Status::findOne($post["id"])->delete();
        }
    }

    public function actionGetNewModal()
    {
        return $this->renderPartial("twigs/add_pipeline_modal.twig");
    }
    public function actionAdd()
    {
        if (Yii::$app->user->identity->is_admin) {
            $post = Yii::$app->request->post();
            $data = $post["data"];
            if (!isset($data["pipeline_name"]) || $data["pipeline_name"] == "") {
                return $this->prepareResult("error", "Название воронки не должно быть пустым", ["error_fields" => ["pipeline_name"]]);
            }
            $pipeline = new Pipeline();
            $pipeline->name = $data["pipeline_name"];
            $pipeline->save();
            $id = $pipeline->id;
            return $this->prepareResult("success", "Успешно добавлено", ["redirect" => "/pipelines/$id/settings"]);
        }
    }

    public function actionDelete()
    {
        if (Yii::$app->user->identity->is_admin) {
            $post = Yii::$app->request->post();
            if (isset($post["id"]) && $post["id"] != "") {
                Pipeline::findOne($post["id"])->delete();
                return $this->prepareResult("success", "Успешно удалено");
            }
        }
    }
}

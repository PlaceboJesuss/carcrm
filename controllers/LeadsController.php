<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Customers;
use app\models\CustomField;
use app\models\CustomFieldValue;
use app\models\EntityType;
use app\models\Pipeline;
use app\models\Status;
use app\models\User;
use app\models\Lead;
use app\models\Note;

class LeadsController extends Controller
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

    public function actionIndex($lead_id = -1)
    {
        if ($lead_id != -1) {
            $lead = Lead::findOne($lead_id);
            $pipelines = Pipeline::find()->all();
            $leads_cfs = CustomField::getLeadField();
            $reserved_statuses = Status::getReservedStatuses();
            $users = User::find()->all();

            $notes = $lead->notes;
            $tasks = $lead->tasks;
            $tasks_notes = array_merge($notes, $tasks);
            usort($tasks_notes, function ($a, $b) {
                return $a->created_at < $b->created_at;
            });

            $lead_cfs = $lead->customFields;
            $lead_cfs_values = [];
            foreach ($lead_cfs as $cf) {
                $lead_cfs_values[$cf->custom_field_id] = $cf->value;
            }
            $body = $this->renderPartial(
                "twigs/lead.twig",
                [
                    "lead" => $lead,
                    "reserved" => $reserved_statuses,
                    "pipelines" => $pipelines,
                    "notifications" => $tasks_notes,
                    "cfs" => $leads_cfs,
                    "cfs_values" => $lead_cfs_values,
                    "users" => $users,
                    "user" => $this->current_user
                ]
            );
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/leads/lead.js"],
                "need_header" => true,
                "type" => "lead",
                "id" => $lead_id,
                "page" => "leads",
                "body" => $body,
                "user" => $this->current_user,
                "pipelines" => Pipeline::find()->all(),
            ]);
        } else {
            $columns = [
                ["name" => "Имя", "attribute" => "name"],
                ["name" => "Стоимость", "attribute" => "discount"],
                ["name" => "Дата создания", "attribute" => "created_at", "type" => "date"],
                ["name" => "Дата закрытия", "attribute" => "closed_at", "type" => "date"]
            ];
            $leads = Lead::find()->all();
            $body = $this->renderPartial('/list/list.twig', ["columns" => $columns, "items" => $leads]);
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/list.js"],
                "need_header" => true,
                "type" => "leads",
                "page" => "leads",
                "body" => $body,
                "user" => Yii::$app->user->identity,
                "pipelines" => Pipeline::find()->all(),
            ]);
        }
    }

    public function actionAttachContact()
    {
        $post = Yii::$app->request->post();
        if (
            isset($post["lead_id"]) &&
            $post["lead_id"] != "" &&
            isset($post["contact_id"]) &&
            $post["contact_id"] != ""
        ) {
            $lead = Lead::findOne($post["lead_id"]);
            $lead->contact_id = $post["contact_id"];
            $lead->save();
            return $this->prepareResult("success", "Успешно прикреплён");
        }
        return $this->prepareResult("error", "Внутренняя ошибка");
    }

    public function actionDetachContact()
    {
        $post = Yii::$app->request->post();
        if (
            isset($post["lead_id"]) &&
            $post["lead_id"] != "" &&
            isset($post["contact_id"]) &&
            $post["contact_id"] != ""
        ) {
            $lead = Lead::findOne($post["lead_id"]);
            if ($lead->contact_id == $post["contact_id"]) {
                $lead->contact_id = null;
            }
            $lead->save();
            return $this->prepareResult("success", "Успешно откреплён");
        }
        return $this->prepareResult("error", "Внутренняя ошибка");
    }

    public function actionGetList()
    {
        $post = Yii::$app->request->post();
        $columns = [
            ["name" => "Имя", "attribute" => "name"],
            ["name" => "Стоимость", "attribute" => "discount"],
            ["name" => "Дата создания", "attribute" => "created_at", "type" => "date"],
            ["name" => "Дата закрытия", "attribute" => "closed_at", "type" => "date"]
        ];
        $leads = Lead::find()->where(['like', 'name', $post["search"]])->all();
        return $this->renderPartial('/list/list-inner.twig', ["columns" => $columns, "items" => $leads]);
    }

    public function actionSendNote()
    {
        $post = Yii::$app->request->post();
        if (isset($post["value"]) && $post["value"] != "") {
            $note = new Note();
            $note->user_id = $this->current_user->id;
            $note->entity_id = $post["id"];
            $note->entity_type = EntityType::findOne(["name" => "lead"])->id;
            $note->text = $post["value"];
            $note->save();
            return $this->prepareResult("success", "Заметка добавлена");
        } else {
            return $this->prepareResult("error", "Текстовое поле должно быть заполнено");
        }
    }

    public function actionGetNewItem()
    {
        $get = Yii::$app->request->get();
        if (isset($get["pipeline_id"])) {
            return $this->renderPartial("twigs/add_lead_modal.twig", $get);
        } else {
            $pipelines = Pipeline::find()->all();
            return $this->renderPartial("twigs/add_lead_modal.twig", ["pipelines" => $pipelines]);
        }
    }

    public function actionDetachCar()
    {
        $post = Yii::$app->request->post();
        $id = $post["lead_id"];
        $lead = Lead::findOne($id);
        $lead->car_id = null;
        $lead->save();
        return $this->prepareResult("success", "Откреплено");
    }

    public function actionAttachCar()
    {
        $post = Yii::$app->request->post();
        $id = $post["lead_id"];
        $lead = Lead::findOne($id);
        $lead->car_id = $post["car_id"];
        $lead->save();
        return $this->prepareResult("success", "Прикреплено");
    }

    public function actionAddNew()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        if (!isset($data["pipeline_id"]) && !isset($data["status_id"])) {
            return $this->prepareResult("error", "Внутренняя ошибка");
        }
        if (!isset($data["lead_name"]) || $data["lead_name"] == "") {
            return $this->prepareResult("error", "Имя сделки должно быть заполнено", ["error_fields" => ["lead_name"]]);
        }
        $lead = new Lead();
        $lead->name = $data["lead_name"];
        $lead->responsible_id = $this->current_user->id;
        if (isset($data["pipeline_id"])) {
            $lead->status_id = Status::find()->where(["pipeline_id" => $data["pipeline_id"]])->orderBy("position")->one()->id;
        } else {
            $lead->status_id = $data["status_id"];
        }
        $lead->save();
        $id = $lead->id;
        return $this->prepareResult("success", "Успешно добавлено", ["redirect" => "/leads/$id"]);
    }

    public function actionSaveLead()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        if (!isset($data["name"]) || $data["name"] == "") {
            return $this->prepareResult("error", "Имя сделки не должно быть пустым", ["error_fields" => ["name"]]);
        }

        $error_fields = [];
        foreach ($data["list"] as $field) {
            $regex = CustomField::findOne($field["id"])->type->regex;
            if ($regex != "" && !preg_match($regex, $field["value"])) {
                $error_fields[] = $field["id"];
            }
        }
        if (count($error_fields) > 0) {
            return $this->prepareResult("error", "Выделенные поля заполнены неверно", ["error_fields" => $error_fields]);
        }

        $lead = Lead::findOne($data["lead_id"]);
        $lead->name = $data["name"];
        $lead->discount = $data["discount"];
        $lead->status_id = $data["status_id"];
        $lead->responsible_id = $data["responsible_id"];
        $lead->save();
        $lead_cf = $lead->customFields;
        $lead_cf_ids = array_column($lead_cf, "custom_field_id");
        foreach ($data["list"] as $field) {
            if (isset($field["value"]) && isset($field["id"]) && $field["id"] != "") {
                $key = array_search($field["id"], $lead_cf_ids);
                if ($key === false) {
                    if ($field["value"] != "") {
                        $val = new CustomFieldValue();
                        $val->entity_id = $lead->id;
                        $val->custom_field_id = $field["id"];
                        $val->value = $field["value"];
                        $val->save();
                    }
                } else {
                    $val = $lead_cf[$key];
                    $val->value = $field["value"];
                    $val->save();
                }
            }
        }
        $this->prepareResult("success", "Успешно сохранено");
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
            Lead::deleteAll(["id" => $arr]);
            return $this->prepareResult("success", "Успешно удалено");
        }
        if (isset($post["lead_id"])) {
            Lead::deleteAll(["id" => $post["lead_id"]]);
            return $this->prepareResult("success", "Успешно удалено");
        }
    }
}

<?php

namespace app\controllers;

use app\models\CustomField;
use app\models\CustomFieldType;
use app\models\CustomFieldValue;
use app\models\HookField;
use app\models\Pipeline;
use app\models\SelectOptions;
use app\models\Lead;
use app\models\Contact;
use Yii;
use yii\web\Controller;
use app\models\User;

class HookController extends Controller
{

    public function beforeAction($action)
    {
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

    public function actionIndex()
    {
        $post = Yii::$app->request->post();
        $lead = new Lead();
        $contact = null;
        $contacts_fields = HookField::contactField();
        if (count($contacts_fields) > 0) {
            foreach ($contacts_fields as $field) {
                if (isset($post["field-for-hook" . $field->id]) && $post["field-for-hook" . $field->id] != "") {
                    if ($field->field_id == "phone") {
                        $contact = Contact::findOne(["phone" => $post["field-for-hook" . $field->id]]);
                    } elseif ($field->field_id == "email") {
                        $contact = Contact::findOne(["email" => $post["field-for-hook" . $field->id]]);
                    }
                    if ($contact != null) {
                        break;
                    }
                }
            }
        }
        if ($contact == null) {
            $contact = new Contact();
            $contact->name = "Контакт с сайта";
            $contact->save();
        }
        $lead->name = "Сделка с сайта";
        $lead->status_id = $post["status_id"];
        unset($post["status_id"]);
        $lead->findResponsible();
        $lead->contact_id = $contact->id;
        $lead->save();

        foreach ($post as $key => $value) {
            if ($value != "") {
                $key = str_replace("field-for-hook", "", $key);
                $field = HookField::findOne($key);
                if (is_numeric($field->field_id)) {
                    $cfv = new CustomFieldValue();
                    $cfv->custom_field_id = $field->field_id;
                    $entity = CustomField::findOne($field->field_id)->entity->name;
                    if ($entity == "lead") {
                        $cfv->entity_id = $lead->id;
                    } elseif ($entity == "contact") {
                        $cfv->entity_id = $contact->id;
                    }
                    $cfv->value = $value;
                    $cfv->save();
                } else {
                    if (in_array($field->field_id, ["lead_name", "discount"])) {
                        $id = $field->field_id == "lead_name" ? "name" : $field->field_id;
                        $lead->{$id} = $value;
                    } elseif (in_array($field->field_id, ["contact_name", "phone", "email"])) {
                        $id = $field->field_id == "contact_name" ? "name" : $field->field_id;
                        $contact->{$id} = $value;
                    }
                }
            }
        }
        $lead->save();
        $contact->save();
    }

    public function actionGetForm()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect('/login');
        }
        $fields = HookField::find()->all();
        $pipelines = Pipeline::find()->all();
        return $this->renderPartial("form.twig", ["fields" => $fields, "pipelines" => $pipelines]);
    }

    public function actionSave()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect('/login');
        }
        if (Yii::$app->user->identity->is_admin) {
            $post = Yii::$app->request->post();
            $data = $post["data"];
            $hf = new HookField();
            foreach ($data["list"] as $field) {
                if ($field["id"] == "text") {
                    $hf->text = $field["value"];
                } elseif ($field["id"] == "option") {
                    $hf->field_id = $field["value"];
                }
            }
            $hf->save();
            return $this->prepareResult("success", "Успешно сохранено");
        }
    }

    public function actionGetAdd()
    {
        $lead_cf = CustomField::getLeadField();
        $cont_cf = CustomField::getContactField();
        $hook_options = [
            [
                "name" => "Сделка",
                "options" => array_merge(
                    [
                        [
                            "id" => "lead_name",
                            "name" => "Название сделки"
                        ],
                        [
                            "id" => "discount",
                            "name" => "Стоимость"
                        ]
                    ],
                    $lead_cf
                )
            ],
            [
                "name" => "Контакт",
                "options" => array_merge(
                    [
                        [
                            "id" => "contact_name",
                            "name" => "Имя контакта"
                        ],
                        [
                            "id" => "phone",
                            "name" => "Номер телефона"
                        ],
                        [
                            "id" => "email",
                            "name" => "Email"
                        ]
                    ],
                    $cont_cf
                )
            ]
        ];
        return $this->renderPartial("modal-add.twig", ["hook_options" => $hook_options]);
    }

    public function actionDelete()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect('/login');
        }
        if (Yii::$app->user->identity->is_admin) {
            $post = Yii::$app->request->post();
            HookField::findOne($post["id"])->delete();
            $this->prepareResult("success", "Успешно удалено");
        }
    }
}

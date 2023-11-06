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
use app\models\EntityType;
use app\models\Pipeline;
use PDO;
use PharIo\Manifest\Email;

class ContactsController extends Controller
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

    private function groupContains($group, $contacts, $contact)
    {
        $phone = $contact["phone"];
        $email = $contact["email"];

        foreach ($group as $id) {
            if ($phone != "" && $phone == $contacts[$id]->phone) {
                return true;
            }
            if ($email != "" &&  $email == $contacts[$id]->email) {
                return true;
            }
        }
        return false;
    }

    private function searchDoubles($contacts)
    {
        $groups = [];
        foreach ($contacts as $id => $contact) {
            $n = -1;
            foreach ($groups as $num_gr => $group) {
                if ($this->groupContains($group, $contacts, $contact)) {
                    if ($n == -1) {
                        $groups[$num_gr][] = $id;
                        $n = $num_gr;
                    } else {
                        $groups[$num_gr] = array_merge($groups[$num_gr], $groups[$n]);
                        unset($groups[$n]);
                        break;
                    }
                }
            }
            if ($n == -1) {
                $groups[] = [$id];
            }
        }
        $groups = array_filter($groups, function ($a) {
            return count($a) > 1;
        });
        return $groups;
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

    public function actionSendNote()
    {
        $post = Yii::$app->request->post();
        if (isset($post["value"]) && $post["value"] != "") {
            $note = new Note();
            $note->user_id = $this->current_user->id;
            $note->entity_id = $post["id"];
            $note->entity_type = EntityType::findOne(["name" => "contact"])->id;
            $note->text = $post["value"];
            $note->save();
            return $this->prepareResult("success", "Заметка добавлена");
        } else {
            return $this->prepareResult("error", "Текстовое поле должно быть заполнено");
        }
    }

    public function actionSaveContact()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        if (!isset($data["name"]) || $data["name"] == "") {
            return $this->prepareResult("error", "Имя контакта не должно быть пустым", ["error_fields" => ["name"]]);
        }
        $error_fields = [];
        $phone_regex = CustomFieldType::findOne(["type" => "phone"])->regex;
        $email_regex = CustomFieldType::findOne(["type" => "email"])->regex;
        if ($phone_regex != "" && !preg_match($phone_regex, $data["phone"])) {
            $error_fields[] = "phone";
        }
        if ($email_regex != "" && !preg_match($email_regex, $data["email"])) {
            $error_fields[] = "email";
        }

        if (!empty($data["list"])) {
            foreach ($data["list"] as $field) {
                $regex = CustomField::findOne($field["id"])->type->regex;
                if ($regex != "" && !preg_match($regex, $field["value"])) {
                    $error_fields[] = $field["id"];
                }
            }
        }
        if (count($error_fields) > 0) {
            return $this->prepareResult("error", "Выделенные поля заполнены неверно", ["error_fields" => $error_fields]);
        }

        $contact = Contact::findOne($data["contact_id"]);
        $contact->name = $data["name"];
        $contact->phone = $data["phone"];
        $contact->email = $data["email"];
        $contact->save();
        $contact_cf = $contact->customFields;
        $contact_cf_ids = array_column($contact_cf, "custom_field_id");
        if (!empty($data["list"])) {
            foreach ($data["list"] as $field) {
                if (isset($field["value"]) && isset($field["id"]) && $field["id"] != "") {
                    $key = array_search($field["id"], $contact_cf_ids);
                    if ($key === false) {
                        if ($field["value"] != "") {
                            $val = new CustomFieldValue();
                            $val->entity_id = $contact->id;
                            $val->custom_field_id = $field["id"];
                            $val->value = $field["value"];
                            $val->save();
                        }
                    } else {
                        $val = $contact_cf[$key];
                        $val->value = $field["value"];
                        $val->save();
                    }
                }
            }
        }
        $this->prepareResult("success", "Успешно сохранено");
    }

    public function actionGetNewItem()
    {
        return $this->renderPartial("twigs/add_contact_modal.twig");
    }

    public function actionAddNew()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        if (!isset($data["contact_name"]) || $data["contact_name"] == "") {
            return $this->prepareResult("error", "Имя контакта должно быть заполнено", ["error_fields" => ["contact_name"]]);
        }
        $contact = new Contact();
        $contact->name = $data["contact_name"];
        $contact->save();
        $id = $contact->id;
        return $this->prepareResult("success", "Успешно добавлено", ["redirect" => "/contacts/$id"]);
    }

    public function actionDelete()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        $arr = array_filter($data["list"], function ($checkboxes) {
            return $checkboxes["value"] === "true";
        });
        $arr = array_column($arr, "id");
        Contact::deleteAll(["id" => $arr]);
        return $this->prepareResult("success", "Успешно удалено");
    }

    public function actionGetList()
    {
        $post = Yii::$app->request->post();
        $columns = [
            ["name" => "Имя", "attribute" => "name"],
            ["name" => "Email", "attribute" => "email"],
            ["name" => "Телефон", "attribute" => "phone"]
        ];
        if (isset($post["search"])  && $post["search"] != "") {
            $contacts = Contact::find()
                ->where(['like', 'name', $post["search"] ?? ""])
                ->orWhere(['like', 'email', $post["search"] ?? ""])
                ->orWhere(['like', 'phone', $post["search"] ?? ""])
                ->all();
        } else {
            $contacts = Contact::find()->all();
        }
        $is_select_contact = false;
        if (isset($post["is_select"])) {
            $is_select = $post["is_select"];
        }
        $inner = false;
        if (isset($post["inner"])) {
            $inner = $post["inner"];
        }
        if ($inner) {
            return $this->renderPartial('/list/list-inner.twig', [
                "columns" => $columns,
                "items" => $contacts,
                "is_select" => $is_select,
                "select_type"=>"contact",
            ]);
        } else {
            return $this->renderPartial('/list/list.twig', [
                "columns" => $columns,
                "items" => $contacts,
                "is_select" => $is_select,
                "select_type"=>"contact",

            ]);
        }
    }

    private function getEmail($list)
    {
        foreach ($list as $data) {
            if (strpos($data["id"], "email-") !== false && $data["value"] == "true") {
                return str_replace("email-", "", $data["id"]);
            }
        }
        return false;
    }

    private function getPhone($list)
    {
        foreach ($list as $data) {
            if (strpos($data["id"], "phone-") !== false && $data["value"] == "true") {
                return str_replace("phone-", "", $data["id"]);
            }
        }
        return false;
    }

    private function getContactsMerge($list)
    {
        $ids = [];
        foreach ($list as $data) {
            if (strpos($data["id"], "select-") !== false && $data["value"] == "true") {
                $ids[] = str_replace("select-", "", $data["id"]);
            }
        }
        return $ids;
    }

    public function actionMergeDoubles()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        $email_id = $this->getEmail($data["list"]);
        $phone_id = $this->getPhone($data["list"]);
        $contacts_id = $this->getContactsMerge($data["list"]);

        $main_id = $contacts_id[0];
        $main_contact = Contact::findOne($main_id);
        unset($contacts_id[0]);
        foreach ($contacts_id as $id) {
            $contact = Contact::findOne($id);
            foreach ($contact->notes as $note) {
                $note->entity_id = $main_id;
                $note->save();
            }
            if ($id === $email_id) {
                $main_contact->email = $contact->email;
            }
            if ($id === $phone_id) {
                $main_contact->phone = $contact->phone;
            }
            foreach ($contact->leads as $lead) {
                $lead->contact_id = $main_id;
                $lead->save();
            }
            $contact->delete();
        }
        $main_contact->save();
        return $this->prepareResult("success", "Успешно объединено");
    }

    public function actionFindDoubles()
    {
        $contacts = Contact::find()->all();
        $groups = $this->searchDoubles($contacts);

        $body = $this->renderPartial('twigs/doubles.twig', ["groups" => $groups, "contacts" => $contacts]);
        return $this->renderPartial("/main/twigs/main.twig", [
            "scripts" => ["/js/contacts/contact.js"],
            "need_header" => true,
            "page" => "contacts",
            "type" => "contacts",
            "body" => $body,
            "user" => $this->current_user,
            "pipelines" => Pipeline::find()->all(),
        ]);
    }

    public function actionIndex($contact_id = -1)
    {
        if ($contact_id === -1) {
            $columns = [
                ["name" => "Имя", "attribute" => "name"],
                ["name" => "Email", "attribute" => "email"],
                ["name" => "Телефон", "attribute" => "phone"]
            ];
            $body = $this->renderPartial('/list/list.twig', ["columns" => $columns, "items" => Contact::find()->all(), "is_contact" => true]);
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/list.js"],
                "need_header" => true,
                "page" => "contacts",
                "type" => "contacts",
                "body" => $body,
                "user" => $this->current_user,
                "pipelines" => Pipeline::find()->all(),
            ]);
        } else {
            $contact = Contact::findOne($contact_id);
            $cfs = CustomField::getContactField();
            $cfs_values = $contact->customFields;
            $contact_cfs_values = [];
            foreach ($cfs_values as $cf) {
                $contact_cfs_values[$cf->custom_field_id] = $cf->value;
            }
            $body = $this->renderPartial(
                "twigs/contact.twig",
                [
                    "contact" => $contact,
                    "cfs" => $cfs,
                    "cfs_values" => $contact_cfs_values,
                    "user" => $this->current_user,
                ]
            );
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" => ["/js/contacts/contact.js"],
                "need_header" => true,
                "page" => "contacts",
                "type" => "contact",
                "id" => $contact_id,
                "body" => $body,
                "user" => $this->current_user,
                "pipelines" => Pipeline::find()->all(),
            ]);
        }
    }
}

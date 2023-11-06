<?php

namespace app\controllers;

use app\models\ApiField;
use app\models\CustomField;
use app\models\CustomFieldType;
use app\models\CustomFieldValue;
use app\models\HookField;
use app\models\Pipeline;
use app\models\SelectOptions;
use app\models\Lead;
use Yii;
use yii\web\Controller;
use app\models\User;
use app\models\Vacation;

class SettingsController extends Controller
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


    public function actionGetOption()
    {
        return $this->renderPartial("twigs/custom_fields/option.twig");
    }

    public function actionDeleteOption()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            $id = $post["id"];
            if ($id != "") {
                SelectOptions::findOne($id)->delete();
                return $this->prepareResult("success", "");
            }
            $this->prepareResult("error", "");
        }
    }

    public function actionSaveVacation()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            $data = $post["data"];
            if (isset($data["id"]) && $data["id"] != "") {
                $vac = Vacation::findOne($data["id"]);
            } else {
                $vac = new Vacation();
            }
            $vac->date_from = strtotime($data["date_from"]);
            $vac->date_to = strtotime($data["date_to"]) + 24 * 60 * 60 - 1;
            if ($vac->date_from > $vac->date_to) {
                return $this->prepareResult("error", "Дата начала должна быть раньше даты окончания отпуска", ["error_fields" => ["date_from", "date_to"]]);
            }
            $vac->user_id = $data["user_id"];
            $vac->transfer_lead = $data["transfer_lead"] == "true" ? 1 : 0;
            $vac->save();
            return $this->prepareResult("success", "Успешно сохранено");
        }
    }

    public function actionSaveAutoField()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            $data = $post["data"];
            foreach ($data as $field => $value) {
                $f = ApiField::find()->where(["field" => $field])->one();
                if ($f === null) {
                    $f = new ApiField();
                    $f->field = $field;
                }
                $f->value = $value;
                $f->save();
            }
            return $this->prepareResult("success", "Успешно сохранено");
        }
    }

    public function actionDeleteVacation()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            Vacation::findOne($post["id"])->delete();
            $this->prepareResult("success", "Успешно удалено");
        }
    }

    public function actionGetEditVacation()
    {
        $post = Yii::$app->request->post();
        $vacation = Vacation::findOne($post["id"]);
        $users = User::find()->all();
        return $this->renderPartial("twigs/add-vacation-modal.twig", ["users" => $users, "vacation" => $vacation]);
    }

    public function actionGetNewVacation()
    {
        if ($this->current_user->is_admin) {
            $users = User::find()->all();
            return $this->renderPartial("twigs/add-vacation-modal.twig", ["users" => $users]);
        }
    }

    public function actionIndex()
    {
        $users = User::find()->all();
        $pipelines = Pipeline::find()->all();
        $lead_cf = CustomField::getLeadField();
        $cont_cf = CustomField::getContactField();
        $cf_types = CustomFieldType::find()->all();
        $hook_fields = HookField::find()->all();
        $vacations = Vacation::find()->all();
        $auto_fields = ApiField::find()->where(["not", ["field" => "URL"]])->all();
        $url = ApiField::find()->where(["field" => "URL"])->one();

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

        $body = $this->renderPartial('twigs/index.twig', [
            "current_user" => $this->current_user,
            "users" => $users,
            "lead_custom_fields" => $lead_cf,
            "contact_custom_fields" => $cont_cf,
            "custom_fields_types" => $cf_types,
            "hook_options" => $hook_options,
            "hook_fields" => $hook_fields,
            "vacations" => $vacations,
            "auto_fields" => $auto_fields,
            "url" => $url
        ]);
        return $this->renderPartial("/main/twigs/main.twig", [
            "scripts" => [
                "/js/settings/index.js"
            ],
            "need_header" => true,
            "page" => "settings",
            "body" => $body,
            "user" => $this->current_user,
            "pipelines" => $pipelines
        ]);
    }

    public function actionGetSettingsModal()
    {
        $post = Yii::$app->request->post();
        if (isset($post['data']['id'])) {
            $id = $post['data']['id'];
            if ($id == $this->current_user->id) {
                $user = $this->current_user;
            } else {
                $user = User::findOne($id);
            }
            return $this->renderPartial(
                "twigs/modal-user-item.twig",
                ["item" => $user]
            );
        } else {
            return $this->renderPartial(
                "twigs/modal-user-item.twig"
            );
        }
    }

    public function actionDeleteUser()
    {
        if ($this->current_user->is_admin) {
            $post = Yii::$app->request->post();
            if (isset($post['data']['id'])) {
                if ($post['data']['id'] != $this->current_user->id) {
                    $id = $post['data']['id'];
                    $user = User::findOne($id);
                    $user->delete();
                    $res = $this->prepareResult("OK", null);
                } else {
                    $res = $this->prepareResult("error", "Запрещено удалять свой аккаунт");
                }
            } else {
                $res = $this->prepareResult("error", "Bad Request");
            }
        } else {
            $res = $this->prepareResult("error", "Нет прав на эту операцию");
        }
        echo json_encode($res);
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
        $post = Yii::$app->request->post();
        $user_request = $post["data"];
        if ($user_request["id"] != "") {
            $is_self = $user_request['id'] == $this->current_user->id;
            $user_bd = User::findIdentity($user_request["id"]);
        } else {
            $is_self = false;
            $user_bd = new User();
        }

        if (!$is_self && !$this->current_user->is_admin) {
            return $this->prepareResult("error", "Недостаточно прав");
        }

        if ($user_request["name"] != "") {
            $user_bd->name = $user_request["name"];
        } else {
            return $this->prepareResult("error", "Имя не должно быть пустым", ["error_fields" => ["name"]]);
        }

        if ($user_request["login"] != "") {
            if ($user_bd->login != $user_request["login"]) {
                if (User::isFreeLogin($user_request["login"])) {
                    $user_bd->login = $user_request["login"];
                } else {
                    return $this->prepareResult("error", "Данный логин уже занят", ["error_fields" => ["login"]]);
                }
            }
        } else {
            return $this->prepareResult("error", "Логин не должно быть пустым", ["error_fields" => ["login"]]);
        }

        if ($is_self) {
            if ($user_request["old_password"] != "") {
                if (md5($user_request["old_password"]) == $user_bd->password) {
                    if (User::isCorrectPassword($user_request["new_password"])) {
                        $user_bd->password = md5($user_request["new_password"]);
                    } else {
                        return $this->prepareResult("error", "Новый пароль должен иметь 8 символов или более", ["error_fields" => ["new_password"]]);
                    }
                } else {
                    return $this->prepareResult("error", "Пароль неверен", ["error_fields" => ["old_password"]]);
                }
            }
        } else {
            if ($user_request["password"] != "") {
                if (User::isCorrectPassword($user_request["password"])) {
                    $user_bd->password = md5($user_request["password"]);
                } else {
                    return $this->prepareResult("error", "Пароль должен иметь 8 символов или более", ["error_fields" => ["new_password"]]);
                }
            }
            $user_bd->is_admin = $user_request["is_admin"] == "true";
        }
        $user_bd->save();
        return $this->prepareResult("success", "Изменения сохранены");
    }
}

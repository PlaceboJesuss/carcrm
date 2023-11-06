<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Pipeline;
use app\models\User;
use app\models\Task;

class TasksController extends Controller
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


    public function actionIndex()
    {
        $users = User::find()->all();
        $pipelines = Pipeline::find()->all();
        $ids = array_column($users, "id");
        $users = array_combine($ids, $users);
        $body = $this->renderPartial('index.twig', ["users" => $users, "self_user_id" => $this->current_user->id]);
        return $this->renderPartial("/main/twigs/main.twig", [
            "scripts" => ["/js/tasks/tasks.js"],
            "need_header" => true,
            "page" => "tasks",
            "pipelines" => $pipelines,
            "body" => $body,
            "user" => $this->current_user
        ]);
    }

    public function actionGetInner()
    {
        $post = Yii::$app->request->post();
        $users = User::find()->all();
        $ids = array_column($users, "id");
        $users = array_combine($ids, $users);
        return $this->renderPartial('inner.twig', ["user" => $users[$post["user_id"]], "self_user_id" => $this->current_user->id]);
    }

    public function actionGetConfirmModal()
    {
        $get = Yii::$app->request->get();
        return $this->renderPartial("confirm-modal.twig", ["id" => $get["id"]]);
    }

    public function actionConfirm()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        $task = Task::findOne($data["id"]);
        if ($task->user_id == $this->current_user->id) {
            $task->result = $data["text"];
            $task->success = true;
            $task->closed_at = time();
            $task->save();
            return $this->prepareResult("success", "Сохранено");
        }
    }

    public function actionCancel()
    {
        $post = Yii::$app->request->post();
        $task = Task::findOne($post["id"]);
        if ($task->user_id == $this->current_user->id) {
            $task->closed_at = time();
            $task->success = false;
            $task->save();
            return $this->prepareResult("success", "Сохранено");
        }
    }

    public function actionGetCreateModal()
    {
        $get = Yii::$app->request->get();
        $lead_id = $get["lead_id"] ?? null;
        $users = User::find()->all();
        return $this->renderPartial("add-modal.twig", [
            "lead_id" => $lead_id,
            "users" => $users,
            "self" => $this->current_user,
        ]);
    }

    public function actionAdd()
    {
        $post = Yii::$app->request->post();
        $data = $post["data"];
        if ($data["text"] === "") {
            return $this->prepareResult("error", "Текст задачи не должен быть пустым", ["error_fields" => "text"]);
        }
        $error_fields = [];
        if ($data["minutes"] < 0 || $data["minutes"] > 59) {
            $error_fields[] = "minutes";
        }
        if ($data["hours"] < 0 || $data["hours"] > 23) {
            $error_fields[] = "hours";
        }
        if (count($error_fields) > 0) {
            return $this->prepareResult("error", "Выделенные поля заполнены неверно", ["error_fields" => $error_fields]);
        }

        $task = new Task();
        $task->action = $data["text"];
        $date = strtotime($data["date"]);
        $date += $data["minutes"] * 60;
        $date += $data["hours"] * 60 * 60;
        $task->date = $date;
        $task->user_id = $this->current_user->is_admin ?  $data["user_id"] : $this->current_user->id;
        $task->lead_id = $data["lead_id"] ?? null;
        $task->director_id = $this->current_user->id;
        $task->save();
        return $this->prepareResult("success", "Успешно добавлено");
    }
}

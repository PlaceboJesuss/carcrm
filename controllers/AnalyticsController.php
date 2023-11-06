<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Pipeline;
use app\models\User;
use app\models\Lead;
use app\models\Note;
use app\models\Task;

class AnalyticsController extends Controller
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

    private function getInner($date_from, $date_to, $user_id)
    {
        if ($user_id == "") {
            $user_id = array_column(User::find()->all(), "id");
        } else {
            $user_id = [$user_id];
        }
        $date_from = strtotime($date_from);
        $date_to = strtotime($date_to) + 24 * 60 * 60 - 1;
        $simple_numbers = [
            [
                "text" => "Сумма успешно-закрытых сделок",
                "value" => Lead::find()->where(["between", "closed_at", $date_from, $date_to])->andWhere(["status_id" => 200])->andWhere(["responsible_id" => $user_id])->sum("discount") ?? 0,
                "size" => "2-1"
            ],
            [
                "text" => "Успешно-закрытых сделок",
                "value" => Lead::find()->where(["between", "closed_at", $date_from, $date_to])->andWhere(["status_id" => 200])->andWhere(["responsible_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Неуспешно-закрытых сделок",
                "value" => Lead::find()->where(["between", "closed_at", $date_from, $date_to])->andWhere(["status_id" => 400])->andWhere(["responsible_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Закрытых сделок",
                "value" => Lead::find()->where(["between", "closed_at", $date_from, $date_to])->andWhere(["responsible_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Новых сделок",
                "value" => Lead::find()->where(["between", "created_at", $date_from, $date_to])->andWhere(["responsible_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Открытых сделок:",
                "value" => Lead::find()->where(["not", ["status_id" => [200, 400]]])->andWhere(["responsible_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Сумма открытых сделок",
                "value" => Lead::find()->where(["not", ["status_id" => [200, 400]]])->andWhere(["responsible_id" => $user_id])->sum("discount") ?? 0,
                "size" => "2-1"
            ],

            [
                "text" => "Задач создано",
                "value" => Task::find()->where(["between", "created_at", $date_from, $date_to])->andWhere(["user_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Задач выполнено",
                "value" => Task::find()->where(["between", "closed_at", $date_from, $date_to])->andWhere(["success" => 1])->andWhere(["user_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Задач отменено",
                "value" => Task::find()->where(["between", "closed_at", $date_from, $date_to])->andWhere(["success" => 0])->andWhere(["user_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Активных задач",
                "value" => Task::find()->where(["closed_at" => null])->andWhere(["user_id" => $user_id])->count(),
                "size" => "1-1"
            ],
            [
                "text" => "Новых примечаний",
                "value" => Note::find()->where(["between", "created_at", $date_from, $date_to])->andWhere(["user_id" => $user_id])->count(),
                "size" => "2-1"
            ],
        ];

        $user_id = implode(",", $user_id);
        $columns_charts = [
            [
                "text" => "Распределение сделок по воронкам",
                "columns" => Yii::$app->db->createCommand("SELECT pipelines.name, COUNT(leads.id) AS value FROM pipelines LEFT JOIN statuses ON statuses.pipeline_id = pipelines.id LEFT JOIN leads ON statuses.id = leads.status_id WHERE leads.responsible_id IN ($user_id) GROUP BY pipelines.id;")->queryAll()
            ],
            [
                "text" => "Распределение сделок по менеджерам",
                "columns" => Yii::$app->db->createCommand("SELECT users.name, COUNT(leads.id) AS value FROM users LEFT JOIN leads ON leads.responsible_id = users.id AND (NOT leads.status_id = 200 OR NOT leads.status_id = 400) GROUP BY users.id;")->queryAll()
            ],
        ];
        $pipelines = Pipeline::find()->all();
        foreach ($pipelines as $pipeline) {
            $pipeline_id = $pipeline->id;
            $columns_charts[] = [
                "text" => "Распределение по статусам в " . $pipeline->name,
                "columns" => Yii::$app->db->createCommand("SELECT statuses.name AS name, COUNT(leads.id) AS value FROM pipelines LEFT JOIN statuses ON statuses.pipeline_id = pipelines.id LEFT JOIN leads ON statuses.id = leads.status_id AND leads.responsible_id IN ($user_id) WHERE pipelines.id = $pipeline_id  GROUP BY statuses.id;")->queryAll()
            ];
        }



        return  $this->renderPartial(
            "twigs/inner.twig",
            [
                "simple_numbers" => $simple_numbers,
                "columns_charts" => $columns_charts,
            ]
        );
    }

    public function actionGetInner()
    {
        $post = Yii::$app->request->post();
        return $this->getInner($post["date_from"], $post["date_to"], $post["user"]);
    }

    public function actionIndex()
    {
        $date_to = date("Y-m-d");
        $date_from = date("Y-m-01");

        $inner = $this->getInner($date_from, $date_to, "");

        $body =  $this->renderPartial(
            "twigs/index.twig",
            [
                "inner" => $inner,
                "date_from" => $date_from,
                "date_to" => $date_to,
                "users" => User::find()->all(),
            ]
        );
        return $this->renderPartial("/main/twigs/main.twig", [
            "scripts" => ["/js/analytics/index.js"],
            "need_header" => true,
            "type" => "lead",
            "page" => "leads",
            "body" => $body,
            "user" => $this->current_user,
            "pipelines" => Pipeline::find()->all(),
        ]);
    }
}

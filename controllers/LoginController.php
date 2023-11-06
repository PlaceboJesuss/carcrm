<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\User;

class LoginController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $login = $data['login'];
            $pass = $data['pass'];
            $pass = md5($pass);
            $user = User::find()->where(['login'=>$login, 'password'=>$pass])->one();
            if ($user === null) {
                $res = [
                    "status" => "no_find",
                    "result" => null,
                ];
            } else {
                Yii::$app->user->login($user, 60*60*24*7);
                $res = [
                    "status" => "find",
                    "result" => null
                ];
            }
            echo json_encode($res);
        } else {
            if (!Yii::$app->user->isGuest) {
                $this->redirect('/leads');
            }
            $body = $this->renderPartial('index');
            return $this->renderPartial("/main/twigs/main.twig", [
                "scripts" =>[
                    "/js/login/index.js"
                ],
                "need_header" => false,
                "body"=> $body,
            ]);
        }
    }
    
    public function actionLogout()
    {
        Yii::$app->user->logout();
        $this->redirect('/login');
    }
}

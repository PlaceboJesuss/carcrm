<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class TemplateController extends Controller
{
    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            $this->redirect('/login');
        }
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionGetInput()
    {
        $post = Yii::$app->request->post();
        $input_type = $post['type'];
        return $this->renderPartial(
            "/inputs/$input_type.twig",
            [
                "class" => $post['class'] ?? [],
                "id" => $post['id'] ?? [],
                "options"=> $post['options'] ?? []
            ]
        );
    }
}

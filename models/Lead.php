<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

class Lead extends ActiveRecord
{
    public static function tableName()
    {
        return '{{leads}}';
    }

    public function getResponsible()
    {
        return $this->hasOne(User::class, ["id" => "responsible_id"]);
    }

    public function getCustomFields()
    {
        return $this->hasMany(CustomFieldValue::class, ["entity_id" => "id"])->where(["custom_field_id" => array_column(CustomField::getLeadField(), "id")]);
    }

    public function getNotes()
    {
        return $this->hasMany(
            Note::class,
            [
                "entity_id" => "id",
            ]
        );
    }

    public function getTasks()
    {
        return $this->hasMany(Task::class, ["lead_id" => "id"]);
    }

    public function getStatus()
    {
        return $this->hasOne(Status::class, ["id" => "status_id"]);
    }

    public function getType()
    {
        return $this->hasOne(EntityType::class, ["name" => "lead"]);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        Task::deleteAll(["id" => array_column($this->tasks, "id")]);
        Note::deleteAll(["id" => array_column($this->notes, "id")]);
        CustomField::deleteAll(["id" => array_column($this->customFields, "id")]);
        return true;
    }

    public function clearCustomFieldsValue()
    {
        $cf = $this->customFields;
        if ($cf) {
            $ids = array_column($cf, "id");
            CustomFieldValue::deleteAll(["id" => $ids]);
        }
    }

    public function getContact()
    {
        return $this->hasOne(Contact::class, ["id" => "contact_id"]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->created_at = time();
        }
        if ($this->status_id == 200 || $this->status_id == 400) {
            $this->closed_at = time();
        } else {
            $this->closed_at = null;
        }
        return true;
    }

    public function findResponsible()
    {
        $users = User::find()->all();
        $min_count_lead = PHP_INT_MAX;
        $min_key = -1;
        foreach ($users as $key => $user) {
            if ($user->isActive) {
                $count = count($user->openLeads);
                if ($count < $min_count_lead) {
                    $min_key = $key;
                    $min_count_lead = $count;
                }
            }
        }
        if ($min_key != -1) {
            $this->responsible_id = $users[$min_key]->id;
            $this->save();
            return $this->responsible_id;
        }
        return false;
    }

    public function getCar()
    {
        return $this->hasOne(Complectation::class, ["id" => "car_id"]);
    }
}

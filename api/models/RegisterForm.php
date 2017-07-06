<?php

namespace api\models;

use common\models\Token;
use common\models\User;
use yii\base\Model;

/**
 * Login form
 */
class RegisterForm extends Model
{
    public $name;
    public $email;
    public $password;



    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6],


            ['name', 'string'],

        ];
    }

    public function register()
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->status = User::STATUS_ACTIVE;
        $user->name = $this->name;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();

        return $user->save() ? $user : null;
    }

    public function generateRegistrationToken($user)
    {
        $token = new Token();
        $token->user_id = $user->id;
        $token->generateToken(time() + 3600 * 24 *7);
        return $token->save() ? $token : null;
    }




}

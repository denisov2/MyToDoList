<?php

namespace api\models;

use common\models\Token;
use common\models\User;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $google_token;

    private $_user;

    const SCENARIO_LOGIN = 'login';
    const SCENARIO_GOOGLE_LOGIN = 'google_login';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_LOGIN] = ['email', 'password'];
        $scenarios[self::SCENARIO_GOOGLE_LOGIN] = ['email', 'google_token'];
        return $scenarios;
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['email', 'password'], 'required',  'on' => self::SCENARIO_LOGIN],
            // password is validated by validatePassword()
            ['password', 'validatePassword',  'on' => self::SCENARIO_LOGIN],

            [['email', 'google_token'], 'required',  'on' => self::SCENARIO_GOOGLE_LOGIN],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * @return Token|null
     */
    public function auth()
    {
        if ($this->validate()) {
            $token = new Token();
            $token->user_id = $this->getUser()->id;
            $token->generateToken(time() + 3600 * 24 *7);
            return $token->save() ? $token : null;
        } else {
            return null;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByEmail($this->email);
        }

        return $this->_user;
    }

}

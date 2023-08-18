<?php
/**
*  Copyright (C) 2023 Maxim [maxirmx] Samsonov (www.sw.consulting)
*  All rights reserved.
*  This file is a part of s-tracker applcation
*
*  Redistribution and use in source and binary forms, with or without
*  modification, are permitted provided that the following conditions
*  are met:
*  1. Redistributions of source code must retain the above copyright
*  notice, this list of conditions and the following disclaimer.
*  2. Redistributions in binary form must reproduce the above copyright
*  notice, this list of conditions and the following disclaimer in the
*  documentation and/or other materials provided with the distribution.
*
*  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
*  ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
*  TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
*  PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS OR CONTRIBUTORS
*  BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
*  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
*  SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
*  INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
*  CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
*  ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
*  POSSIBILITY OF SUCH DAMAGE.
*/
require_once PROJECT_ROOT_PATH . "/Model/LinkModel.php";
require_once PROJECT_ROOT_PATH . "/Model/UserModel.php";
require_once PROJECT_ROOT_PATH . "/Controller/Libs/jwt.php";

class AuthController extends BaseController
{
    public function checkAuth() {
        $token = get_bearer_token();
        if (!$token) {
            $this->notAuthorized('Необходимо войти в систему');
        }
        $user = is_jwt_valid($token, JWT_SECRET);
        if (!$user) {
            $this->notAuthorized('Необходимо войти в систему');
        }
        if (!$user->isEnabled) {
            $this->forbidden('Учетная запись не активна.');
        }
        return $user;
    }

    public function processToken($token, $method) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $userModel = new UserModel();
            $m = strtoupper($method);
            if (isset($token) && $m == 'GET') {
                $data = is_jwt_valid($token, JWT_SECRET);
                if (!$data || !$data->email) {
                    $this->notAuthorized('Время действия ссылки истекло.');
                }
                if ($data->type == 'register' || $data->type == 'recover'){
                    $userModel->enableUserByEmail($data->email);
                    $rsp = $this->login($userModel, $data->email, null);
                }
                else  {
                    $this->notSupported();
                }
            }
            else  {
                $this->notSupported();
            }
        }
        catch (Error $e) {
            $strErrorDesc = $e->getMessage();
        }
        if (!$strErrorDesc) {
            $this->ok($rsp);
        } else {
            $this->serverError($strErrorDesc);
        }
    }

    protected function login($uModel, $email, $password)
    {
        $rsp = $uModel->getUserByEmail($email);
        if (!$rsp) {
            $this->notAuthorized('Неправильный адрес электронной почты или пароль');
        }
        if ($password && !password_verify($password, $rsp['password'])) {
            $this->notAuthorized('Неправильный адрес электронной почты или пароль');
        }
        if (!$rsp['isEnabled']) {
            $this->forbidden('Учетная запись не активна.');
        }

        unset($rsp['password']);
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('id' => $rsp['id'], 'orgId' => $rsp['orgId'], 'isEnabled' => $rsp['isEnabled'],
                         'isManager' => $rsp['isManager'], 'isAdmin' => $rsp['isAdmin'],
                         'exp' => (time() + JWT_EXPIRE));
        $jwt = generate_jwt($headers, $payload, JWT_SECRET);
        $rsp['token'] = $jwt;
        return $rsp;
    }
    public function execute($id, $method) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $userModel = new UserModel();
            $m = strtoupper($method);
            if ($id == 'login' && $m == 'POST') {
                $data = $this->getPostData();
                if (!isset($data['email']) || !isset($data['password'])) {
                    $this->missedParameter();
                }
                $rsp = $this->login($userModel, $data['email'], $data['password']);
            }
            elseif ($id == 'register' && $m == 'POST') {
                $linkModel = new LinkModel();
                $linkModel->flushLinks();
                $data = $this->getPostData();
                $data['isEnabled'] = false;
                $ursp = $userModel->addUser($data);
                if ($ursp['res'] < 1) {
                    $this->notAdded('Пользователь с таким адресом электронной почты уже зарегистрирован');
                }
                $headers = array('alg'=>'HS256','typ'=>'JWT');
                $payload = array('email' => $data['email'], 'type' => 'register', 'exp' => (time() + JWT_EXPIRE));
                $jwt = generate_jwt($headers, $payload, JWT_SECRET);
                $linkModel->addLink($jwt, $payload['exp']);
                $rsp = array('token'=> $jwt);
            }
            elseif ($id == 'recover' && $m == 'POST') {
                $linkModel = new LinkModel();
                $linkModel->flushLinks();
                $data = $this->getPostData();
                $user = $userModel->getUserByEmail($data['email']);
                if (!$user) {
                    $this->notFound('Пользователь с таким адресом электронной почты не зарегистрирован');
                }
                $headers = array('alg'=>'HS256','typ'=>'JWT');
                $payload = array('email' => $data['email'], 'type' => 'recover', 'exp' => (time() + JWT_EXPIRE));
                $jwt = generate_jwt($headers, $payload, JWT_SECRET);
                $linkModel->addLink($jwt, $payload['exp']);
                $rsp['token'] = $jwt;
            }
            else  {
                $this->notSupported();
            }
        }
        catch (Error $e) {
            $strErrorDesc = $e->getMessage();
        }
        if (!$strErrorDesc) {
            $this->ok($rsp);
        } else {
            $this->serverError($strErrorDesc);
        }
    }

}
?>

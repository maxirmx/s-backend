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
    public function check_auth() {
        $token = get_bearer_token();
        if (!$token) {
            $this->not_authorized('Необходимо войти в систему');
        }
        $user = is_jwt_valid($token, JWT_SECRET);
        if (!$user) {
            $this->not_authorized('Необходимо войти в систему');
        }
        if (!$user->isEnabled) {
            $this->forbidden('Учетная запись не активна.');
        }
        return $user;
    }

    protected function pre_process_token($op) {
        if ($op == 'register') {
            $g = 'для регистрации';
        }
        elseif ($op == 'recover') {
            $g = 'для восстановления пароля';
        }
        else {
            $g = '';
        }
        $data = $this->get_post_data();
        $jwt = $data['jwt'];
        if (!$jwt) {
            $this->not_found("Ссылка $g не найдена.");
        }
        $linkModel = new LinkModel();
        $res = $linkModel->delete_link($jwt);
        if ($res['res']<= 0) {
            $this->not_found("Ссылка $g не найдена. Вероятно, её уже использовали.");
        }
        $user = is_jwt_valid($jwt, JWT_SECRET);

        if (!$user || $user->type != $op) {
            $this->forbidden("Некорректная ссылка $g.");
        }
        if (!$user) {
            $m = "Время действия ссылки $g истекло.";
            if ($op == 'register') {
                $m .= " Ваша регистрация может быть завершена администратором.";
            }
            elseif ($op == 'recover') {
                $m .= " Попробуйте восстановить пароль ещё раз.";
            }
            $this->forbidden($m);
        }
        return $user;
    }

    protected function login($uModel, $email, $password)
    {
        $rsp = $uModel->get_user_by_email($email);
        if (!$rsp) {
            $this->not_authorized('Неправильный адрес электронной почты или пароль');
        }
        if ($password && !password_verify($password, $rsp['password'])) {
            $this->not_authorized('Неправильный адрес электронной почты или пароль');
        }
        if (!$rsp['isEnabled']) {
            $this->forbidden('Учетная запись не активна.');
        }

        unset($rsp['password']);
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('id' => $rsp['id'], 'orgs' => $rsp['orgs'], 'isEnabled' => $rsp['isEnabled'],
                         'isManager' => $rsp['isManager'], 'isAdmin' => $rsp['isAdmin'],
                         'exp' => (time() + JWT_EXPIRE));
        $jwt = generate_jwt($headers, $payload, JWT_SECRET);
        $rsp['token'] = $jwt;
        return $rsp;
    }

    protected function url_for_send_link($jwt, $host, $method) {
        if (!$host) {
            $host = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];
        }
        $url = $host.'/?'.$method.'='.$jwt;
        return $url;
    }
    protected function send_link($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . SERVICE_EMAIL . "\r\n";
        mail($to,$subject,$message,$headers);
    }

    public function execute($id, $method) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $userModel = new UserModel();
            $m = strtoupper($method);
            if ($id == 'login' && $m == 'POST') {
                $data = $this->get_post_data();
                if (!isset($data['email']) || !isset($data['password'])) {
                    $this->missed_parameter();
                }
                $rsp = $this->login($userModel, $data['email'], $data['password']);
            }
            elseif ($id == 'register' && $m == 'POST') {
                $linkModel = new LinkModel();
                $linkModel->flush_links();

                $data = $this->get_post_data();
                $data['isEnabled'] = false;
                $data['isManager'] = false;
                $data['isAdmin'] = false;
                $data['orgId'] = -1;

                $ursp = $userModel->add_user($data);
                if ($ursp['res'] < 1) {
                    $this->not_successful('Пользователь с таким адресом электронной почты уже зарегистрирован');
                }
                $headers = array('alg'=>'HS256','typ'=>'JWT');
                $payload = array('email' => $data['email'], 'type' => $id, 'exp' => (time() + JWT_EXPIRE));
                $jwt = generate_jwt($headers, $payload, JWT_SECRET);
                $linkModel->add_link($jwt, $payload['exp']);
                $rsp = array('res'=> 'ok');

                $url = $this->url_for_send_link($jwt, isset($data['host']) ? $data['host'] : null, $id);
                $subject = 'Регистрация в системе Track and Trace';
                $message = "Добрый день ! <br/><br/>
                Для завершения регистрации в системе Track and Trace перейдите
                по ссылке <a href='$url'>$url</a><br/>
                Обратите внимание, что ссылка действительна в течение 4 часов и является одноразовой.<br/>
                Если Вы не запрашивали регистрацию, просто проигнорируйте это письмо.<br/><br/>
                Спасибо, что Вы с нами !<br/>";

                $this->send_link($data['email'], $subject, $message);
            }
            elseif ($id == 'recover' && $m == 'POST') {
                $linkModel = new LinkModel();
                $linkModel->flush_links();
                $data = $this->get_post_data();
                $user = $userModel->get_user_by_email($data['email']);
                if (!$user) {
                    $this->not_found('Пользователь с таким адресом электронной почты не зарегистрирован');
                }
                $headers = array('alg'=>'HS256','typ'=>'JWT');
                $payload = array('email' => $user['email'], 'type' => $id, 'exp' => (time() + JWT_EXPIRE));
                $jwt = generate_jwt($headers, $payload, JWT_SECRET);
                $linkModel->add_link($jwt, $payload['exp']);
                $rsp = is_jwt_valid($jwt, JWT_SECRET); //array('res'=> 'ok');

                $url = $this->url_for_send_link($jwt, isset($data['host']) ? $data['host'] : null, $id);
                $subject = 'Восстановление пароля к системе Track and Trace';
                $message = "Добрый день ! <br/><br/>
                Для восстановления пароля к системе Track and Trace перейдите
                по ссылке <a href='$url'>$url</a><br/>
                Обратите внимание, что ссылка действительна в течение 4 часов и является одноразовой.<br/>
                Если Вы не запрашивали восстановления пароля, просто проигнорируйте это письмо.<br/><br/>
                Спасибо, что Вы с нами !<br/>";

                $this->send_link($data['email'], $subject, $message);
            }
            elseif ($id == 'register' && $m == 'PUT') {
                $user = $this->pre_process_token($id);
                $userModel = new UserModel();
                $userModel->enable_user_by_email($user->email);
                $rsp = $this->login($userModel, $user->email, null);
            }
            elseif ($id == 'recover' && $m == 'PUT') {
                $user = $this->pre_process_token($id);
                $userModel = new UserModel();
                $rsp = $this->login($userModel, $user->email, null);
            }
            elseif ($id == 'check' && $m == 'GET') {
                $rsp = $this->check_auth();
            }
            else  {
                $this->not_supported();
            }
        }
        catch (Error $e) {
            $strErrorDesc = $e->getMessage();
        }
        if (!$strErrorDesc) {
            $this->ok($rsp);
        } else {
            $this->server_error($strErrorDesc);
        }
    }

}
?>

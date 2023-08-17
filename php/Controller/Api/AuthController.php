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
require_once PROJECT_ROOT_PATH . "/Model/UserModel.php";
require_once PROJECT_ROOT_PATH . "/Controller/Libs/jwt.php";

class AuthController extends BaseController
{
    public function checkAuth() {
        $token = get_bearer_token();
        if (!$token) {
            $this->notAuthorized();
        }
        $user = is_jwt_valid($token, JWT_SECRET);
        if (!$user) {
            $this->notAuthorized();
        }
        if (!$user->isEnabled) {
            $this->forbidden();
        }
        return $user;
    }

    public function execute($id, $method) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $m = strtoupper($method);
            if ($id == 'login' && $m == 'POST') {
                $data = $this->getPostData();
                if (!isset($data['email']) || !isset($data['password'])) {
                    $this->missedParameter();
                }
                $userModel = new UserModel();
                $rsp = $userModel->getUserByEmail($data['email']);
                if (!$rsp) {
                    $this->notAuthorized();
                }
                if (!password_verify($data['password'], $rsp['password'])) {
                    $this->notAuthorized();
                }
                if (!$rsp['isEnabled']) {
                    $this->forbidden();
                }

                unset($rsp['password']);
                $headers = array('alg'=>'HS256','typ'=>'JWT');
                $payload = array('id' => $rsp['id'], 'isEnabled' => $rsp['isEnabled'], 'isManager' => $rsp['isManager'], 'isAdmin' => $rsp['isAdmin']);
                $payload['exp'] = (time() + 60*60*4);
                $jwt = generate_jwt($headers, $payload, JWT_SECRET);
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

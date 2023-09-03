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

class UserController extends BaseController
{
    public function execute($id, $method, $user) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $userModel = new UserModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $data = $this->getPostData();
                if (!$user->isAdmin) {
                   $data['isEnabled'] = false;
                   $data['isManager'] = false;
                   $data['isAdmin'] = false;
                   $data['orgId'] = -1;
                }
                $this->checkParams($data, ['email', 'lastName', 'firstName', 'password', "isEnabled", "isManager", "isAdmin", "orgId"]);

                $rsp = $userModel->addUser($data);
                if ($rsp['res'] < 1) {
                    $this->notSuccessful('Пользователь с таким адресом электронной почты уже зарегистрирован');
                }
            }
            elseif ($id == null && $method == 'GET') {
                $this->fenceAdmin($user);
                $rsp = $userModel->getUsers();
            }
            elseif ($m == 'GET') {
                $this->fenceAdminOrSameUser($id, $user);
                $rsp = $userModel->getUser($id);
            }
            elseif ($m == 'PUT') {
                if ($id==0) {
                    $this->forbidden('Настройки этого пользователя нельзя изменить');
                }
                $this->fenceAdminOrSameUser($id, $user);
		        $data = $this->getPostData();
		        $usr = $userModel->getUserByEmail($data['email']);
                if ($usr && $usr['id'] != $id) {
                    $this->notSuccessful('Пользователь с таким адресом электронной почты уже зарегистрирован');
                }
                $rsp = $userModel->updateUser($id, $data, $user->isAdmin);
            }
            elseif ($m == 'DELETE') {
                $this->fenceAdmin($user);
                if ($id==0) {
                    $this->forbidden('Этого пользователя нельзя удалить');
                }
                $rsp = $userModel->deleteUser($id);
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

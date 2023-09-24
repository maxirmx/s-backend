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
require_once PROJECT_ROOT_PATH . "/Model/OrgModel.php";

class UserController extends BaseController
{
    protected function check_user_exists($userModel, $email, $id = null) {
        $usr = $userModel->get_user_by_email($email);
        if ($usr && $usr['id'] != $id) {
            $this->not_successful('Пользователь с таким адресом электронной почты уже зарегистрирован');
        }
    }

    public function execute($id, $method, $user) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $userModel = new UserModel();
            $orgModel = new OrgModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $data = $this->get_post_data();
                if (!$user->isAdmin) {
                   $data['isEnabled'] = false;
                   $data['isManager'] = false;
                   $data['isAdmin'] = false;
                   $data['orgs'] = [ ];
                }
                $this->check_params($data, ['email', 'lastName', 'firstName', 'password', "isEnabled", "isManager", "isAdmin", "orgs"]);
                $this->check_user_exists($userModel, $data['email']);
                $userModel->start_transaction();
                try {
                    $rsp = $userModel->add_user($data);
                    if ($rsp['res'] < 1) {
                        $this->not_successful('Не удалось добавить пользователя');
                    }
                    $rsp_o = $orgModel->insert_user_org_mappings($orgModel->last_insert_id(), $data['orgs']);
                    if ($rsp_o['res'] != count($data['orgs'])) {
                        $this->not_successful('Не удалось связать пользователя с организациями');
                    }
                    $rsp['res_o'] = $rsp_o['res'];
                }
                catch (Error $e) {
                    $userModel->rollback_transaction();
                    throw $e;
                }
                $userModel->commit_transaction();
            }
            elseif ($id == null && $method == 'GET') {
                $this->fence_admin($user);
                $rsp = $userModel->get_users();
            }
            elseif ($m == 'GET') {
                $this->fence_admin_or_same_user($id, $user);
                $rsp = $userModel->get_user($id);
            }
            elseif ($m == 'PUT') {
                if ($id==0) {
                    $this->forbidden('Настройки этого пользователя нельзя изменить');
                }
                $this->fence_admin_or_same_user($id, $user);
		        $data = $this->get_post_data();
                $this->check_params($data, ['email', 'lastName', 'firstName', 'isEnabled', 'isManager', 'isAdmin', 'orgs']);
                $this->check_user_exists($userModel, $data['email'], $id);
                $userModel->start_transaction();
                try {
                    $rsp = $userModel->update_user($id, $data, $user->isAdmin);
                    $orgModel->delete_user_org_mappings($id);
                    $rsp_o = $orgModel->insert_user_org_mappings($id, $data['orgs']);
                    if ($rsp_o['res'] != count($data['orgs'])) {
                        $this->not_successful('Не удалось связать пользователя с организациями');
                    }
                    $rsp['res_o'] = $rsp_o['res'];
                }
                catch (Error $e) {
                    $userModel->rollback_transaction();
                    throw $e;
                }
                $userModel->commit_transaction();
            }
            elseif ($m == 'DELETE') {
                $this->fence_admin($user);
                if ($id==0) {
                    $this->forbidden('Этого пользователя нельзя удалить');
                }
                $rsp = $userModel->delete_user($id);
                $orgModel->delete_user_org_mappings($id);
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

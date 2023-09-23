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
require_once PROJECT_ROOT_PATH . "/Model/OrgModel.php";

class OrgController extends BaseController
{
    protected function checkOrgExists($orgModel, $name, $id = null) {
        $org = $orgModel->get_org_by_name($name);
        if ($org && $id && $org['id'] != $id) {
            $this->not_successful('Организация с таким названием уже зарегистрирована');
        }
    }

    public function execute($id, $method, $user) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $orgModel = new OrgModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $this->fence_admin($user);
                $data = $this->get_post_data();
                $this->check_params($data, ['name']);
                $this->checkOrgExists($orgModel, $data['name']);
                $rsp = $orgModel->add_org($data);
                if ($rsp['res'] < 1) {
                    $this->not_successful('Не удалось добавить организацию');
                }
            }
            elseif ($id == null && $m == 'GET') {
                if ($user->isManager || $user->isAdmin) {
                    $rsp = $orgModel->get_orgs();
                }
                else {
                    $rsp = $orgModel->get_orgs_by_user_id($user->id);
                }
            }
            elseif ($m == 'GET') {
                $this->fence_manager_or_admin_or_same_org($id, $user);
                $rsp = $orgModel->get_org($id);
            }
            elseif ($m == 'PUT' && $id != null) {
                $this->fence_admin($user);
                $data = $this->get_post_data();
                $this->check_params($data, ['name']);
                $this->checkOrgExists($orgModel, $data['name'], $id);
                $rsp = $orgModel->update_org($id, $data);
            }
            elseif ($m == 'DELETE' && $id != null) {
                $this->fence_admin($user);
                $rsp = $orgModel->get_org($id);
                if (!$rsp) {
                    $this->not_successful('Не удалось удалить организацию');
                }
                if ($rsp['num_users'] > 0) {
                    $this->not_successful('Невозможно удалить организацию, если с ней связаны пользователи');
                }
                if ($rsp['num_shipments'] > 0 || $rsp['num_archieved'] > 0) {
                    $this->not_successful('Невозможно удалить организацию, если с ней связаны отправления');
                }
                $rsp = $orgModel->delete_org($id);
                if ($rsp['res'] < 1) {
                    $this->not_successful('Не удалось добавить организацию');
                }
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

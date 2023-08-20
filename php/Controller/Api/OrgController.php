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
    public function execute($id, $method, $user) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $orgModel = new OrgModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $this->fenceAdmin($user);
                $rsp = $orgModel->addOrg($this->getPostData());
                if ($rsp['res'] < 1) {
                    $this->notAdded('Организация с таким названием уже зарегистрирована');
                }
            }
            elseif ($id == null && $method == 'GET') {
                $this->fenceManager($user);
                $rsp = $orgModel->getOrgs();
            }
            elseif ($m == 'GET') {
                $rsp = $orgModel->getOrg($id);
            }
            elseif ($m == 'PUT') {
                $this->fenceAdmin($user);
                $rsp = $orgModel->updateOrg($id, $this->getPostData());
                if ($rsp['res'] < 1) {
                    $this->notAdded('Организация с таким названием уже зарегистрирована');
                }
            }
            elseif ($m == 'DELETE') {
                $this->fenceAdmin($user);
                $rsp = $orgModel->deleteOrg($id);
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

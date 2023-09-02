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
require_once PROJECT_ROOT_PATH . "/Model/StatusModel.php";
require_once PROJECT_ROOT_PATH . "/Model/ShipmentModel.php";

class StatusController extends BaseController
{
    protected $dh = false;
    public function deliverHistory() {
        $this->dh = true;
    }
    public function execute($id, $method, $user) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $statusModel = new StatusModel();
            $shipmentModel = new ShipmentModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $this->fenceManager($user);
                $data = $this->getPostData();
                $rsp = $statusModel->addStatus($data);
                $shipmentModel->updateDDate($data);
            }
            elseif ($id != null && $method == 'GET') {
                if ($this->dh) {
                    $rsp = $statusModel->getStatusesByShipmentId($id);
                    $usr = $shipmentModel->getUserByShipmentId($id);
                }
                else {
                    $rsp = $statusModel->getStatus($id);
                    if ($rsp) {
                        $usr = $shipmentModel->getUserByShipmentId($rsp['shipmentNumber']);
                    }
                }
                if (!$usr) {
                    $this->notFound('Отправление с таким номером не зарегистрировано.');
                }
                /*
                    Если инициатор запроса - менеджер, он может видеть всё.
                    Иначе - только свои отправления и отправления своей организации.
                */
                if (!$user->isManager && !$this->checkUser($usr, $user) && !$this->checkOrg($usr, $user)) {
                    $this->forbidden('Недостаточно прав для выполнения операции.');
                }
            }
            elseif ($id != null && $method == 'PUT') {
                $this->fenceManager($user);
                $data = $this->getPostData();
                $rsp = $statusModel->updateStatus($id, $data);
                $shipmentModel->updateDDate($data);
            }
            elseif ($method == 'DELETE') {
                $this->fenceAdmin($user);
                if ($id == null) {
                    $this->notFound('Не указан статус для удаления.');
                }
                $rsp = $statusModel->deleteStatus($id);
                if ($rsp['res'] < 1) {
                    $this->notDeleted('Не удалось удалить статус.');
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
}
?>

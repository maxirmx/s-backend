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
require_once PROJECT_ROOT_PATH . "/Model/ShipmentModel.php";
require_once PROJECT_ROOT_PATH . "/Model/StatusModel.php";

class ShipmentController extends BaseController
{
    public function execute($id, $method, $user) {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $shipmentModel = new ShipmentModel();
            $statusModel = new StatusModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $this->fenceManager($user);
                $data = $this->getPostData();
                $rsp = $shipmentModel->addShipment($data);
                if ($rsp['res'] < 1) {
                    $this->notAdded('Отправление с таким номером уже зарегистрировано.');
                }
                $rsp = $statusModel->addInitialStatus($data);
                if ($rsp['res'] < 1) {
                    $this->notAdded('Не удалось зарегистрировать начальный статус для отправления.');
                }
            }
            elseif ($method == 'GET') {
                if ($id == null) {
                    if ($user->isManager) {
                    $rsp = $shipmentModel->getAllShipments();
                    }
                    else {
                        $rsp = $shipmentModel->getFilteredShipments($user->id, $user->orgId);
                    }
                }
                else {
                    $rsp = $shipmentModel->getShipmentByNumber($id);
                    if (!$rsp) {
                        $this->notFound('Отправление с таким номером не найдено.');
                    }

                    if (!$user->isManager && !$this->checkUser($rsp, $user) && !$this->checkOrg($rsp, $user)) {
                        $this->forbidden('Недостаточно прав для выполнения операции.');
                    }

                    unset($rsp['userId']);
                    unset($rsp['orgId']);
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
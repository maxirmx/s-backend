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
                $this->checkParams($data, ['number', 'ddate', 'dest', 'status', 'date', 'location', 'orgId']);
                $rsp = $shipmentModel->addShipment($data);
                if ($rsp['res'] < 1) {
                    $this->notSuccessful('Отправление с таким номером уже зарегистрировано.');
                }
		        $data['shipmentId'] = $rsp['ref'];
                $rsp2 = $statusModel->addStatus($data);
                if ($rsp2['res'] < 1) {
                    $this->notSuccessful('Не удалось зарегистрировать начальный статус для отправления.');
                }
                $rsp['ref2'] = $rsp2['ref'];
            }
            elseif ($method == 'GET') {
                if ($id == null) {
                    if ($user->isManager) {
                        $rsp = $shipmentModel->getAllShipments();
                    }
                    else {
                        $rsp = $shipmentModel->getFilteredShipments($user->orgId);
                    }
                }
                else {
                    $rsp = $shipmentModel->getShipmentEnriched($id);
                    if (!$rsp) {
                        $this->notFound('Информация об отправлении не найдена.');
                    }

                    if (!$user->isManager && !$this->checkUser($rsp, $user) && !$this->checkOrg($rsp, $user)) {
                        $this->forbidden('Недостаточно прав для выполнения операции.');
                    }

                    unset($rsp['userId']);
                    unset($rsp['orgId']);
                }
            }
            elseif ($method == 'PUT' && $id != null) {
                $this->fenceAdmin($user);
                $data = $this->getPostData();
                $this->checkParams($data, ['number', 'ddate', 'dest', 'orgId']);
                $rsp = $shipmentModel->updateShipment($id, $data);
                if ($rsp['res'] < 1) {
                    $this->notSuccessful('Не удалось изменить отправление.');
                }
            }
            elseif ($method == 'DELETE'  && $id != null) {
                $this->fenceAdmin($user);
                $shipmentModel->startTransaction();
                $rsp = $shipmentModel->deleteShipment($id);
                if ($rsp['res'] < 1) {
                    $shipmentModel->rollbackTransaction();
                    $this->notSuccessful('Не удалось удалить отправление.');
                }
                $statusModel->deleteStatusesByShipmentId($id);
                $shipmentModel->commitTransaction();
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

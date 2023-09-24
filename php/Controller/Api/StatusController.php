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
        $org = null;
        $strErrorDesc = null;
        try {
            $statusModel = new StatusModel();
            $shipmentModel = new ShipmentModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $this->fence_manager($user);
                $data = $this->get_post_data();
                $this->check_params($data, ['shipmentId', 'status', 'date', 'location', 'dest', 'ddate']);
                $statusModel->start_transaction();
                $rsp = $shipmentModel->get_shipment($data['shipmentId']);
                if (!$rsp) {
                    $statusModel->rollback_transaction();
                    $this->not_found('Отправление не найдено.');
                }
                $rsp = $statusModel->add_status($data);
                if ($rsp['res'] < 1) {
                    $statusModel->rollback_transaction();
                    $this->not_successful('Не удалось добавить статус.');
                }
                $shipmentModel->update_delivery_date($data);
                $statusModel->commit_transaction();
            }
            elseif ($id != null && $method == 'GET') {
                if ($this->dh) {
                    $rsp = $statusModel->get_statuses_by_shipment_id($id);
                    $org = $shipmentModel->get_org_by_shipment_id($id);
                }
                else {
                    $rsp = $statusModel->get_status($id);
                    if ($rsp) {
                        $org = $shipmentModel->get_org_by_shipment_id($rsp['shipmentId']);
                    }
                }
                if (!$org) {
                    $this->not_found('Отправление не найдено.');
                }
                /*
                    Если инициатор запроса - менеджер, он может видеть всё.
                    Иначе - только отправления своей организации.
                */
                if (!$user->isManager && !$this->check_org($org, $user)) {
                    $this->forbidden('Недостаточно прав для выполнения операции.');
                }
            }
            elseif ($id != null && $method == 'PUT') {
                $this->fence_manager($user);
                $data = $this->get_post_data();
                $this->check_params($data, ['status', 'date', 'location', 'dest', 'ddate']);
                $statusModel->start_transaction();
                $rsp = $shipmentModel->get_shipment_id_by_status_id($id);
                if (!$rsp) {
                    $statusModel->rollback_transaction();
                    $this->not_found('Отправление не найдено.');
                }
                $data['shipmentId'] = $rsp['shipmentId'];
                $rsp = $statusModel->update_status($id, $data);
                $shipmentModel->update_delivery_date($data);
                $statusModel->commit_transaction();
            }
            elseif ($id != null && $method == 'DELETE') {
                $this->fence_admin($user);
                $rsp = $statusModel->delete_status($id);
                if ($rsp['res'] < 1) {
                    $this->not_successful('Не удалось удалить статус.');
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

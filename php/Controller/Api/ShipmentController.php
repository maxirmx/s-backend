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
    public function execute($id, $method, $user)
    {
        $rsp = null;
        $strErrorDesc = null;
        try {
            $shipmentModel = new ShipmentModel();
            $shipmentModel->archieve();
            $statusModel = new StatusModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $this->fence_manager($user);
                $data = $this->get_post_data();
                $this->check_params($data, ['number', 'ddate', 'dest', 'status', 'date', 'location', 'orgId']);
                $data['isArchieved'] = false;
                $rsp = $shipmentModel->add_shipment($data);
                if ($rsp['res'] < 1) {
                    $this->not_successful('Отправление с таким номером уже зарегистрировано.');
                }
		        $data['shipmentId'] = $rsp['ref'];
                $rsp2 = $statusModel->add_status($data);
                if ($rsp2['res'] < 1) {
                    $this->not_successful('Не удалось зарегистрировать начальный статус для отправления.');
                }
                $rsp['ref2'] = $rsp2['ref'];
            }
            elseif ($method == 'GET') {
                if ($id == null) {
                    if ($user->isManager) {
                        $rsp = $shipmentModel->get_all_shipments(false);
                    }
                    else {
                        $rsp = $shipmentModel->get_filtered_shipments($user->orgs, false);
                    }
                }
                elseif ($id == 'archieve') {
                    if ($user->isManager) {
                        $rsp = $shipmentModel->get_all_shipments(true);
                    }
                    else {
                        $rsp = $shipmentModel->get_filtered_shipments($user->orgs, true);
                    }
                }
                else {
                    $rsp = $shipmentModel->get_shipment_enriched($id);
                    if (!$rsp) {
                        $this->not_found('Информация об отправлении не найдена.');
                    }

                    if (!$user->isManager  && !$this->check_org($rsp, $user)) {
                        $this->forbidden('Недостаточно прав для выполнения операции.');
                    }

                }
            }
            elseif ($method == 'PUT' && $id != null) {
                $this->fence_admin($user);
                $data = $this->get_post_data();
                $this->check_params($data, ['number', 'ddate', 'dest', 'orgId']);
                $rsp = $shipmentModel->update_shipment($id, $data);
            }
            elseif ($method == 'DELETE'  && $id != null) {
                $this->fence_admin($user);
                $shipmentModel->start_transaction();
                $rsp = $shipmentModel->delete_shipment($id);
                if ($rsp['res'] < 1) {
                    $shipmentModel->rollback_transaction();
                    $this->not_successful('Не удалось удалить отправление.');
                }
                $statusModel->delete_statuses_by_shipment_id($id);
                $shipmentModel->commit_transaction();
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

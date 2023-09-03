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

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class ShipmentModel extends Database
{
    protected const SHIPMENT_REQ =
    '   SELECT shipments.id, shipments.number, shipments.dest, shipments.ddate,
               organizations.id AS orgId, organizations.name, 
               most_recent_status.status, most_recent_status.id AS statusId,
               first_status.location AS origin
        FROM shipments
        LEFT JOIN organizations ON organizations.id = shipments.orgId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND a.id < b.id
            WHERE b.shipmentId IS NULL)
        AS most_recent_status
        ON shipments.id = most_recent_status.shipmentId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND a.id > b.id
            WHERE b.shipmentId IS NULL)
        AS first_status
        ON shipments.id = first_status.shipmentId
        WHERE shipments.id = ?
    ';

    protected const ALL_SHIPMENTS_REQ =
    '   SELECT  shipments.id, shipments.number, shipments.dest, shipments.ddate,
                shipments.orgId, organizations.name,
                most_recent_status.date, most_recent_status.location,
                most_recent_status.status,
                first_status.location AS origin
        FROM shipments
        LEFT JOIN organizations ON organizations.id = shipments.orgId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND a.id < b.id
            WHERE b.shipmentId IS NULL)
        AS most_recent_status
        ON shipments.id = most_recent_status.shipmentId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND a.id > b.id
            WHERE b.shipmentId IS NULL)
        AS first_status
        ON shipments.id = first_status.shipmentId
        ORDER BY shipments.id ASC
    ';

    protected const FILTERED_SHIPMENTS_REQ =
    '   SELECT shipments.id, shipments.number, shipments.dest, shipments.ddate,
               shipments.orgId, organizations.name,
               most_recent_status.date, most_recent_status.location,
               most_recent_status.status,
               first_status.location AS origin
        FROM shipments
        LEFT JOIN organizations ON organizations.id = shipments.orgId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND a.id < b.id
            WHERE b.shipmentId IS NULL)
        AS most_recent_status
        ON shipments.id = most_recent_status.shipmentId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND a.id > b.id
            WHERE b.shipmentId IS NULL)
        AS first_status
        ON shipments.id = first_status.shipmentId
        WHERE shipments.orgId = ?
        ORDER BY shipments.id ASC
    ';

    public function getFilteredShipments($orgId)
    {
        return $this->select(ShipmentModel::FILTERED_SHIPMENTS_REQ, 'i', array($orgId));
    }

    public function getAllShipments()
    {
        return $this->select(ShipmentModel::ALL_SHIPMENTS_REQ);
    }

    public function getShipments()
    {
        return $this->select("SELECT * FROM shipments ORDER BY id DESC");
    }

    protected function fortifyRef($data, $name)
    {
        return  isset($data[$name]) && $data[$name] != -1 ? $data[$name] : -2;
    }

    public function addShipment($data)
    {
        $res = $this->execute("INSERT INTO shipments (number, dest, ddate, userId, orgId) VALUES (?, ?, ?, ?, ?)", 'sssii',
                    array($data['number'], $data['dest'], $data['ddate'],
                        $this->fortifyRef($data, 'userId'),
                        $this->fortifyRef($data, 'orgId')));
        return array("res" => $res, "ref" => $this->lastInsertId());
    }

    public function getShipment($id)
    {
        $result = $this->select("SELECT * FROM shipments WHERE id = ?", 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function getShipmentIdByStatusId($statusId)
    {
        $result = $this->select("SELECT statuses.shipmentId FROM statuses WHERE statuses.id = ?", 's', array($statusId));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function getShipmentEnriched($id)
    {
        $result = $this->select(ShipmentModel::SHIPMENT_REQ, 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function getUserByShipmentId($id)
    {
        $result = $this->select('SELECT shipments.userId, shipments.orgId FROM shipments WHERE shipments.id = ?', 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function updateShipment($id, $data)
    {
        $res = $this->execute("UPDATE shipments SET number = ?, dest = ?, ddate = ?, userId = ?, orgId = ? WHERE id = ?", 'sssiii',
                            array($data['number'], $data['dest'], $data['ddate'],
                               $this->fortifyRef($data, 'userId'),
                               $this->fortifyRef($data, 'orgId'), $id)) ;
        return array("res" => $res );
    }

    public function updateDDate($data)
    {
        $res = $this->execute("UPDATE shipments SET ddate = ? WHERE id = ?" , 'si',
                               array($data['ddate'], $data['shipmentId']));
        return array("res" => $res );
    }

    public function deleteShipment($id)
    {
        $res = $this->execute("DELETE FROM shipments WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }
}
?>

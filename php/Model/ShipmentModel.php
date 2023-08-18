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
    protected const ALL_SHIPMENTS_REQ =
    '   SELECT  shipments.id, shipments.number, shipments.ddate, shipments.dest,
                most_recent_status.date, most_recent_status.location,
                most_recent_status.status, most_recent_status.comment
        FROM shipments
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentNumber = b.shipmentNumber AND a.id < b.id
            WHERE b.shipmentNumber IS NULL)
        AS most_recent_status
        ON shipments.number = most_recent_status.shipmentNumber
    ';

    protected const FILTERED_SHIPMENTS_REQ =
    '   SELECT shipments.id, shipments.number, shipments.ddate, shipments.dest,
               most_recent_status.date, most_recent_status.location,
               most_recent_status.status, most_recent_status.comment
        FROM shipments
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentNumber = b.shipmentNumber AND a.id < b.id
            WHERE b.shipmentNumber IS NULL)
        AS most_recent_status
        ON shipments.number = most_recent_status.shipmentNumber
        WHERE shipments.userId = ? OR shipments.orgId = ?
    ';

    public function getFilteredShipments($userId, $orgId)
    {
        return $this->select(ShipmentModel::FILTERED_SHIPMENTS_REQ, 'ii', array($userId, $orgId));
    }

    public function getAllShipments()
    {
        return $this->select(ShipmentModel::ALL_SHIPMENTS_REQ);
    }

    public function getShipments()
    {
        return $this->select("SELECT * FROM shipments ORDER BY id ASC");
    }

    public function addShipment($data)
    {
        $orgId = isset($data['orgId']) ? $data['orgId'] : -2;
        if ($orgId == -1) {
            $orgId = -2;
        }
        $userId = isset($data['userId']) ? $data['userId'] : -2;
        if ($userId == -1) {
            $userId = -2;
        }
        $res = $this->execute("INSERT INTO shipments (number, dest, ddate, userId, orgId) VALUES (?, ?, ?, ?, ?)", 'sssii',
               array($data['number'], $data['dest'], $data['ddate'], $userId, $orgId));
        return array("res" => $res );
    }
    public function getShipment($id)
    {
        $result = $this->select("SELECT * FROM shipments WHERE id = ?", 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }
    public function updateShipment($id, $data)
    {
        $orgId = isset($data['orgId']) ? $data['orgId'] : -2;
        $userId = isset($data['userId']) ? $data['userId'] : -2;
        $res = $this->execute("UPDATE shipments SET number = ?, dest = ?, ddate = ?, userId = ?, orgId = ? WHERE id = ?", 'sssiii',
        array($data['number'], $data['dest'], $data['ddate'], $userId, $orgId, $id));
        return array("res" => $res );
    }
    public function deleteShipment($id)
    {
        $res = $this->execute("DELETE FROM shipments WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }
}

/* SELECT shipments.id, shipments.number, shipments.ddate, shipments.dest, shipments.userId, statuses.date, statuses.location, statuses.status, statuses.comment
FROM shipments
LEFT JOIN statuses ON shipments.number = statuses.shipmentNumber
ORDER BY statuses.id DESC LIMIT 1


SELECT shipments.id, shipments.number, shipments.ddate, shipments.dest, shipments.userId, statuses.date, statuses.location, statuses.status, statuses.comment, users.orgId
FROM shipments
LEFT JOIN statuses ON shipments.number = statuses.shipmentNumber
LEFT JOIN users ON shipments.userId = users.id
ORDER BY statuses.id DESC LIMIT 1


SELECT shipments.id, shipments.number, shipments.ddate, shipments.dest, shipments.userId, shipments.orgId, most_recent_status.date, most_recent_status.location, most_recent_status.status, most_recent_status.comment
FROM shipments
LEFT JOIN (
SELECT a.*
FROM statuses a
LEFT OUTER JOIN statuses b
    ON a.shipmentNumber = b.shipmentNumber AND a.id < b.id
WHERE b.shipmentNumber IS NULL) AS most_recent_status
ON shipments.number = most_recent_status.shipmentNumber

*/
?>

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
    public function getShipments()
    {
        return $this->select("SELECT * FROM shipments ORDER BY id ASC");
    }

// eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTEsImlzRW5hYmxlZCI6MSwiaXNNYW5hZ2VyIjoxLCJpc0FkbWluIjoxLCJleHAiOjE2OTIzNTcyMjl9.nBuUufbkAgAlKmFMRPu_ySVipsEty9gqs63LomPXHYw
// curl --header "Content-Type: application/json" --verbose -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTEsImlzRW5hYmxlZCI6MSwiaXNNYW5hZ2VyIjoxLCJpc0FkbWluIjoxLCJleHAiOjE2OTIzNTcyMjl9.nBuUufbkAgAlKmFMRPu_ySVipsEty9gqs63LomPXHYw" --data '{"number": "2234A1", "location":"Тикси, РФ", "ddate":"2023-08-14", "dest":"Hanga Roa, CL"}' --request POST https://tracker.sw.consulting/backend/shipments/add
// curl --header "Content-Type: application/json" --verbose -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTEsImlzRW5hYmxlZCI6MSwiaXNNYW5hZ2VyIjoxLCJpc0FkbWluIjoxLCJleHAiOjE2OTIzNTcyMjl9.nBuUufbkAgAlKmFMRPu_ySVipsEty9gqs63LomPXHYw" --data '{"status":6, "shipmentNumber": "2234A1", "location":"Лесосибирск, РФ", "date":"2023-07-15", "comment":"В Лесосибирске было страшно"}' --request POST https://tracker.sw.consulting/backend/statuses/add
// curl --header "Content-Type: application/json" --verbose -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTEsImlzRW5hYmxlZCI6MSwiaXNNYW5hZ2VyIjoxLCJpc0FkbWluIjoxLCJleHAiOjE2OTIzNTcyMjl9.nBuUufbkAgAlKmFMRPu_ySVipsEty9gqs63LomPXHYw" --data '{"number": "2274A4", "location":"Malmo, SE", "ddate":"2023-07-15", "date":"2023-06-22", "dest":"Лесосибирск, РФ", "comment":"Мальмё - дыра дырой"}' --request POST https://tracker.sw.consulting/backend/shipments/add
public function addShipment($data)
    {
        $orgId = isset($data['orgId']) ? $data['orgId'] : -2;
        $userId = isset($data['userId']) ? $data['userId'] : -2;
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
*/
?>

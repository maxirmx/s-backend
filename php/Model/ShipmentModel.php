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
    '   SELECT shipments.id, shipments.number, shipments.dest, shipments.ddate, shipments.isArchieved,
               organizations.id AS orgId, organizations.name,
               most_recent_status.status, most_recent_status.id AS statusId,
               first_status.location AS origin
        FROM shipments
        LEFT JOIN organizations ON organizations.id = shipments.orgId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND ( a.date < b.date OR ( a.date = b.date AND a.id < b.id ) )
            WHERE b.shipmentId IS NULL)
        AS most_recent_status
        ON shipments.id = most_recent_status.shipmentId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND ( a.date > b.date OR ( a.date = b.date AND a.id > b.id ) )
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
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND ( a.date < b.date OR ( a.date = b.date AND a.id < b.id ) )
            WHERE b.shipmentId IS NULL)
        AS most_recent_status
        ON shipments.id = most_recent_status.shipmentId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND ( a.date > b.date OR ( a.date = b.date AND a.id > b.id ) )
            WHERE b.shipmentId IS NULL)
        AS first_status
        ON shipments.id = first_status.shipmentId
        WHERE shipments.isArchieved = ?
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
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND ( a.date < b.date OR ( a.date = b.date AND a.id < b.id ) )
            WHERE b.shipmentId IS NULL)
        AS most_recent_status
        ON shipments.id = most_recent_status.shipmentId
        LEFT JOIN (
            SELECT a.*
            FROM statuses a
            LEFT OUTER JOIN statuses b ON a.shipmentId = b.shipmentId AND ( a.date > b.date OR ( a.date = b.date AND a.id > b.id ) )
            WHERE b.shipmentId IS NULL)
        AS first_status
        ON shipments.id = first_status.shipmentId
        WHERE shipments.isArchieved = ? AND shipments.orgId IN
    ';

    protected const ARCHIEVE_REQ =
    '   UPDATE `shipments` SET `isArchieved` = 1
        WHERE  `isArchieved` = 0 AND
               `id` IN ( SELECT `shipmentId` FROM `statuses` WHERE `status` = ' . FINAL_STATUS . ' AND `date` < ( CURDATE() - INTERVAL ' . ARCHIEVE_THRESHOLD . ' DAY )
        )
    ';

    public function archieve()
    {
        $res = $this->execute(ShipmentModel::ARCHIEVE_REQ);
        return array("res" => $res );
    }

    public function get_filtered_shipments($orgs, $isArchieved = false)
    {
        if (count($orgs) ==0) {
            return [];
        }
        $q = ShipmentModel::FILTERED_SHIPMENTS_REQ;
        $d = '( ';
        foreach ($orgs as $org) {
            $q = $q . $d . $org->orgId;
            $d = ', ';
        }
        $q = $q . ' ) ORDER BY shipments.id ASC';
        return $this->select($q, 'i', array($isArchieved ? 1 : 0));
    }

    public function get_all_shipments($isArchieved = false)
    {
        return $this->select(ShipmentModel::ALL_SHIPMENTS_REQ, 'i', array($isArchieved ? 1 : 0));
    }

    public function get_shipments()
    {
        return $this->select("SELECT * FROM shipments ORDER BY id DESC");
    }

    protected function fortify_ref($data, $name)
    {
        return  isset($data[$name]) && $data[$name] != -1 ? $data[$name] : -2;
    }

    public function add_shipment($data)
    {
        $res = $this->execute("INSERT INTO shipments (`number`, `dest`, `ddate`, `orgId`, `isArchieved`) VALUES (?, ?, ?, ?, ?)", 'sssii',
                    array($data['number'], $data['dest'], $data['ddate'],
                        $this->fortify_ref($data, 'orgId'), $data['isArchieved']));
        return array("res" => $res, "ref" => $this->last_insert_id());
    }

    public function get_shipment($id)
    {
        $result = $this->select("SELECT * FROM shipments WHERE id = ?", 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function get_shipment_id_by_status_id($statusId)
    {
        $result = $this->select("SELECT statuses.shipmentId FROM statuses WHERE statuses.id = ?", 's', array($statusId));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function get_shipment_enriched($id)
    {
        $result = $this->select(ShipmentModel::SHIPMENT_REQ, 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function get_org_by_shipment_id($id)
    {
        $result = $this->select('SELECT orgId FROM shipments WHERE id = ?', 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }

    public function update_shipment($id, $data)
    {
        $res = $this->execute("UPDATE shipments SET number = ?, dest = ?, ddate = ?, orgId = ? WHERE id = ?", 'sssii',
                            array($data['number'], $data['dest'], $data['ddate'],
                               $this->fortify_ref($data, 'orgId'), $id)) ;
        return array("res" => $res );
    }

    public function update_delivery_date($data)
    {
        $res = $this->execute("UPDATE shipments SET ddate = ? WHERE id = ?" , 'si',
                               array($data['ddate'], $data['shipmentId']));
        return array("res" => $res );
    }

    public function delete_shipment($id)
    {
        $res = $this->execute("DELETE FROM shipments WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }
}
?>

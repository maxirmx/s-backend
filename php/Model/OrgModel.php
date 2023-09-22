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

class OrgModel extends Database
{
    protected const ORGS_REQ =
    '
        SELECT *,
                (SELECT COUNT(user_org_mappings.userId) FROM `user_org_mappings` WHERE user_org_mappings.orgId = organizations.id) as num_users,
                (SELECT COUNT(shipments.id) FROM `shipments` WHERE shipments.orgId = organizations.id) as num_shipments,
                (SELECT COUNT(shipments.id) FROM `shipments` WHERE shipments.orgId = organizations.id AND shipments.isArchieved = 1) as num_archieved
        FROM `organizations`
        ORDER BY organizations.id ASC
    ';

    protected const ORGS_BY_U_REQ =
    '
        SELECT *,
                (SELECT COUNT(user_org_mappings.userId) FROM `user_org_mappings` WHERE user_org_mappings.orgId = organizations.id) as num_users,
                (SELECT COUNT(shipments.id) FROM `shipments` WHERE shipments.orgId = organizations.id) as num_shipments,
                (SELECT COUNT(shipments.id) FROM `shipments` WHERE shipments.orgId = organizations.id AND shipments.isArchieved = 1) as num_archieved
        FROM `organizations`
        WHERE organizations.id IN (SELECT user_org_mappings.orgId FROM `user_org_mappings` WHERE user_org_mappings.userId = ?)
        ORDER BY organizations.id ASC
    ';

    protected const ORG_REQ =
    '
        SELECT *,
            (SELECT COUNT(users.id) FROM `users` WHERE users.orgId = organizations.id) as num_users,
            (SELECT COUNT(shipments.id) FROM `shipments` WHERE shipments.orgId = organizations.id AND shipments.isArchieved = 0) as num_shipments,
            (SELECT COUNT(shipments.id) FROM `shipments` WHERE shipments.orgId = organizations.id AND shipments.isArchieved = 1) as num_archieved
        FROM `organizations`
        WHERE organizations.id = ?
    ';

    public function getOrgs()
    {
        return $this->select(OrgModel::ORGS_REQ);
    }
    public function addOrg($data)
    {
        $res = $this->execute("INSERT INTO organizations (name) VALUES (?)", 's', array($data['name']));
        return array("res" => $res, "ref" => $this->lastInsertId());
    }
    public function getOrg($id)
    {
        $result = $this->select(OrgModel::ORG_REQ, 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }
    public function getOrgsByUserId($userId) {
        return $this->select(OrgModel::ORGS_BY_U_REQ, 'i', array($userId));
    }
    public function getOrgByName($name)
    {
        $result = $this->select("SELECT * FROM organizations WHERE organizations.name = ?", 's', array($name));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }
    public function updateOrg($id, $data)
    {
        $res = $this->execute("UPDATE organizations SET name = ? WHERE id = ?", 'si', array($data['name'], $id));
        return array("res" => $res );
    }
    public function deleteOrg($id)
    {
        $res = $this->execute("DELETE FROM organizations WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }
}
?>

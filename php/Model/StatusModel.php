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

class StatusModel extends Database
{
    public function getStatuses()
    {
        return $this->select("SELECT * FROM statuses ORDER BY id ASC");
    }

    public function getStatusesByNumber($number)
    {
        return $this->select("SELECT * FROM statuses WHERE shipmentNumber = ? ORDER BY id DESC", 's', array($number));
    }

    public function addStatus($data)
    {
        if (!isset($data['comment'])) {
            $data['comment'] = "";
        }
        $res = $this->execute("INSERT INTO statuses (shipmentNumber, status, date, location, comment) VALUES (?, ?, ?, ?, ?)", 'sisss',
                               array($data['shipmentNumber'], $data['status'], $data['date'], $data['location'], $data['comment']));
        return array("res" => $res );
    }
    public function addInitialStatus($data)
    {
        $data['shipmentNumber'] = $data['number'];
        return $this->addStatus($data);
    }
    public function getStatus($id)
    {
        $result = $this->select("SELECT * FROM statuses WHERE id = ?", 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }
    public function updateStatus($id, $data)
    {
        if (!isset($data['comment'])) {
            $data['comment'] = "";
        }
        $res = $this->execute("UPDATE statuses SET shipmentNumber = ?, status = ?, date = ?, location = ?, comment = ? WHERE id = ?", 'sisssi',
                               array($data['shipmentNumber'], $data['status'], $data['date'], $data['location'], $data['comment'], $id));
        return array("res" => $res );
    }
    public function deleteStatus($id)
    {
        $res = $this->execute("DELETE FROM statuses WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }
}
?>

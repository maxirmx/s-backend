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

class UserModel extends Database
{
    protected const FLDS = "id, email, lastName, firstName, patronimic, orgId, isEnabled, isManager, isAdmin";
    protected const FLDS_INS = "email, lastName, firstName, patronimic, orgId, isEnabled, isManager, isAdmin, password";
    protected const FLDS_UPDF = "email=?, lastName=?, firstName=?, patronimic=?, orgId=?, password=?";
    protected const FLDS_UPD = "email=?, lastName=?, firstName=?, patronimic=?, orgId=?";
    protected const FLDS_UPDFC = "email=?, lastName=?, firstName=?, patronimic=?, orgId=?, isEnabled=?, isManager=?, isAdmin=?, password=?";
    protected const FLDS_UPDC = "email=?, lastName=?, firstName=?, patronimic=?, orgId=?, isEnabled=?, isManager=?, isAdmin=?";

    public function getUsers()
    {
        return $this->select("SELECT " . UserModel::FLDS . " FROM users ORDER BY id ASC");
    }
    public function addUser($data)
    {
        $email = strtolower($data['email']);
        $orgId = isset($data['orgId']) ? $data['orgId'] : -1;
        $patronimic = isset($data['patronimic']) ? $data['patronimic'] : '';
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $res = $this->execute("INSERT INTO users (".UserModel::FLDS_INS.") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    'ssssiiiis',
                    array($email,  $data['lastName'],  $data['firstName'], $patronimic,
                          $orgId,  $data['isEnabled'], $data['isManager'], $data['isAdmin'],
                          $password)
                );
        return array("res" => $res, "ref" => $this->lastInsertId());
    }
    public function getUser($id)
    {
        $result = $this->select("SELECT " . UserModel::FLDS . " FROM users WHERE id = ?", 'i', array($id));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }
    public function getUserByEmail($email)
    {
        $result = $this->select("SELECT * FROM users WHERE email = ?", 's', array(strtolower($email)));
        return !is_null($result) && count($result) > 0 ? $result[0] : null;
    }
    public function updateUser($id, $data, $credentials = false)
    {
        $email = strtolower($data['email']);
        $orgId = isset($data['orgId']) ? $data['orgId'] : -1;
        $patronimic = isset($data['patronimic']) ? $data['patronimic'] : '';
        if (isset($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            if ($credentials) {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPDFC." WHERE id = ?",
                    'ssssiiiisi',
                    array($email,  $data['lastName'],  $data['firstName'], $patronimic,
                          $orgId,  $data['isEnabled'], $data['isManager'], $data['isAdmin'],
                          $password, $id)
                );
            }
            else {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPDF." WHERE id = ?",
                    'ssssisi',
                    array($email,  $data['lastName'],  $data['firstName'], $patronimic,
                          $orgId,  $password, $id)
                );
            }
        }
        else {
            if ($credentials) {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPDC." WHERE id = ?",
                    'ssssiiiii',
                    array($email,  $data['lastName'],  $data['firstName'], $patronimic,
                          $orgId,  $data['isEnabled'], $data['isManager'], $data['isAdmin'],
                          $id)
                );
            }
            else {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPD." WHERE id = ?",
                    'ssssii',
                    array($email,  $data['lastName'],  $data['firstName'], $patronimic,
                          $orgId,  $id)
                );
            }
        }
        return array("res" => $res );
    }
    public function deleteUser($id)
    {
        $res = $this->execute("DELETE FROM users WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }

    public function enableUserByEmail($email)
    {
        $res = $this->execute("UPDATE users SET isEnabled = 1 WHERE email = ?", 's', array($email));
        return array("res" => $res );
    }
}
?>

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
    protected const FLDS = "id, email, lastName, firstName, patronimic, isEnabled, isManager, isAdmin";
    protected const FLDS_INS = "email, lastName, firstName, patronimic, isEnabled, isManager, isAdmin, password";
    protected const FLDS_UPDF = "email=?, lastName=?, firstName=?, patronimic=?, password=?";
    protected const FLDS_UPD = "email=?, lastName=?, firstName=?, patronimic=?";
    protected const FLDS_UPDFC = "email=?, lastName=?, firstName=?, patronimic=?, isEnabled=?, isManager=?, isAdmin=?, password=?";
    protected const FLDS_UPDC = "email=?, lastName=?, firstName=?, patronimic=?,  isEnabled=?, isManager=?, isAdmin=?";

    protected function enrich_with_orgs($user) {
        if ($user) {
            $user['orgs'] = array();
            $orgs = $this->select("SELECT orgId FROM user_org_mappings WHERE userId = ?", 'i', array($user['id']));
            if (count($orgs) > 0) {
                foreach ($orgs as $org) {
                    $user['orgs'][] = $org;
                }
            }
            else {
                $user['orgs'][] = array("orgId" => -1);
            }
        }
        return $user;
    }
    public function get_users()
    {
        $users = $this->select("SELECT " . UserModel::FLDS . " FROM users ORDER BY id ASC");
        foreach ($users as &$user) {
            $user = $this->enrich_with_orgs($user);
        }
        return $users;
    }
    public function add_user($data)
    {
        $email = strtolower($data['email']);
        $patronimic = isset($data['patronimic']) ? $data['patronimic'] : '';
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $res = $this->execute("INSERT INTO users (".UserModel::FLDS_INS.") VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    'ssssiiis',
                    array($email,             $data['lastName'],  $data['firstName'], $patronimic,
                          $data['isEnabled'], $data['isManager'], $data['isAdmin'],   $password)
                );
        return array("res" => $res, "ref" => $this->last_insert_id());
    }
    public function get_user($id)
    {
        $result = $this->select("SELECT " . UserModel::FLDS . " FROM users WHERE id = ?", 'i', array($id));
        $res = !is_null($result) && count($result) > 0 ? $result[0] : null;
        return $this->enrich_with_orgs($res);
    }
    public function get_user_by_email($email)
    {
        $result = $this->select("SELECT * FROM users WHERE email = ?", 's', array(strtolower($email)));
        $res = !is_null($result) && count($result) > 0 ? $result[0] : null;
        return $this->enrich_with_orgs($res);
    }
    public function update_user($id, $data, $credentials = false)
    {
        $email = strtolower($data['email']);
        $patronimic = isset($data['patronimic']) ? $data['patronimic'] : '';
        if (isset($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            if ($credentials) {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPDFC." WHERE id = ?",
                    'ssssiiisi',
                    array($email,             $data['lastName'],  $data['firstName'], $patronimic,
                          $data['isEnabled'], $data['isManager'], $data['isAdmin'],   $password,    $id)
                );
            }
            else {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPDF." WHERE id = ?",
                    'sssssi',
                    array($email,    $data['lastName'],  $data['firstName'], $patronimic,
                          $password, $id)
                );
            }
        }
        else {
            if ($credentials) {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPDC." WHERE id = ?",
                    'ssssiiii',
                    array($email,             $data['lastName'],  $data['firstName'], $patronimic,
                          $data['isEnabled'], $data['isManager'], $data['isAdmin'],   $id)
                );
            }
            else {
                $res = $this->execute(
                    "UPDATE users SET ".UserModel::FLDS_UPD." WHERE id = ?",
                    'ssssi',
                    array($email,  $data['lastName'],  $data['firstName'], $patronimic, $id)
                );
            }
        }
        return array("res" => $res );
    }
    public function delete_user($id)
    {
        $res = $this->execute("DELETE FROM users WHERE id = ?", 'i', array($id));
        return array("res" => $res );
    }

    public function enable_user_by_email($email)
    {
        $res = $this->execute("UPDATE users SET isEnabled = 1 WHERE email = ?", 's', array($email));
        return array("res" => $res );
    }
}
?>

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

class Database
{
    protected static $connection = null;
    protected static $refs = 0;
    public function __construct()
    {
        try {
            if (self::$connection == null) {
                self::$connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE_NAME);
                if ( mysqli_connect_errno()) {
                    throw new Exception("Could not connect to database.");
                }
                self::$connection->set_charset("utf8mb4");
            }
            self::$refs++;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function select($query = "" , $types = "", $params = [])
    {
        try {
            $stmt = $this->executeStatement( $query , $types, $params );
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }
        return false;
    }
    public function execute($query = "" , $types = "", $params = [])
    {
        try {
            $stmt = $this->executeStatement( $query , $types, $params );
            $res = $stmt->affected_rows;
            $stmt->close();
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }
        return $res;
    }
    private function executeStatement($query = "" , $types= "", $params = [])
    {
        try {
            $stmt = self::$connection->prepare( $query );
            if($stmt === false) {
                throw New Exception("Unable to do prepared statement: " . $query);
            }
            if( $params ) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt;
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }
    }

    public function startTransaction()
    {
        self::$connection->begin_transaction();
    }

    public function commitTransaction()
    {
        self::$connection->commit();
    }

    public function rollbackTransaction()
    {
        self::$connection->rollback();
    }

    public function lastInsertId()
    {
	    return self::$connection->insert_id;
    }

    public function __destruct()
    {
        self::$refs--;
        if (self::$refs == 0 && self::$connection) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}
?>

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

class BaseController
{
    /**
* __call magic method.
*/
    public function __call($name, $arguments)
    {
        $this->not_found('Unknown method:' . $name);
    }
    /**
* Get URI elements.
*
* @return array
*/
    protected function get_uri_segments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode( '/', $uri );
        return $uri;
    }
    /**
* Get querystring params.
*
* @return array
*/
    protected function get_query_string_params()
    {
        return parse_str($_SERVER['QUERY_STRING'], $query);
    }
    /**
* Send API output.
*
* @param mixed $data
* @param string $httpHeader
*/
    protected function send_output($data, $httpHeaders=array())
    {
        header_remove('Set-Cookie');

        if (CORS_ALLOW) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

            if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
                header("HTTP/1.1 200 OK");
                exit(0);
            }
        }

        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        echo $data;
        exit;
    }

    protected function check_org($data, $user)
    {
        if (isset($data['orgId'])) {
            foreach ($user->orgs as $org) {
                if ($data['orgId'] == $org->orgId) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function fence_admin($user)
    {
        if (!$user->isAdmin) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function fence_manager_or_admin($user)
    {
        if (!$user->isManager && !$user->isAdmin) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function fence_manager_and_admin($user)
    {
        if (!$user->isManager || !$user->isAdmin) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function fence_manager($user)
    {
        if (!$user->isManager) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function fence_admin_or_same_org($orgId, $user)
    {
        if (!$user->isAdmin && !in_array($orgId, $user->orgs, true)) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function fence_manager_or_admin_or_same_org($orgId, $user)
    {
        if (!$user->isAdmin && !$user->isManager && !in_array($orgId, $user->orgs, true)) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function fence_admin_or_same_user($userId, $user)
    {
        if (!$user->isAdmin && $user->id != $userId) {
            $this->forbidden('Недостаточно прав для выполнения операции.');
        }
    }

    protected function get_post_data()
    {
        $data = file_get_contents('php://input');
        return json_decode($data, true);
    }

    public function not_authorized($msg)
    {
        $this->send_output(json_encode(array('message' => $msg)),
            array('Content-Type: application/json', 'HTTP/1.1 401 Unauthorized')
        );
    }

    public function forbidden($msg)
    {
        $this->send_output(json_encode(array('message' => $msg)),
            array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
    }

    protected function not_supported()
    {
        $this->send_output(json_encode(array('message' => 'Метод не поддерживается')),
            array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
    }

    protected function missed_parameter($param = null)
    {
        $this->send_output(json_encode(array('message' => 'Не задан необходимый параметр запроса' . ($param ? ': ' . $param : ''))),
            array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
    }

    protected function server_error($desc)
    {
        $this->send_output(json_encode(array('message' => $desc)),
        array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
        );
    }

    protected function ok($rsp)
    {
        $this->send_output(
            json_encode($rsp),
            array('Content-Type: application/json', 'HTTP/1.1 200 OK')
        );
    }

    protected function not_successful($msg)
    {
        $this->send_output(
            json_encode(array('message' => $msg)),
            array('Content-Type: application/json', 'HTTP/1.1 409 Conflict')
        );
    }

    protected function not_found($msg)
    {
        $this->send_output(
            json_encode(array('message' => $msg)),
            array('Content-Type: application/json', 'HTTP/1.1 404 Not Found')
        );
    }

    protected function check_params($data, $params)
    {
        foreach ($params as $param) {
            if (!isset($data[$param])) {
                $this->missed_parameter($param);
            }
          }
    }

}
?>

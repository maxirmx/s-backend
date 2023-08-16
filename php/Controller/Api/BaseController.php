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
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }
    /**
* Get URI elements.
*
* @return array
*/
    protected function getUriSegments()
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
    protected function getQueryStringParams()
    {
        return parse_str($_SERVER['QUERY_STRING'], $query);
    }
    /**
* Send API output.
*
* @param mixed $data
* @param string $httpHeader
*/
    protected function sendOutput($data, $httpHeaders=array())
    {
        header_remove('Set-Cookie');

        header('Access-Control-Allow-Headers: Origin, Content-Type, Content-Length, Accept, X-Auth-Token');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Request-Headers: Origin, X-Custom-Header, X-Requested-With, Authorization, Content-Type, Content-Length, Accept');
        header('Access-Control-Expose-Headers: Content-Length, X-Kuma-Revision');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');

        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        echo $data;
        exit;
    }

    protected function getPostData()
    {
        $data = file_get_contents('php://input');
        return json_decode($data, true);
    }

    protected function notSupported()
    {
        $this->sendOutput(json_encode(array('error' => 'Method not supported')),
            array('Content-Type: application/json', 'HTTP/1.1 422 Unprocessable Entity')
        );
    }

    protected function serverError($desc)
    {
        $this->sendOutput(json_encode(array('error' => $desc)),
        array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
        );
    }

    protected function ok($rsp)
    {
        $this->sendOutput(
            json_encode($rsp),
            array('Content-Type: application/json', 'HTTP/1.1 200 OK')
        );
    }
}
?>

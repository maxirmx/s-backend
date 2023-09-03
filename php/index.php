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

define("PROJECT_ROOT_PATH", __DIR__ );
require PROJECT_ROOT_PATH . "/inc/bootstrap.php";
require PROJECT_ROOT_PATH . "/Controller/Api/AuthController.php";

$auth = new AuthController();
try {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode( '/', $uri );

    if (!isset($uri[2])) {
        $auth->notFound('Не задан метод API');
    }

    $controller = null;
    $user = null;

    if ($uri[2] == 'auth') {
        $auth->execute(isset($uri[3]) ? $uri[3] : null, $_SERVER["REQUEST_METHOD"]);
    }
    else
    {
        $user = $auth->checkAuth();
        if ($uri[2] == 'orgs') {
            require PROJECT_ROOT_PATH . "/Controller/Api/OrgController.php";
            $controller = new OrgController();
        }
        elseif ($uri[2] == 'users') {
            require PROJECT_ROOT_PATH . "/Controller/Api/UserController.php";
            $controller = new UserController();
        }
        elseif ($uri[2] == 'shipments') {
            require PROJECT_ROOT_PATH . "/Controller/Api/ShipmentController.php";
            $controller = new ShipmentController();
        }
        elseif ($uri[2] == 'statuses' || $uri[2] == 'history') {
            require PROJECT_ROOT_PATH . "/Controller/Api/StatusController.php";
            $controller = new StatusController();
            if ($uri[2] == 'history') {
                $controller->deliverHistory();
            }
        }
        if (is_null($controller)) {
            $auth->notFound('Неизвестный метод API');
        }
        else {
            $controller->execute(isset($uri[3]) ? $uri[3] : null, $_SERVER["REQUEST_METHOD"], $user);
        }
    }

}
catch (Exception $e) {
    echo $e->getMessage();
}
echo 'Запрос не был обработан по непонятной причине.';
?>

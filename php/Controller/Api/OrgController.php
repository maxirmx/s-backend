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

class OrgController extends BaseController
{
    public function execute($id, $method) {
        $rsp = null;
        $strErrorDesc = null;
        $strErrorHeader = null;
        try {
            $orgModel = new OrgModel();
            $m = strtoupper($method);
            if ($id == 'add' && $m == 'POST') {
                $rsp = $orgModel->addOrg(getPostData());
            }
            elseif ($id == null && $method == 'GET') {
                $rsp = $orgModel->getOrgs();
            }
            elseif ($m == 'GET') {
                $rsp = $orgModel->getOrg($id);
            }
            elseif ($m == 'PUT') {
                $rsp = $orgModel->updateOrg($id, getPostData());
            }
            elseif ($m == 'DELETE') {
                $rsp = $orgModel->deleteOrg($id);
            }
            else  {
                $this->sendOutput(json_encode(array('error' => 'Method not supported')),
                            array('Content-Type: application/json', 'HTTP/1.1 422 Unprocessable Entity')
                );
            }
        }
        catch (Error $e) {
            $strErrorDesc = $e->getMessage();
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }
        if (!$strErrorDesc) {
            $this->sendOutput(
                json_encode($rsp),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

}
?>

<?php

/**
 * Copyright (c) 2012, Zarif Safiullin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class WHATSNEW_CTRL_Console extends OW_ActionController
{
    public function listRsp()
    {
        $request = json_decode($_POST['request'], true);

        $event = new BASE_CLASS_ConsoleListEvent('whatsnew.console.load_list', $request, $request['data']);
        OW::getEventManager()->trigger($event);

        $response = array();
        $response['items'] = $event->getList();

        $response['data'] = $event->getData();
        $response['markup'] = array();

        echo json_encode($response);

        exit;
    }

    public function signin()
    {
        $response = WHATSNEW_BOL_Service::getInstance()->setUserAccessKey($_POST['request']);

        echo json_encode($response);

        exit;
    }
}
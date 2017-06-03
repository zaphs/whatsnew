<?php

/**
 * Copyright (c) 2012, Zarif Safiullin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class WHATSNEW_CMP_MailboxItem extends BASE_CMP_ConsoleListIpcItem
{
    public function __construct()
    {
        parent::__construct();

        $this->addClass('ow_mailbox_request_item ow_cursor_default');
    }
}
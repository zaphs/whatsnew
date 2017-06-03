<?php

/**
 * Copyright (c) 2012, Zarif Safiullin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class WHATSNEW_CLASS_ConsoleBridge
{
    /**
     * Class instance
     *
     * @var WHATSNEW_CLASS_ConsoleBridge
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return WHATSNEW_CLASS_ConsoleBridge
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @var WHATSNEW_BOL_Service
     */
    private $service;

    private function __construct()
    {
        $this->service = WHATSNEW_BOL_Service::getInstance();

    }

    public function ping( BASE_CLASS_ConsoleDataEvent $event )
    {
        $params = $event->getParams();

        if (!isset($params['accessKey']))
        {
            return;
        }

        $userId = $this->service->getUserIdByAccessKey($params['accessKey']);

        if ($userId == 0)
        {
            return;
        }

        $data = $event->getItemData('notification');

        $newNotificationCount = NOTIFICATIONS_BOL_Service::getInstance()->findNotificationCount($userId, false);
        $allNotificationCount = NOTIFICATIONS_BOL_Service::getInstance()->findNotificationCount($userId);


        ///////////////////////////////////////////////// MAILBOX //////////////////////////////////////////////////////

        $newInvitationCount = 0;
        $viewInvitationCount = 0;
        if (OW::getPluginManager()->isPluginActive('mailbox'))
        {
            $userId = $this->service->getUserIdByAccessKey($params['accessKey']);

            $data = $event->getItemData('mailbox');

            $newInvitationCount = MAILBOX_BOL_ConversationService::getInstance()->getNewConsoleConversationCount($userId);
            $viewInvitationCount = MAILBOX_BOL_ConversationService::getInstance()->getVievedConversationCountForConsole($userId);

        }

        ///////////////


        $data['counter'] = array(
            'all' => $allNotificationCount + $newInvitationCount + $viewInvitationCount,
            'new' => $newNotificationCount + $newInvitationCount
        );

        $event->setItemData('notification', $data);

    }

    public function loadList( BASE_CLASS_ConsoleListEvent $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        $userId = $this->service->getUserIdByAccessKey($params['accessKey']);

        $loadItemsCount = 7;
        $beforeTimestamp = isset($params['console']['time']) ? $params['console']['time'] : time() ;
        $notifications = NOTIFICATIONS_BOL_Service::getInstance()->findNotificationList($userId, $beforeTimestamp, $params['ids'], $loadItemsCount);
        $notificationIds = array();

        $data['listFull'] = count($notifications) < $loadItemsCount;

        $itemList = array();

        foreach ( $notifications as $notification )
        {
            $interface = $this->processDataInterface(array(
                'key' => 'notification_' . $notification->id,
                'entityType' => $notification->entityType,
                'entityId' => $notification->entityId,
                'pluginKey' => $notification->pluginKey,
                'userId' => $notification->userId,
                'viewed' => (bool) $notification->viewed,
                'data' => $notification->getData()
            ), $notification->getData());

            if ( empty($interface) )
            {
                return;
            }

            $item = new WHATSNEW_CMP_NotificationItem();
            $item->setAvatar($interface['avatar']);
            $item->setContent($interface['string']);
            $item->setKey($interface['key']);
            $item->setToolbar($interface['toolbar']);
            $item->setContentImage($interface['contentImage']);
            $item->setUrl($interface['url']);

            if ( $interface['viewed'] )
            {
                $item->addClass('ow_console_new_message');
            }

            $itemList[] = array('timestamp'=>$notification->timeStamp, 'item' => array($item->render(), $notification->id) );

            $notificationIds[] = $notification->id;
        }

        $event->setData($data);
        NOTIFICATIONS_BOL_Service::getInstance()->markNotificationsViewedByIds($notificationIds);


        /////////////////////////////////////////////////// MAILBOX ////////////////////////////////////////////////////

        if (OW::getPluginManager()->isPluginActive('mailbox'))
        {
            $requests = MAILBOX_BOL_ConversationService::getInstance()->getConsoleConversationList($userId, 0, $loadItemsCount, $beforeTimestamp, $params['ids']);

            $conversationIdList = array();

            foreach ( $requests as $conversation )
            {
                $conversationIdList[] = $conversation['conversationId'];
            }

            /* @var $conversation MAILBOX_BOL_Conversation  */

            $renderedItems = array();

            foreach ( $requests as $request )
            {
                $senderId = 0;
                $userType = '';

                if ( $request['initiatorId'] == $userId )
                {
                    $senderId = $request['interlocutorId'];
                    $userType = 'initiator';
                }

                if ( $request['interlocutorId'] == $userId )
                {
                    $senderId = $request['initiatorId'];
                    $userType = 'interlocutor';
                }

                $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($senderId), true, true, true, false );
                $avatar = $avatar[$senderId];

                $userUrl = BOL_UserService::getInstance()->getUserUrl($senderId);
                $displayName = BOL_UserService::getInstance()->getDisplayName($senderId);


                $subject = $request['subject'];
                $text = '<span class="error">' . OW::getLanguage()->text('mailbox', 'read_permission_denied') . '</span>';
                $conversationUrl = MAILBOX_BOL_ConversationService::getInstance()->getConversationUrl($request['conversationId']);


                if ( BOL_AuthorizationService::getInstance()->isActionAuthorizedForUser($userId, 'mailbox', 'read_message') )
                {
                    $text = mb_strlen($request['text']) > 100 ? mb_substr(strip_tags($request['text']), 0, 100) . '...' : $request['text'];
                }

                $langVars = array(
                    'userUrl'=> $userUrl,
                    'displayName'=>$displayName,
                    'subject' => $subject,
                    'text' => $text,
                    'conversationUrl' => $conversationUrl );

                $string = OW::getLanguage()->text( 'mailbox', 'console_request_item', $langVars );

                $item = new WHATSNEW_CMP_MailboxItem();
                $item->setAvatar($avatar);
                $item->setContent($string);
                $item->setUrl($conversationUrl);

                if ( empty($request['viewed']) || ( ( !($request['viewed'] & MAILBOX_BOL_ConversationDao::VIEW_INITIATOR) && $userType == 'initiator' ) || ( !($request['viewed'] & MAILBOX_BOL_ConversationDao::VIEW_INTERLOCUTOR) && $userType == 'interlocutorId' ) ) )
                {
                    $item->addClass('ow_console_new_message');
                }

                $js = UTIL_JsGenerator::newInstance();
                OW::getDocument()->addOnloadScript($js->generateJs());

                $itemList[] = array('timestamp'=>$request['timeStamp'], 'item' => array($item->render(), $request['id']) );
            }

            MAILBOX_BOL_ConversationService::getInstance()->setConversationViewedInConsole($conversationIdList, $userId);

        }

        function orderByTimestamp($item1, $item2)
        {
            if ( $item1['timestamp'] < $item2['timestamp'])
                return 1;
            else
                return -1;
        }

        usort($itemList, 'orderByTimestamp');

        $itemList = array_slice($itemList, 0, $loadItemsCount);

        foreach( $itemList as $val )
        {
            $event->addItem( $val['item'][0], $val['item'][1] );
        }
    }



    private function processDataInterface( $params, $data )
    {
        if ( empty($data['avatar']) )
        {
            return array();
        }

        foreach ( array('string', 'conten') as $langProperty )
        {
            if ( !empty($data[$langProperty]) && is_array($data[$langProperty]) )
            {
                $key = explode('+', $data[$langProperty]['key']);
                $vars = empty($data[$langProperty]['vars']) ? array() : $data[$langProperty]['vars'];
                $data[$langProperty] = OW::getLanguage()->text($key[0], $key[1], $vars);
            }
        }

        if ( empty($data['string']) )
        {
            return array();
        }

        if ( !empty($data['contentImage']) )
        {
            $data['contentImage'] = is_string($data['contentImage'])
                ? array( 'src' => $data['contentImage'] )
                : $data['contentImage'];
        }
        else
        {
            $data['contentImage'] = null;
        }

        $data['contentImage'] = empty($data['contentImage']) ? array() : $data['contentImage'];
        $data['toolbar'] = empty($data['toolbar']) ? array() : $data['toolbar'];
        $data['key'] = isset($data['key']) ? $data['key'] : $params['key'];
        $data['viewed'] = isset($params['viewed']) && !$params['viewed'];
        $data['url'] = isset($data['url']) ? $data['url'] : null;

        return $data;
    }

    public function init()
    {
        if (!OW::getPluginManager()->isPluginActive('notifications'))
        {
            return;
        }
        OW::getEventManager()->bind('whatsnew.console.load_list', array($this, 'loadList'));
        OW::getEventManager()->bind('console.ping', array($this, 'ping'));
    }
}
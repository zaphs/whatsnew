<?php

/**
 * Copyright (c) 2012, Zarif Safiullin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */


class WHATSNEW_BOL_Service
{
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return WHATSNEW_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private $userDao;

    public function __construct()
    {
        $this->userDao = WHATSNEW_BOL_UserDao::getInstance();
    }

    public function getUserIdByAccessKey($accessKey)
    {
        if (empty($accessKey))
        {
            return 0;
        }
        $example = new OW_Example();
        $example->andFieldLike('accessKey', $accessKey);

        /**
         * @var $user WHATSNEW_BOL_User
         */
        $user = $this->userDao->findObjectByExample($example);

        if (empty($user))
        {
            return 0;
        }

        return $user->userId;
    }

    public function setUserAccessKey($data)
    {
        $adapter = new BASE_CLASS_StandardAuth($data['identity'], $data['password']);

        $result = OW::getUser()->authenticate($adapter);

        if ( $result->isValid() )
        {
            $userId = OW::getUser()->getId();
            $loginCookie = BOL_UserService::getInstance()->saveLoginCookie($userId);

            setcookie('ow_login', $loginCookie->getCookie(), (time() + 86400 * 7), '/', null, null, true);

            $example = new OW_Example();
            $example->andFieldLike('userId', $userId);
            $userAccessKey = $this->userDao->findObjectByExample($example);

            if( $userAccessKey == null )
            {
                $userAccessKey = new WHATSNEW_BOL_User();
                $userAccessKey->userId = $userId;
                $userAccessKey->accessKey = sha1( time().$userId );
                $this->userDao->save($userAccessKey);
            }

            return array('accessKey'=>$userAccessKey->accessKey);
        }

        return array('accessKey'=>"");
    }
}

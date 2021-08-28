<?php
declare(strict_types=1);

namespace RedisApp\Handlers;

use Predis\Client;
use Laminas\Diactoros\Response;

class AuthHandler
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }

    public function check()
    {
        // check upfront for session
        if(!empty($userData)) return $userData;
        
        $authSecret = $_COOKIE['auth'];

        if(!empty($authSecret)){
            if($userId = $this->client->hget("auths", $authSecret)){

                $userDetails = $this->client->hgetall("user:$userId");
                
                // secret exists. confirm if the user owns this auth
                if($userDetails['auth'] == $authSecret){
                    $userData = [
                        'username' => $userDetails['username'],
                        'fullname' => $userDetails['fullname'],
                        'email' => $userDetails['email'],
                        'created' => date('Y-m-d', intval($this->client->zscore("users_by_time", $userDetails['username']))),
                        'bio' => $userDetails['bio'],
                        'id' => $userId,
                    ];

                    return $userData;
                }
            }
        }
        return false;
    }
}
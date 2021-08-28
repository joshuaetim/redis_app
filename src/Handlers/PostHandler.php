<?php
declare(strict_types=1);

namespace RedisApp\Handlers;

use Predis\Client;
use Laminas\Diactoros\Response;
use RedisApp\Handlers\PostHandler;

class PostHandler
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }

    /**
     * Get a specific post
     * Used by other methods
     * 
     * Fetch Post data from Redis - HGETALL post:id
     * Fetch associate user to the post - HGETALL user:user_id
     * Organize data to usable format
     * Return data to method
     */

    public static function getPost($postId, $preview = false)
    {
        $self = new static; // instantiate object

        $post = $self->client->hgetall("post:$postId");

        $post['id'] = $postId;
        $user = $self->client->hgetall("user:{$post['user_id']}");
        $post['user'] = [
            'id' => $post['user_id'],
            'username' => $user['username'],
        ];

        $post['time'] = date("Y-m-d g:i:s a", (int) $post['time']);

        if($preview){
            $post['body'] = substr($post['body'], 0, 250);
        }

        return $post;
    }

    public static function getUserPosts($username, $start = 0, $stop = -1)
    {
        $self = new static;

        $postsIds = $self->client->lrange($username.":posts", $start, $stop);

        $posts = [];

        foreach($postsIds as $id){
            $data = PostHandler::getPost($id);

            $data['body'] = substr($data['body'], 0, 250);

            $posts[] = $data;
        }

        return $posts;
    }
}
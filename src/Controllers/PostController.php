<?php
declare(strict_types=1);

namespace RedisApp\Controllers;

use Predis\Client;
use Laminas\Diactoros\Response;
use RedisApp\Handlers\PostHandler;
use RedisApp\Handlers\RequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class PostController 
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }

    /**
     * Show All Posts
     * Open to all site users, visitors and registers
     * makes use of the Timeline Hash created in Redis
     * 
     * Algo: 
     * - Get All Post Ids stored in Timeline (50 of them)
     * - For each id, get post Data with getPost method
     * - Further organization suitable for the route
     * - Return the View with Data Loaded
     */
    public function index(ServerRequestInterface $request) : Response
    {
        $auth = authCheck();

        $sortBy = $_GET['sort_by'] ?? "score";

        // return jsonResponse($sortBy);

        if($sortBy == "score"){
            $postIds = $this->client->zrevrange("posts_by_score", 0, 50);
        }
        else{
            $postIds = $this->client->zrevrange("posts_by_time", 0, 50);
        }

        // return jsonResponse($postIds);


        $posts = [];

        foreach($postIds as $id){
            $posts[] = PostHandler::getPost($id, true);
        }

        // return jsonResponse($posts);

        $data = ['title' => 'Blog', 'posts' => $posts, 'auth' => $auth, 'sort_by' => $sortBy];

        return view('posts/index.html', $data);
    }
    
    /**
     * Display the form to create a Post
     */
    public function create(ServerRequestInterface $request) : Response
    {
        $auth = authCheck();

        $data = ['title' => 'Create Post', 'auth' => $auth];

        return view('posts/create.html', $data);
    }

    /**
     * Show A single post
     * Open to everyone
     * 
     * Algo:
     * - Get the post id value from request
     * - Get post Data with getPost method
     * - Return view with Data Loaded
     */
    public function single(ServerRequestInterface $request, array $args) : Response
    {
        $auth = authCheck();

        $postId = $args['id'];

        $post = PostHandler::getPost($postId);

        if(empty($post)) return redirect('/posts');

        // check voted status
        $voted = false;

        if(!empty($auth['id']) && $this->client->sismember("voted:$postId", $auth['id'])){
            $voted = true;
        }

        // return jsonResponse($post);

        $data = ['title' => $post['title']. ' - Post', 'auth' => $auth, 'post' => $post, 'voted' => $voted];

        return view('posts/single.html', $data);
    }

    /**
     * Store a Post in the Redis Database
     * Limited to logged in members
     * 
     * Algo:
     * - Get the posted information
     * - Perform verification to ensure they're valid
     * - Increment the nextpostid on Redis - INCR next_post_id
     * - Add the Post to the redis Hash - HSET post:id user_id "" time "" title "" body ""
     * - Get all followers of the current user - ZRANGE followers:user_id 0 -1  (set stored as ZSET followers:2 "time" "follower_id")
     *  - Loop through followers id and add post id to their curated posts - LPUSH posts:fid "post_id"
     *  - Add Post to the General Timeline - LPUSH timeline "post_id"
     *  - Redirect to Posts Page
     */
    public function store(ServerRequestInterface $request): Response
    {
        $auth = authCheck();

        $requestBody = $request->getParsedBody();

        $title = $requestBody['title'];
        $body = $requestBody['body'];

        $_SESSION['flash']['input'] = ['title' => $title, 'body' => $body];

        if(strlen(trim($title)) < 4 || strlen(trim($title)) > 70 || strlen(trim($body)) < 10 || strlen(trim($body)) > 10000){
            return errorRedirect('Please fill in the details correctly', '/create-post');
        }

        $postId = $this->client->incr('next_post_id');
        $this->client->hset("post:$postId", 
                        "user_id", $auth['id'],
                        "time", time(),
                        "title", $title,
                        "body", $body,
                        "votes", 1
                    );
        
        // add to user's owned posts
        $this->client->lpush($auth['username'].":posts", $postId);
        
        // // get all followers
        // $followers = $this->client->zrange("followers:{$auth['id']}", 0, -1);
        // $followers[] = $auth['id']; // you are your follower too

        // foreach($followers as $fid){
        //     $this->client->lpush("posts:$fid", $postId);
        // }

        // add post to zset time sorted
        $this->client->zadd('posts_by_time', time(), $postId);

        // add post to score sorted zset
        $this->client->zadd('posts_by_score', time() + 432, $postId);

        // add user to voters of this post
        $this->client->sadd("voted:$postId", $auth['id']);
        

        return redirect('/posts');
    }
    
    /**
     * Show page to edit post details
     * request - /posts/{id}/edit
     */
    public function edit(ServerRequestInterface $request, array $args): Response
    {
        $post = PostHandler::getPost($args['id']);

        if(empty($post)) return redirect('/posts');

        $auth = authCheck();

        // check if post belongs to owner
        if($auth['id'] !== $post['user_id']) return redirect('/posts');

        $data = ['title' => 'Edit Post', 'auth' => $auth, 'post' => $post];

        return view('posts/edit.html', $data);

        // return jsonResponse($args);
    }

    /**
     * Update the post information
     * Only the title and body is affected. 
     * TODO: add modified time to the post
     */
    public function update(ServerRequestInterface $request): Response 
    {
        $auth = authCheck();

        $requestBody = $request->getParsedBody();

        $title = $requestBody['title'];
        $body = $requestBody['body'];
        $id = $requestBody['id'];

        // check if post actually belongs to user

        $postOwner = PostHandler::getPost($id)['user_id'];

        if($auth['id'] !== $postOwner) return redirect('/posts');

        if(strlen(trim($title)) < 4 || strlen(trim($title)) > 70 || strlen(trim($body)) < 10 || strlen(trim($body)) > 10000){
            return errorRedirect('Please fill in the details correctly', "/posts/$id/edit");
        }

        // update post
        $this->client->hset("post:$id",
                        "title", $title,
                        "body", $body
                    );

        $_SESSION['flash']['success'] = "Post Updated Successfully";

        return redirect("/posts/$id");
        

        // return jsonResponse($postOwner);
    }

    /**
     * Like the post
     * Procedure:
     * check if user has voted
     * if yes, then undo vote, and subract 432 from score, and remove 1 from post votes
     * else then (1) add to voted set and (2) add 432 to score, and add 1 to post votes
     */
    public function like(ServerRequestInterface $request): Response
    {
        $auth = authCheck();

        $requestBody = $request->getParsedBody();

        $postId = $requestBody['id'];

        if($this->client->sismember("voted:$postId", $auth['id'])){
            // already voted
            $this->client->srem("voted:$postId", $auth['id']);
            $this->client->zincrby("posts_by_score", -432, $postId);
            $this->client->hincrby("post:$postId", "votes", -1);
            
            return redirect("/posts/$postId");
        }

        // not voted
        $this->client->sadd("voted:$postId", $auth['id']);
        $this->client->zincrby("posts_by_score", 432, $postId);
        $this->client->hincrby("post:$postId", "votes", 1);
        
        return redirect("/posts/$postId");
    }
}
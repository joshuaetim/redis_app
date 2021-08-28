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

        $postIds = $this->client->lrange("timeline", 0, 50);

        $posts = [];

        foreach($postIds as $id){
            $posts[] = PostHandler::getPost($id, true);
        }

        // return jsonResponse($posts);

        $data = ['title' => 'Create Post', 'posts' => $posts, 'auth' => $auth];

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
    public function single(ServerRequestInterface $request) : Response
    {
        $auth = authCheck();

        $postId = RequestHandler::getLastParam($request);

        $post = PostHandler::getPost($postId);

        // return jsonResponse($post);

        $data = ['title' => $post['title']. ' - Post', 'auth' => $auth, 'post' => $post];

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
                        "body", $body
                    );
        
        // add to user's owned posts
        $this->client->lpush($auth['username'].":posts", $postId);
        
        // get all followers
        $followers = $this->client->zrange("followers:{$auth['id']}", 0, -1);
        $followers[] = $auth['id']; // you are your follower too

        foreach($followers as $fid){
            $this->client->lpush("posts:$fid", $postId);
        }

        $this->client->lpush("timeline", $postId); // for all site visitors

        return redirect('/posts');
    }
    
    /**
     * Show page to edit post details
     * request - /posts/{id}/edit
     */
    public function edit(ServerRequestInterface $request)
    {

    }
}
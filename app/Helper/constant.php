<?php


namespace App\Helper;

trait Constant
{

    public $limit = 20;

    public $usertable = 'users';
    public $userUsername = 'users.username';
    public $userPassword = 'users.password';

    public $username = 'username';

    public $postTable = 'posts';
    public $postId = 'post_id';
    public $postText  = 'text';

    public $commentTable = 'comments';
    public $commentId = 'comment_id';

    public $problemTable = 'problems';

    public $isDelete = 'is_delete';

    public $assetpath = "e39ac4eaaaa14573b03ef31d70b60fc4"; // asset folder (assetFile)
}

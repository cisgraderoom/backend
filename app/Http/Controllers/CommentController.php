<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Models\Comment;
use App\Helper\RoleBase;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CommentController extends Controller
{
    use Constant;

    public function newComment(string $classcode, int $id, Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        $post = DB::table($this->postTable)->where($this->postId, $id)->first();
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบโพสต์นี้'
            ], Response::HTTP_BAD_REQUEST);
        }
        $text = $request->input('text', '');
        $text = htmlentities(strip_tags(html_entity_decode(htmlspecialchars($text)))) ?? '';
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $comment = new Comment();
        $comment->classcode = $classcode;
        $comment->post_id = $id;
        $comment->text = $text;
        $comment->username = $user->username;
        if (!$comment->save()) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถสร้างคอมเมนต์ได้'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->cacheComment($classcode, $id);
        return response()->json([
            'status' => true,
            'message' => 'สร้างคอมเมนต์สำเร็จ'
        ], Response::HTTP_CREATED);
    }


    public function getComment(string $classcode, int $id)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $redisKey = 'comment:class:' . $classcode . ':' . $id;
        if (!Redis::get($redisKey)) {
            $this->cacheComment($classcode, $id);
        }

        $data = json_decode(Redis::get($redisKey));

        return response()->json([
            'status' => true,
            'message' => 'ดึงคอมเมนต์สำเร็จ',
            'data' => $data ?: [],
        ], Response::HTTP_CREATED);
    }


    public function deleteComment(string $classcode, int $postId, int $id)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $result = DB::table($this->commentTable)->where($this->commentId, $id)->where($this->postId, $postId)->where($this->username, $user->username)->update([$this->isDelete => 1]);
        if (!$result) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'ลบคอมเมนต์ไม่สำเร็จ'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $this->cacheComment($classcode, $postId);
        return response()->json([
            'status' => true,
            'message' => 'ลบคอมเมนต์สำเร็จ',
        ]);
    }


    private function cacheComment(string $classcode, int $id)
    {
        $redisKey = 'comment:class:' . $classcode . ':' . $id;
        $comments = DB::table('comments')->where('classcode', $classcode)->where($this->postId, $id)->where('is_delete', false)->leftJoin('users', 'comments.username', '=', 'users.username')->orderByDesc('post_id')->get(['users.name', 'comments.text', 'comments.comment_id', 'comments.post_id', 'comments.classcode', 'comments.created_at', 'comments.updated_at', 'users.role']);
        Redis::setEx($redisKey, 3600 * 24, json_encode($comments));
    }
}

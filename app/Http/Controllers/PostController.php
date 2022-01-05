<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\PageInfo;
use App\Helper\RoleBase;
use App\Models\Classroom;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PostController extends Controller
{
    use Constant;

    public function newPost(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $classcode = $request->input('classcode', '');
        $classroom = Classroom::where('classcode', $classcode)->first();
        if (!$classroom) {
            return response()->json([
                'status' => false,
                'message' => 'รหัสวิชาไม่ถูกต้อง'
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
        $post = new Post();
        $post->classcode = $classcode;
        $post->text = $text;
        $post->username = $user->username;
        if (!$post->save()) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถสร้างโพสต์ได้'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->cachePost($classcode);
        return response()->json([
            'status' => true,
            'message' => 'สร้างโพสต์สำเร็จ'
        ], Response::HTTP_CREATED);
    }


    public function getPost(string $classcode, Request $request)
    {
        $pageInfo = new PageInfo();
        $page = $pageInfo->mapPage((int) $request->get('page', 1));
        $itemsPerPage = $pageInfo->itemPerPage();
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $redisKey = 'post:class:' . $classcode;
        if (!Redis::get($redisKey)) {
            $this->cachePost($classcode);
        }

        $data = json_decode(Redis::get($redisKey));
        $total = count($data);
        $data = array_slice($data, ($page - 1) * $itemsPerPage, $itemsPerPage) ?: [];

        return $pageInfo->pageInfo($page, $total, $itemsPerPage, $data);
    }

    public function getPostById(string $classcode, int $id)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $result = DB::table($this->postTable)->where($this->postId, $id)->first();
        if (empty($result)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบโพสต์นี้'
            ], Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'status' => true,
            'message' => 'ดึงโพสต์สำเร็จ',
            'data' => $result,
        ]);
    }

    public function deletePost(string $classcode, int $id)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $result = DB::table($this->postTable)->where($this->postId, $id)->update([$this->isDelete => 1]);
        if (!$result) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'ลบโพสต์ไม่สำเร็จ'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $this->cachePost($classcode);
        return response()->json([
            'status' => true,
            'message' => 'ลบโพสต์สำเร็จ',
        ]);
    }

    public function updatePost(string $classcode, int $id, Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $text = $request->input('text', '');
        $text = htmlentities(strip_tags(html_entity_decode(htmlspecialchars($text)))) ?? '';
        $result = DB::table($this->postTable)->where($this->postId, $id)->update([$this->postText => $text]);
        if (!$result) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'แก้ไขโพสต์ไม่สำเร็จ'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $this->cachePost($classcode);
        return response()->json([
            'status' => true,
            'message' => 'แก้ไขโพสต์สำเร็จ',
        ]);
    }

    private function cachePost(string $classcode)
    {
        $redisKey = 'post:class:' . $classcode;
        $posts = DB::table('posts')->where('classcode', $classcode)->where('is_delete', false)->leftJoin('users', 'posts.username', '=', 'users.username')->orderByDesc('post_id')->get(['users.name', 'posts.text', 'posts.post_id', 'posts.classcode', 'posts.created_at', 'posts.updated_at']);
        Redis::setEx($redisKey, 3600 * 24, json_encode($posts));
    }
}

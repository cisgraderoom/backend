<?php

namespace App\Http\Controllers;

use App\Helper\PageInfo;
use App\Helper\RoleBase;
use App\Models\Classroom;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class PostController extends Controller
{
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
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ]);
        }

        $redisKey = 'post:class:' . $classcode;
        if (!Redis::get($redisKey)) {
            $post = new Post();
            $posts = $post->where('classcode', $classcode)->where('is_delete', false)->get();
            Redis::setEx($redisKey, 3600 * 24, json_encode($posts));
        }

        $data = json_decode(Redis::get($redisKey));
        $total = count($data);
        $data = array_slice($data, ($page - 1) * $itemsPerPage, $itemsPerPage) ?: [];

        return $pageInfo->pageInfo($page, $total, $itemsPerPage, $data);
    }

    private function cachePost(string $classcode)
    {
        $redisKey = 'post:class:' . $classcode;
        $post = new Post();
        $posts = $post->where('classcode', $classcode)->where('is_delete', false)->get();
        Redis::setEx($redisKey, 3600 * 24, json_encode($posts));
    }
}

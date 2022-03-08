<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\PageInfo;
use App\Helper\RoleBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class SubmissionController extends Controller
{
    use Constant;

    public function submit(int $id, Request $request)
    {
        $user = auth()->user();
        $classcode  = $request->input('classcode', '');
        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $open = date('c', time());
        $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('close_at', '>=', $open)->orWhere('close_at', null)->where('open_at', '<=', $open)->where('is_hidden', false)->where('is_delete', false)->where('problem_id', $id)->first();
        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถส่งงานได้ขณะนี้'
            ]);
        }
        $code  = $request->input('code', null);
        $result = "Queue";

        $res = DB::table($this->submissionTable)->insert([
            'username' => $user->username,
            'classcode' => $classcode,
            'problem_id' => $id,
            'code' => $code,
            'lang' => 'c',
            'result' => $result,
            'created_at' => date('Y-m-d:h:i:s', time()),
        ]);

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถส่งงานได้ขณะนี้'
            ]);
        }

        DB::table($this->scoreTable)->insertOrIgnore([
            'username' => $user->username,
            'classcode' => $classcode,
            'problem_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'msg' => 'ส่งงานสำเร็จ'
        ]);
    }

    public function scoreByProblemId(string $classcode, int $id)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $res = DB::table($this->submissionTable)->where('classcode', $classcode)->where('problem_id', $id)->latest()->first();
        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่พบการส่งงาน'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => $res->result == 'Queue' ? 'กำลังตรวจ' : 'ตรวจสำเร็จ',
            'state' => $res->result == 'Queue' ? false : true,
            'data' => $res,
            'array_result' => $res->result != 'Queue' ? str_split($res->result) : []
        ]);
    }

    public function scoreByClassroom(string $classcode)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $score = [];
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isStudent($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }


        $res = DB::table($this->scoreTable)->where('score.classcode', $classcode)->join($this->usertable, 'score.username', '=', 'users.username')->join($this->problemTable, 'score.problem_id', '=', 'problems.problem_id')->get([
            'users.username',
            'users.name',
            'score.problem_id',
            'problems.problem_name',
            'score.score',
        ])->toArray() ?: [];

        foreach ($res as $_ => $item) {
            $item = (array)$item;
            $score[$item['problem_id']][] = $item;
        }

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถดึงคะแนนได้ขณะนี้'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'ดึงคะแนนสำเร็จ',
            'data' => $score,
        ]);
    }

    public function getListSubmission(string $classcode, int $problem, Request $request)
    {
        $rolebase = new RoleBase();
        $pageInfo = new PageInfo();
        $page = (int)$request->input('page', 1);
        $user = auth()->user();

        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $datas = DB::table($this->submissionTable)->where('classcode', $classcode)->where('problem_id', $problem)->offset(($page - 1) * $this->limit)->take($this->limit)->get()->toArray() ?: [];
        $total = DB::table($this->submissionTable)->where('classcode', $classcode)->where('problem_id', $problem)->count() ?: 0;
        return $pageInfo->pageInfo($page, $total, $this->limit, $datas);
    }


    public function scoreByUser(string $classcode)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $problem = DB::table($this->problemTable)->where('classcode', $classcode)->where('is_delete', false)->get('problem_id')->toArray();
        $problem  = array_column($problem, 'problem_id') ?: [];

        $res = DB::table($this->scoreTable)->where('classcode', $classcode)->whereIn('problem_id', $problem)->where('username', $user->username)->get();
        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถส่งงานได้ขณะนี้'
            ]);
        }

        return response()->json([
            'status' => false,
            'data' => $res
        ]);
    }
}

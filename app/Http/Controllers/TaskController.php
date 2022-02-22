<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\RoleBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{

    use Constant;

    public function newTask(Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $problemName = $request->input('problemName', '');
        $problemDesc = $request->input('problemDesc', '');
        $testcase = $request->input('numberOfTestcase', 0);
        $score = $request->input('score', 0);
        $classcode  = $request->input('classcode');
        $openAt = $request->input('open', date('Y-m-d h:i:s'));
        $closeAt = $request->input('close', null);
        // $files = $request->hasFile('asset');
        // $path = Storage::putFile('testcase', $request->file('testcase'));
        if ($closeAt !== null && date('Y-m-d h:i:s', $openAt) <= date('Y-m-d h:i:s', $closeAt)) {
            return response()->json([
                'status' => false,
                'message' => 'ตั้งเวลาผิดพลาด'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $resp = DB::table($this->problemTable)->insert([
            'problem_name' => $problemName,
            'problem_desc' => $problemDesc,
            'max_score' => $score,
            'username' => $user->username,
            'classcode' => $classcode,
            'open_at' => $openAt,
            'close_at' => $closeAt,
            'testcase' => $testcase,
        ]);

        if (!$resp) {
            return response()->json([
                'status' => false,
                'msg' => 'สร้างโจทย์ไม่สำเร็จ'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'สร้างโจทย์สำเร็จ'
        ]);
    }

    public function getTask(Request $request)
    {
        $classcode  = $request->input('classcode', '');
        $user = auth()->user();

        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $open = date('Y-m-d h:i:s', time());

        $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('is_hidden', false)->where('is_delete', false)->where('open_at', '<=', $open)->where('close_at', '>=', $open)->orWhere('close_at', null)->get();

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถดึงข้อมูลได้'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'ข้อมูลโจทย์',
            'data' => $res,
        ]);
    }
}

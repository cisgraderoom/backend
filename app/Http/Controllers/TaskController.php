<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\RoleBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\Storage;

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
        $openAt = $request->input('open', time());
        $closeAt = $request->input('close', null);
        $openAt = date('c', $openAt);
        // $files = $request->hasFile('asset');
        // $path = Storage::putFile('testcase', $request->file('testcase'));
        if ($closeAt != null) {
            $closeAt = date('c', $closeAt);
            if ($openAt >= $closeAt) {
                return response()->json([
                    'status' => false,
                    'message' => 'ตั้งเวลาผิดพลาด'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

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

        $resp = DB::table($this->problemTable)->insertGetId([
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

        $usernmae = DB::table('user_access')->where('classcode', $classcode)->join($this->usertable, 'user_access.username', '=', 'users.username')->where('users.role', 'student')->get('users.username')->toArray() ?: [];
        $usernmae = array_column($usernmae, 'username') ?: [];

        $score = [];
        foreach ($usernmae as $username) {
            array_push($score, [
                'username' => $username,
                'classcode' => $classcode,
                'problem_id' => $resp,
            ]);
        }

        DB::table($this->scoreTable)->insertOrIgnore($score);

        return response()->json([
            'status' => true,
            'msg' => 'สร้างโจทย์สำเร็จ'
        ]);
    }

    public function editTask(int $id, Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $problemName = $request->input('problemName', '');
        $problemDesc = $request->input('problemDesc', '');
        // $score = $request->input('score', 0);
        $classcode  = $request->input('classcode');
        $openAt = $request->input('open', time());
        $closeAt = $request->input('close', null);
        $openAt = date('c', $openAt);
        // $files = $request->hasFile('asset');
        // $path = Storage::putFile('testcase', $request->file('testcase'));
        if ($closeAt != null) {
            $closeAt = date('c', $closeAt);
            if ($openAt >= $closeAt) {
                return response()->json([
                    'status' => false,
                    'message' => 'ตั้งเวลาผิดพลาด'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
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

        $resp = DB::table($this->problemTable)->update([
            'product_id' => $id,
            'problem_name' => $problemName,
            'problem_desc' => $problemDesc,
            'username' => $user->username,
            'classcode' => $classcode,
            'open_at' => $openAt,
            'close_at' => $closeAt,
        ]);

        if (!$resp) {
            return response()->json([
                'status' => false,
                'msg' => 'แก้ไขโจทย์ไม่สำเร็จ'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'แก้ไขโจทย์สำเร็จ'
        ]);
    }


    public function hiddenProblem(int $id, Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $classcode  = $request->input('classcode', '');
        $hidden  = (bool)$request->input('hidden', false);
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

        $res = DB::table($this->problemTable)->where('problem_id', $id)->update([
            'is_hidden' => $hidden,
        ]);

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถเปลี่ยนสถานะได้'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'เปลี่ยนสถานะสำเร็จ',
        ]);
    }

    public function deleteProblem(int $id, Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $classcode  = $request->input('classcode', '');
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

        $res = DB::table($this->problemTable)->where('problem_id', $id)->update([
            'is_delete' => true,
        ]);

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถเปลี่ยนสถานะได้'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'เปลี่ยนสถานะสำเร็จ',
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

        $open = date('c', time());
        $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('close_at', '>=', $open)->where('open_at', '<=', $open)->where('is_hidden', false)->where('is_delete', false)->get();

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

    public function getTaskById(int $id, Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $classcode  = $request->input('classcode', '');
        if (!$rolebase->checkUserHasPermission($user, $classcode)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $open = date('c', time());
        $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('close_at', '>=', $open)->orWhere('close_at', null)->where('problem_id', $id)->where('open_at', '<=', $open)->where('is_hidden', false)->where('is_delete', false)->first();

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

    public function getTaskAdmin(Request $request)
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
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $res = DB::table($this->problemTable)->where('classcode', $classcode)->get();

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

    public function getTaskByIdAdmin(int $id, Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $classcode  = $request->input('classcode', '');
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

        $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('problem_id', $id)->first();
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

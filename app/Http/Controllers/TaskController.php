<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\RoleBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use ZipArchive;

class TaskController extends Controller
{

    use Constant;

    public function newTasks(Request $request)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $problemName = $request->input('problemName', '');
        $problemDesc = $request->input('problemDesc', '');
        $score = $request->input('score', 0);
        $classcode  = $request->input('classcode');
        $openAt = $request->input('open', time());
        $closeAt = $request->input('close', null);
        if (!$_FILES['testcase']) {
            return response()->json([
                'status' => false,
                'message' => 'กรุณาเพิ่มไฟล์ที่ต้องการอัพโหลด'
            ], Response::HTTP_BAD_REQUEST);
        }
        $zip = new ZipArchive();
        $openAt = date('Y-m-d H:i:s', $openAt);

        if ($closeAt != null) {
            $closeAt = date('Y-m-d H:i:s', $closeAt);
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

        $target_dir = storage_path("testcase/");
        $target_file = $target_dir . time() . '_' . basename($_FILES['testcase']['name']);
        move_uploaded_file($_FILES['testcase']['tmp_name'], $target_file);

        if ($_FILES['asset']) {
            $asset_target_dir = storage_path("asset/");
            $asset_target_file = $asset_target_dir . time() . '_' . basename($_FILES['asset']['name']);
            move_uploaded_file($_FILES['asset']['tmp_name'], $asset_target_file);
        }


        $zip->open($target_file);
        $testcase = ceil($zip->count() / 2);
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

        $zip->extractTo($target_dir . $resp . '/');
        $zip->close();


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
        if ($user->role === 'student') {
            $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('close_at', '>=', $open)->where('open_at', '<=', $open)->where('is_hidden', false)->where('is_delete', false)->get();
        } else {
            $res = DB::table($this->problemTable)->where('classcode', $classcode)->get();
        }

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
        if ($user->role === 'student') {
            $res = DB::table($this->problemTable)->where('problem_id', $id)->where('classcode', $classcode)->where('close_at', '>=', $open)->orWhere('close_at', null)->where('open_at', '<=', $open)->where('is_hidden', false)->where('is_delete', false)->first();
        } else {
            $res = DB::table($this->problemTable)->where('classcode', $classcode)->where('problem_id', $id)->first();
        }

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

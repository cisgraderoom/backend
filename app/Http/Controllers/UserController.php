<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\PageInfo;
use App\Helper\RoleBase;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class UserController extends Controller
{

    use  HasApiTokens, HasFactory, Notifiable, Constant;

    public function checklogin()
    {
        return response()->json([
            'status' => false,
            'message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function login()
    {
        $credential = request()->only(['username', 'password']);
        if (!auth()->validate($credential)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบผู้ใช้ หรือ ข้อมูลที่กรอกไม่ถูกต้อง'
            ], Response::HTTP_NOT_FOUND);
        }
        $user = User::where('username', $credential['username'])->first();
        $user->tokens()->delete();
        $token = $user->createToken($_SERVER['HTTP_USER_AGENT'], [$user->role]);
        return response()->json([
            'status' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'token' => $token->plainTextToken,
                'user' => [
                    'username' => $credential['username'],
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ]
        ], Response::HTTP_OK);
    }

    public function uploadStudent(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        $role = $request->input('role', 'student');
        if (!$this->checkRole($role)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิผู้ใช้นี้'
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!$rolebase->isAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        if (!isset($_FILES['file'])) {
            return response()->json([
                'status' => false,
                'message' => 'กรุณาเพิ่มไฟล์ที่ต้องการอัพโหลด'
            ], Response::HTTP_BAD_REQUEST);
        }
        $handle = fopen($_FILES['file']['tmp_name'], "r");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $username = $data[0];
            $password = bcrypt($data[0]);
            $name = $data[1];
            DB::table('users')->insertOrIgnore([
                'username' => $username,
                'password' => $password,
                'name' => $name,
                'role' => $role,
                'created_at' =>  date('c', time()),
                'updated_at' => date('c', time()),
            ]);
        }
        fclose($handle);
        return response()->json([
            'status' => true,
            'message' => 'เพิ่มผู้ใช้สำเร็จ'
        ], Response::HTTP_OK);
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();
        $newpassword = trim($request->input('newpassword', ''));
        if (strlen($newpassword) < 8) {
            return response()->json([
                'status' => false,
                'message' => 'รหัสผ่านอย่างน้อย 8 ตัวอักษร'
            ]);
        }
        $resp = DB::table($this->usertable)->where('username', $user->username)->update(['password' => bcrypt($newpassword)]);
        if (!$resp) {
            return response()->json([
                'status' => false,
                'message' => 'เปลี่ยนรหัสผ่านไม่สำเร็จ'
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'
        ]);
    }

    public function getUserAll(Request $request)
    {
        $rolebase = new RoleBase();
        $pageInfo = new PageInfo();
        $page = (int)$request->input('page', 1);
        $user = auth()->user();
        if (!$rolebase->isAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $datas = DB::table($this->usertable)->offset(($page - 1) * $this->limit)->take($this->limit)->get()->toArray();;
        $total = DB::table($this->usertable)->count() ?: 0;
        return $pageInfo->pageInfo($page, $total, $this->limit, $datas);
    }

    public function getByUserId(string $username)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $data = DB::table($this->usertable)->where('username', $username)->first();
        if (empty($data)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'status' => true,
            'message' => 'ดึงผู้ใช้สำเร็จ',
            'data' => $data,
        ], Response::HTTP_OK);
    }

    public function resetPassword(string $username)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $data = DB::table($this->usertable)->where('username', $username)->first();
        if (empty($data)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], Response::HTTP_NOT_FOUND);
        }

        $newPassword = bcrypt($username);

        $resp = DB::table($this->usertable)->where('username', $username)->update(['password' => $newPassword]);
        if ($resp <= 0) {
            return response()->json([
                'status' => true,
                'message' => 'ไม่สามารถแก้ไขได้'
            ], Response::HTTP_OK);
        }
        return response()->json([
            'status' => true,
            'message' => 'แก้ผู้ใช้สำเร็จ'
        ], Response::HTTP_OK);
    }

    public function newUser(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $name = $request->input('name');
        $username = $request->input('username');
        $password = bcrypt($request->input('password'));
        $role = $request->input('role', 'student');

        if (!$this->checkRole($role)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิผู้ใช้นี้'
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = DB::table($this->usertable)->where('username', $username)->first();
        if ($data) {
            return response()->json([
                'status' => false,
                'message' => 'ชื่อผู้ใช้ซ้ำ'
            ], Response::HTTP_BAD_REQUEST);
        }

        $resp = DB::table($this->usertable)->insert([
            'name' => $name, 'role' => $role, 'username' => $username, 'password' => $password,
        ]);
        if (!$resp) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถเพิ่มได้'
            ], Response::HTTP_BAD_REQUEST);
        }
        return response()->json([
            'status' => true,
            'message' => 'เพิ่มผู้ใช้สำเร็จ'
        ], Response::HTTP_OK);
    }

    public function updateUser(string $username, Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $data = DB::table($this->usertable)->where('username', $username)->first();
        if (empty($data)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], Response::HTTP_NOT_FOUND);
        }

        $name = $request->input('name');
        $role = $request->input('role', 'student');

        $resp = DB::table($this->usertable)->where('username', $username)->update(['name' => $name, 'role' => $role]);
        if ($resp <= 0) {
            return response()->json([
                'status' => true,
                'message' => 'ไม่สามารถแก้ไขได้'
            ], Response::HTTP_OK);
        }
        return response()->json([
            'status' => true,
            'message' => 'แก้ผู้ใช้สำเร็จ'
        ], Response::HTTP_OK);
    }

    private function checkRole(string $role)
    {
        if (!in_array($role, ['teacher', 'student', 'superteacher'])) {
            return false;
        }
        return true;
    }
}

<?php

namespace App\Http\Controllers;

use App\Helper\RoleBase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class UserController extends Controller
{

    use  HasApiTokens, HasFactory, Notifiable;

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

    public function uploadStudent()
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
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
        $oldpassword = $request->input('oldpassword', '');
        $newpassword = $request->input('newpassword', '');
        $users = new User();
        $credential = ['username' => $user->username, 'password' => $oldpassword];
        if (!auth()->validate($credential)) {
            var_dump('password');
        }
        // $users->update([
        // 'password' => $newpassword,
        // ])
        // $user = new User();
        // $user->select('user')
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserController extends Controller
{

    use  HasApiTokens, HasFactory, Notifiable;

    public function checklogin()
    {
        return response()->json([
            'status' => Response::HTTP_UNAUTHORIZED,
            'message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน'
        ]);
    }

    public function login()
    {
        $credential = request()->only(['username', 'password']);
        if (!auth()->validate($credential)) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'ไม่พบผู้ใช้ หรือ ข้อมูลที่กรอกไม่ถูกต้อง'
            ]);
        }
        $user = User::where('username', $credential['username'])->first();
        $user->tokens()->delete();
        $token = $user->createToken($_SERVER['HTTP_USER_AGENT'], [$user->role]);
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'token' => $token->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ]
        ], 200);
    }
}

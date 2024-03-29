<?php

namespace App\Http\Controllers;

use App\Helper\PageInfo;
use App\Helper\RoleBase;
use App\Models\Classroom;
use App\Models\UserAccess;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Helper\Constant;

class ClassroomController extends Controller
{

    use Constant;

    public function newClass(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }
        $classcode = Str::random(7);
        $classname = (string) $request->input('classname', '');
        $teacher_id = $user->username;
        $section = (int) $request->input('section', 1);
        $year =  $request->input('year', date('Y'));
        $semester = (int) $request->input('semester', 1);

        $classroom = new Classroom();
        $classroom->classcode = $classcode;
        $classroom->classname = $classname;
        $classroom->teacher = $teacher_id;
        $classroom->section = $section;
        $classroom->year = $year;
        $classroom->term = $semester;
        $result = $classroom->save();
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $userAccess = new UserAccess();
        $userAccess->username = $teacher_id;
        $userAccess->classcode = $classcode;
        $result = $userAccess->save();
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->insertClassIntoCache($user);

        Redis::setEx("classroom:$classcode", 3600 * 24, json_encode($classroom));

        return response()->json([
            'status' => true,
            'message' => 'สร้างชั้นเรียนสำเร็จ',
            'data' => [
                'classcode' => $classcode
            ]
        ], Response::HTTP_CREATED);
    }

    public function editClass(string $classcode, Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $classname = $request->input('classname', '');
        $section = (int) $request->input('section', 1);
        $year =  $request->input('year', date('Y'));
        $semester = (int) $request->input('semester', 1);

        $result = DB::table('classrooms')->where('classcode', $classcode)->update([
            'classname' => $classname,
            'section' => $section,
            'year' => $year,
            'term' => $semester
        ]);
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $classroom = DB::table('classrooms')->where('classcode', $classcode)->first() ?: null;

        if ($classroom) {
            Redis::setEx("classroom:$classcode", 3600 * 24, json_encode($classroom));
        }

        Redis::del(Redis::keys(
            "classroom:*:*"
        ));

        return response()->json([
            'status' => true,
            'message' => 'แก้ชั้นเรียนสำเร็จ',
            'data' => [
                'classcode' => $classcode
            ]
        ], Response::HTTP_CREATED);
    }

    public function joinClass(Request $request)
    {
        $classcode = $request->input('classcode', '');
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->isStudent($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $classroom = Classroom::where('classcode', $classcode)->first();
        if (!$classroom) {
            return response()->json([
                'status' => false,
                'message' => 'รหัสวิชาไม่ถูกต้อง'
            ], Response::HTTP_BAD_REQUEST);
        }
        $join = new UserAccess();
        if (UserAccess::where('username', $user->username)->where('classcode', $classcode)->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'เข้าชั้นเรียนนี้แล่้ว'
            ], Response::HTTP_BAD_REQUEST);
        }
        $join->username = $user->username;
        $join->classcode = $classcode;
        $result = $join->save();
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->insertClassIntoCache($user);
        return response()->json([
            'status' => true,
            'message' => 'เข้าชั้นเรียนสำเร็จ'
        ], Response::HTTP_CREATED);
    }

    public function listClass(Request $request)
    {
        $user = auth()->user();
        $roleBase = new RoleBase();
        if ($roleBase->isStudent($user)) {
            if (!Redis::get('classroom:student:' . $user->username)) {
                $access = $this->listClassQuery($user);
                Redis::setEx('classroom:student:' . $user->username, 3600 * 24, json_encode($access));
            }
            $access = json_decode(Redis::get('classroom:student:' . $user->username), true);
        } else if ($roleBase->isTeacher($user)) {
            if (!Redis::get('classroom:teacher:' . $user->username)) {
                $access = $this->listClassQuery($user);
                Redis::setEx('classroom:teacher:' . $user->username, 3600 * 24, json_encode($access));
            }
            $access = json_decode(Redis::get('classroom:teacher:' . $user->username));
        } else if ($roleBase->isAdmin($user)) {
            if (!Redis::get('classroom:superteacher:' . $user->username)) {
                $access = $this->listClassQuery($user);
                Redis::setEx('classroom:superteacher:' . $user->username, 3600 * 24, json_encode($access));
            }
            $access = json_decode(Redis::get('classroom:superteacher:' . $user->username));
        } else {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $pageInfo = new PageInfo();
        $page = $pageInfo->mapPage((int) $request->get('page', 1));
        $itemsPerPage = $pageInfo->itemPerPage();
        $total = count($access) ?: 0;

        return $pageInfo->pageInfo($page, $total, $itemsPerPage, $access);
    }

    // public function listByClassCode 

    private function insertClassIntoCache(\Illuminate\Contracts\Auth\Authenticatable|null $user): bool
    {
        $roleBase = new RoleBase();
        if ($roleBase->isStudent($user)) {
            $access = $this->listClassQuery($user);
            Redis::setEx('classroom:student:' . $user->username, 3600 * 24, json_encode($access));
        } else if ($roleBase->isTeacher($user)) {
            $access = $this->listClassQuery($user);
            Redis::setEx('classroom:teacher:' . $user->username, 3600 * 24, json_encode($access));
        } else if ($roleBase->isAdmin($user)) {
            $access = $this->listClassQuery($user);
            Redis::setEx('classroom:superteacher:' . $user->username, 3600 * 24, json_encode($access));
        } else {
            return false;
        }
        return true;
    }

    private function listClassQuery(\Illuminate\Contracts\Auth\Authenticatable|null $user)
    {
        return DB::table('user_access')->where('user_access.username', $user->username)->join('classrooms', 'user_access.classcode', '=', 'classrooms.classcode')->leftJoin('users', 'classrooms.teacher', '=', 'users.username')->where('user_access.is_delete', 0)->get(['users.username', 'user_access.classcode', 'classrooms.classname', 'users.name', 'classrooms.section', 'classrooms.year']);
    }

    public function classroomByClasscode(string $classcode)
    {
        $user = auth()->user();
        $access = DB::table('user_access')->where('username', $user->username)->where('classcode', $classcode)->first();
        if ($access === null) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึง',
            ], Response::HTTP_FORBIDDEN);
        }
        $classroom = DB::table('classrooms')->where('classcode', $classcode)->join('users', 'classrooms.teacher', '=', 'users.username')->first(['classrooms.classcode', 'classrooms.classname', 'classrooms.term', 'classrooms.section', 'classrooms.year', 'users.name']);
        if ($classroom === null) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบชั้นเรียนนี้',
            ], Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'status' => true,
            'message' => 'successful',
            'data' => $classroom,
        ], Response::HTTP_OK);
    }

    public function listUserByClasscode(string $classcode, Request $request)
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

        $classroom = DB::table('user_access')->where('classcode', $classcode)->offset(($page - 1) * $this->limit)->take($this->limit)->get('username')->toArray();
        $username = array_column($classroom, 'username') ?: [];
        $datas = DB::table($this->usertable)->whereIn('users.username', $username)->join('user_access', 'users.username', '=', 'user_access.username')->where('user_access.classcode', $classcode)->get([
            'users.username',
            'users.role',
            'users.status',
            'users.name',
            'user_access.is_delete'
        ])->toArray();
        $total = DB::table('user_access')->where('classcode', $classcode)->count() ?: 0;
        return $pageInfo->pageInfo($page, $total, $this->limit, $datas);
    }

    public function deleteUserInClassroom(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        $username = $request->input('username', '');
        $classcode = $request->input('classcode', '');

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

        $res = DB::table('user_access')->where('classcode', $classcode)->where('username', $username)->update([
            'is_delete' => true,
        ]);

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถลบได้'
            ]);
        }

        $this->flushCache($username);
        return response()->json([
            'status' => true,
            'msg' => 'ลบสำเร็จ',
        ]);
    }

    public function flushCache(string $username)
    {
        $user = DB::table('users')->where('username', $username)->first('role');
        $role = $user->role ?: 'student';
        Redis::del(Redis::keys(
            "classroom:" . $role . ":" . $username
        ));
    }

    public function joinTeacherClass(Request $request)
    {
        $classcode = $request->input('classcode', '');
        $username = $request->input('username', '');
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $classroom = Classroom::where('classcode', $classcode)->first();
        if (!$classroom) {
            return response()->json([
                'status' => false,
                'message' => 'รหัสวิชาไม่ถูกต้อง'
            ], Response::HTTP_BAD_REQUEST);
        }

        $teacherRole = DB::table('users')->where('username', $username)->first('role');
        if (!$teacherRole) {
            return response()->json([
                'status' => false,
                'message' => 'ผู้ใช้ไม่ถูกต้อง'
            ], Response::HTTP_BAD_REQUEST);
        }
        $teacherRole = $teacherRole->role != null ? $teacherRole->role : 'student';
        if ($teacherRole == 'student') {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'เพิ่มได้เฉพาะอาจารย์เท่านั้น'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $join = new UserAccess();
        if (UserAccess::where('username', $username)->where('classcode', $classcode)->count() > 0) {
            return response()->json([
                'status' => false,

                'message' => 'เข้าชั้นเรียนนี้แล่้ว'
            ], Response::HTTP_BAD_REQUEST);
        }
        $join->username = $username;
        $join->classcode = $classcode;
        $result = $join->save();
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $user->username = $username;
        $this->insertClassIntoCache($user);
        return response()->json([
            'status' => true,
            'message' => 'เข้าชั้นเรียนสำเร็จ'
        ], Response::HTTP_CREATED);
    }
}

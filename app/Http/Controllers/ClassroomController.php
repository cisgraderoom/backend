<?php

namespace App\Http\Controllers;

use App\Helper\RoleBase;
use App\Models\Classroom;
use App\Models\UserAccess;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ClassroomController extends Controller
{
    public function newClass(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if (!$rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ]);
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
        $classroom->teacher_id = $teacher_id;
        $classroom->section = $section;
        $classroom->year = $year;
        $classroom->semester = $semester;
        $result = $classroom->save();
        if (!$result) {
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้'
            ]);
        }

        $database_score = $classcode . '_score';
        $database_submission = $classcode . '_submission';
        $sql = 'CREATE TABLE ' . $database_score . ' ( username varchar(13) NOT NULL, PRIMARY KEY (username), FOREIGN KEY (username) REFERENCES users(username))';
        if (!DB::statement($sql)) {
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'ไม่สามารถสร้างตารางได้'
            ]);
        }
        $sql = 'CREATE TABLE ' . $database_submission . ' ( username varchar(13) NOT NULL, code TINYTEXT, result varchar(100) NOT NULL,score DECIMAL(3,2) NOT NULL,classcode varchar(7), PRIMARY KEY (username),problem_id varchar(8), FOREIGN KEY (username) REFERENCES users(username),FOREIGN KEY (classcode) REFERENCES classrooms(classcode),FOREIGN KEY (problem_id) REFERENCES problems(problem_id))';
        if (!DB::statement($sql)) {
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'ไม่สามารถสร้างตารางได้'
            ]);
        };

        return response()->json([
            'status' => Response::HTTP_CREATED,
            'message' => 'สร้างชั้นเรียนสำเร็จ',
            'data' => [
                'classcode' => $classcode
            ]
        ]);
    }

    public function joinClass(Request $request)
    {
        $classcode = $request->input('classcode', '');
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->isStudent($user)) {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ]);
        }

        $classroom = Classroom::where('classcode', $classcode)->first();
        if (!$classroom) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'รหัสวิชาไม่ถูกต้อง'
            ]);
        }
        $join = new UserAccess();
        if (UserAccess::where('username', $user->username)->where('classcode', $classcode)->count() > 0) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'เข้าชั้นเรียนนี้แล่้ว'
            ]);
        }
        $join->username = $user->username;
        $join->classcode = $classcode;
        $result = $join->save();

        DB::table($classcode . '_score')->insert(
            ['username' => $user->username]
        );

        if (!$result) {
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้',
            ]);
        }

        $this->insertClassIntoCache($user);
        return response()->json([
            'status' => Response::HTTP_CREATED,
            'message' => 'เข้าชั้นเรียนสำเร็จ'
        ]);
    }

    public function listClass()
    {
        $user = auth()->user();
        $roleBase = new RoleBase();
        if ($roleBase->isStudent($user)) {
            if (!Redis::get('classroom:student:' . $user->username) && $cache) {
                $classrooms = UserAccess::where('username', $user->username)->get();
                Redis::setEx('classroom:student:' . $user->username, 3600 * 24, json_encode($classrooms));
            }
            $classrooms = json_decode(Redis::get('classroom:student:' . $user->username));
        } else if ($roleBase->isTeacher($user)) {
            if (!Redis::get('classroom:teacher:' . $user->username) && $cache) {
                $classrooms = Classroom::where('teacher_id', $user->username)->get();
                Redis::setEx('classroom:teacher:' . $user->username, 3600 * 24, json_encode($classrooms));
            }
            $classrooms = json_decode(Redis::get('classroom:teacher:' . $user->username));
        } else if ($roleBase->isAdmin($user) && $cache) {
            if (!Redis::get('classroom:admin:' . $user->username) && $cache) {
                $classrooms = Classroom::all();
                Redis::setEx('classroom:admin:' . $user->username, 3600 * 24, json_encode($classrooms));
            }
            $classrooms = json_decode(Redis::get('classroom:admin:' . $user->username));
        } else {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'ไม่พบสิทธิการเข้าถึงส่วนนี้'
            ]);
        }

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'ดึงข้อมูลสำเร็จ',
            'data' => $classrooms ?? []
        ]);
    }

    private function insertClassIntoCache(\Illuminate\Contracts\Auth\Authenticatable|null $user): bool
    {
        $roleBase = new RoleBase();
        if ($roleBase->isStudent($user)) {
            $classrooms = UserAccess::where('username', $user->username)->get();
            Redis::setEx('classroom:student:' . $user->username, 3600 * 24, json_encode($classrooms));
        } else if ($roleBase->isTeacher($user)) {
            $classrooms = Classroom::where('teacher_id', $user->username)->get();
            Redis::setEx('classroom:teacher:' . $user->username, 3600 * 24, json_encode($classrooms));
        } else if ($roleBase->isAdmin($user) && $cache) {
            $classrooms = Classroom::all();
            Redis::setEx('classroom:admin:' . $user->username, 3600 * 24, json_encode($classrooms));
        } else {
            return false;
        }
        return true;
    }
}

<?php

namespace App\Http\Controllers;

use App\Helper\RoleBase;
use App\Models\Classroom;
use App\Models\UserAccess;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    public function newClass(Request $request)
    {
        $rolebase = new RoleBase();
        $user = auth()->user();
        if ($rolebase->isTeacher($user)) {
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
            'status' => Response::HTTP_OK,
            'message' => 'สร้างชั้นเรียนสำเร็จ'
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
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'เพิ่มชั้นเรียนสำเร็จ'
        ]);
    }
}

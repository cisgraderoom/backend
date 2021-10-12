<?php

namespace App\Http\Controllers;

use App\Helper\RoleBase;
use App\Models\Classroom;
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

        // $database_score = $classcode . '_score';
        // $sql = 'CREATE TABLE' . $database_score . ' ( username varchar(13) NOT NULL, PRIMARY KEY (username), FOREIGN KEY (username) REFERENCES users(username))';
        // DB::statement($sql, [$database_score]);

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'บันทึกข้อมูลเรียบร้อย'
        ]);
    }
}

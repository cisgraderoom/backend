<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use App\Helper\PageInfo;
use App\Helper\RoleBase;
use App\Http\Jobs\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class SubmissionController extends Controller
{
    use Constant;

    public function submit(int $id, Request $request)
    {
        $user = auth()->user();
        $classcode  = $request->input('classcode', '');
        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $open = date('c', time());
        $data = DB::table($this->problemTable)->where('classcode', $classcode)->where('close_at', '>=', $open)->orWhere('close_at', null)->where('open_at', '<=', $open)->where('is_hidden', false)->where('is_delete', false)->where('problem_id', $id)->first();
        if (!$data) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถส่งงานได้ขณะนี้'
            ]);
        }

        if (!isset($_FILES['code'])) {
            return response()->json([
                'status' => false,
                'msg' => 'กรุณาแนบไฟล์'
            ]);
        }

        $extension = explode('.', $_FILES['code']['name'])[1];
        if (!in_array($extension, ['c', 'cpp', 'java'])) {
            return response()->json([
                'status' => false,
                'msg' => 'นามสกุลไฟล์ไม่ถูกต้อง'
            ]);
        }

        $code = file_get_contents($_FILES['code']['tmp_name']);

        $username = $user->username;
        $code_target_dir = storage_path("sourcecode/");
        $code_target_file = $code_target_dir . "{$id}_${username}." . $extension;
        move_uploaded_file($_FILES['code']['tmp_name'], $code_target_file);

        $result = "Queue";

        $res = DB::table($this->submissionTable)->insertGetId([
            'username' => $user->username,
            'classcode' => $classcode,
            'problem_id' => $id,
            'code' => $code,
            'lang' => $extension,
            'result' => $result,
            'created_at' => date('Y-m-d:h:i:s', time()),
        ]);

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถส่งงานได้ขณะนี้'
            ]);
        }

        DB::table($this->scoreTable)->insertOrIgnore([
            'username' => $user->username,
            'classcode' => $classcode,
            'problem_id' => $id,
        ]);

        $this->Judge([
            'mode' => 'new',
            'language' => $extension,
            'code' => $code,
            'time_limit' => $data->time_limit,
            'mem_limit' => $data->mem_limit * 1000,
            'problem_id' => $id,
            'username' => $user->username,
            'max_score' => $data->max_score,
            'submission_id' => $res,
            'classcode' => $classcode,
        ]);

        return response()->json([
            'status' => true,
            'msg' => 'ส่งงานสำเร็จ'
        ]);
    }

    public function scoreByProblemId(string $classcode, int $id)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $res = DB::table($this->submissionTable)->where('classcode', $classcode)->where('problem_id', $id)->where('username', $user->username)->latest()->first();
        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่พบการส่งงาน'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => $res->result == 'Queue' ? 'กำลังตรวจ' : 'ตรวจสำเร็จ',
            'state' => $res->result == 'Queue' ? false : true,
            'data' => $res,
            'array_result' => $res->result != 'Queue' ? str_split($res->result) : []
        ]);
    }

    public function scoreByClassroom(string $classcode)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        $score = [];
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isStudent($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }


        $res = DB::table($this->scoreTable)->where('score.classcode', $classcode)->join($this->usertable, 'score.username', '=', 'users.username')->join($this->problemTable, 'score.problem_id', '=', 'problems.problem_id')->get([
            'users.username',
            'users.name',
            'score.problem_id',
            'problems.problem_name',
            'score.score',
        ])->toArray() ?: [];

        foreach ($res as $_ => $item) {
            $item = (array)$item;
            $score[$item['problem_id']][] = $item;
        }

        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถดึงคะแนนได้ขณะนี้'
            ]);
        }

        return response()->json([
            'status' => true,
            'msg' => 'ดึงคะแนนสำเร็จ',
            'data' => $score,
        ]);
    }

    public function getListSubmission(string $classcode, int $problem, Request $request)
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

        $datas = DB::table($this->submissionTable)->where('classcode', $classcode)->where('problem_id', $problem)->offset(($page - 1) * $this->limit)->take($this->limit)->get()->toArray() ?: [];
        $total = DB::table($this->submissionTable)->where('classcode', $classcode)->where('problem_id', $problem)->count() ?: 0;
        return $pageInfo->pageInfo($page, $total, $this->limit, $datas);
    }


    public function scoreByUser(string $classcode)
    {
        $user = auth()->user();
        $rolebase = new RoleBase();
        if (!$rolebase->checkUserHasPermission($user, $classcode) || $rolebase->isTeacherOrAdmin($user)) {
            return response()->json([
                'status' => false,
                'message' => 'คุณไม่มีสิทธิในส่วนนี้'
            ], Response::HTTP_FORBIDDEN);
        }

        $problem = DB::table($this->problemTable)->where('classcode', $classcode)->where('is_delete', false)->get('problem_id')->toArray();
        $problem  = array_column($problem, 'problem_id') ?: [];

        $res = DB::table($this->scoreTable)->where('classcode', $classcode)->whereIn('problem_id', $problem)->where('username', $user->username)->get();
        if (!$res) {
            return response()->json([
                'status' => false,
                'msg' => 'ไม่สามารถส่งงานได้ขณะนี้'
            ]);
        }

        return response()->json([
            'status' => false,
            'data' => $res
        ]);
    }


    public function Judge(array $data)
    {

        $input = [];
        $output = [];
        $res = DB::table('problems')->where('problem_id', $data['problem_id'])->first();
        if (!$res) {
            return false;
        }
        for ($i = 1; $i <= $res->testcase; $i++) {
            array_push($input, preg_replace("/<br\W*?\/>/", "\n", file_get_contents(storage_path('testcase/' . $data['problem_id'] . '/' . $i . '.in'))));
            array_push($output, preg_replace("/<br\W*?\/>/", "\n", file_get_contents(storage_path('testcase/' . $data['problem_id'] . '/' . $i . '.out'))));
        }
        $data['input'] = $input;
        $data['output'] = $output;
        $data['testcase'] = $res->testcase;
        $connection = new AMQPStreamConnection('127.0.0.1', 5672, 'cisgraderoomcloud', 'cisgraderoom');
        $channel = $connection->channel();
        $channel->exchange_declare('cisgraderoom.judge', 'topic', false, true, false);
        $channel->queue_declare('cisgraderoom.judge.result', false, true, false, false);
        $msg = new AMQPMessage(json_encode($data));
        $channel->basic_publish($msg, 'cisgraderoom.judge', 'cisgraderoom.judge.result.*');
        $channel->close();
        $connection->close();
    }


    public function NewJudgeAndPlagiarism(string $classcode, string $mode, int $id)
    {
        $rolebase = new RoleBase();
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
        $res = DB::table('problems')->where('problem_id', $id)->first();
        if (!$res) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถใช้งานได้'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $check = DB::table('jobs')->where('classcode', $classcode)->where('username', $user->username)->orderBy('id', 'desc')->first();
        if ($check) {
            if (!$check->status) {
                return response()->json([
                    'status' => false,
                    'message' => 'คุณมีงานที่กำลังทำอยู่หลังบ้านกรุณารอสักครู่'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($mode === 'judge') {
            $job = DB::table("jobs")->insertGetId([
                'username' => $user->username,
                'classcode' => $classcode,
                'key' => 'J',
            ]);
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'ไม่สามารถส่งตรวจได้'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $sql = "SELECT a.username,a.code,a.lang FROM submission a INNER JOIN ( SELECT username, MAX(created_at) created_at FROM submission WHERE problem_id = ${id}  GROUP BY username) b ON a.username = b.username AND a.created_at = b.created_at";
            $query = DB::select(DB::raw($sql));
            foreach ($query as $data) {
                $this->Judge([
                    'mode' => 'again',
                    'language' => $data->lang,
                    'username' => $res->username,
                    'code' => $data->code,
                    'time_limit' => $res->time_limit,
                    'mem_limit' => $res->mem_limit * 1000,
                    'problem_id' => $id,
                    'username' => $data->username,
                    'max_score' => $res->max_score,
                    'classcode' => $classcode,
                    'submission_id' => rand(100000, 999999),
                    'job_id' => $job,
                ]);
            }
        }

        $this->Judge([
            'mode' => 'success',
            'job_id' => $job,
            'language' => '',
            'username' => '',
            'code' => '',
            'time_limit' => 1,
            'mem_limit' => 2000,
            'problem_id' => 1,
            'username' => '',
            'max_score' => 0.00,
            'classcode' => '',
            'submission_id' => rand(100000, 999999),
            'job_id' => $job,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'ระบบกำลังทำรายการตรวจสอบข้อสอบนี้'
        ], Response::HTTP_OK);
    }
}

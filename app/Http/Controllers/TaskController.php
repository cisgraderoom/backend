<?php

namespace App\Http\Controllers;

use App\Helper\Constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{

    use Constant;

    public function newTask(Request $request)
    {

        $user = auth()->user();
        $problemName = $request->input('problemName', '');
        $problemDesc = $request->input('problemDesc', '');
        $score = $request->input('score', 0);
        $type  = $request->input('type', 'manual');
        $classcode  = $request->input('classcode');
        $openAt = $request->input('open', date('Y-m-d h:i:s'));
        $closeAt = $request->input('close');
        $files = $request->hasFile('asset');
        // if ($files) {
        // foreach ($request->file('asset') as $file) {
        //     $path = $this->assetpath;
        //     // $extension = $file->extension();
        //     $clientOriginalName = $file->getClientOriginalName();
        //     $newFileName = time() . "_" . $clientOriginalName;
        //     $file->move($path, $newFileName);
        //     Storage::putFileAs($path, $files, $newFileName);
        // }
        // }


        // if (isset($_FILES['testcase'])) {
        // }

        // $result = DB::table('problem')
        return response()->json([
            'status' => true,
            'msg' => 'สร้างโจทย์สำเร็จ'
        ]);
    }
}

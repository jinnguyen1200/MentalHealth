<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 12/10/2017
 * Time: 14:27
 */

namespace MentalHealthAI\Http\Controllers;


use MentalHealthAI\Models\IndustrialPhysicianCommentHistory;
use MentalHealthAI\Repositories\Interfaces\IIndustrialPhysicianCommentHistoryRepository;
use MentalHealthAI\User;
use Illuminate\Http\Request;

class IndustrialPhysicianCommentHistoryController extends Controller
{
    protected $employeeRepository;
    protected $industrialPhysicianCommentHistory;

    public function __construct(IIndustrialPhysicianCommentHistoryRepository $industrialPhysicianCommentHistoryRepository)
    {
        $this->industrialPhysicianCommentHistory = $industrialPhysicianCommentHistoryRepository;
        $this->middleware('auth');
        $this->middleware('roles:' . User::DOCTOR . ',' . User::COMPANY);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $interviewDay = $request['txtDay'];
        $comment = $request['txtComment'];
        $employeeID = $request['txtEmpID'];
        $messageField = '';
        $flagField = false;
        if($comment == '' || count($comment) == 0){
            $messageField = 'Comment';
        }
        if($interviewDay == '' || count($interviewDay) == 0){
            $messageField = 'Interview day';
        }
        if($flagField){
            return response()->json([
                'result' => false,
                'message' => 'Field required'
            ]);
        }
        if(strlen($comment) > 1000){
            return response()->json([
                'result' => false,
                'message' => 'コメントメッセージは1000文字未満でなければなりません。'
            ]);
        }

        $industrialPhysicianCommentHistory = new IndustrialPhysicianCommentHistory();
        $industrialPhysicianCommentHistory['employee_id'] = $employeeID;
        $industrialPhysicianCommentHistory['comment'] = $comment;
        $industrialPhysicianCommentHistory['interview_date'] = $interviewDay;
        $industrialPhysicianCommentHistory['registration_date'] = $interviewDay;

        $notification = $this->industrialPhysicianCommentHistory->create($industrialPhysicianCommentHistory);
        if ($notification) {
            return response()->json([
                'result' => true,
                'message' => '登録が完了しました。'
            ]);
        } else {
            return response()->json([
                'result' => false,
                'message' => 'アップロードに失敗しました。'
            ]);
        }
    }

    /**
     * Edit data of a comment message
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request){
        $txtID = $request['txtID'];
        $interviewDay = $request['txtDay'];
        $comment = $request['txtComment'];
        $employeeID = $request['txtEmpID'];
        $messageField = '';
        $flagField = false;
        if($comment == '' || count($comment) == 0){
            $messageField = 'Comment';
        }
        if($interviewDay == '' || count($interviewDay) == 0){
            $messageField = 'Interview day';
        }
        if($flagField){
            return response()->json([
                'result' => false,
                'message' => 'Field required'
            ]);
        }
        if(strlen($comment) > 1000){
            return response()->json([
                'result' => false,
                'message' => 'コメントメッセージは1000文字未満でなければなりません。'
            ]);
        }

        $industrialPhysicianCommentHistory = new IndustrialPhysicianCommentHistory();
        $industrialPhysicianCommentHistory['employee_id'] = $employeeID;
        $industrialPhysicianCommentHistory['comment'] = $comment;
        $industrialPhysicianCommentHistory['interview_date'] = $interviewDay;
        $industrialPhysicianCommentHistory['registration_date'] = $interviewDay;

        $notification = $this->industrialPhysicianCommentHistory->update($industrialPhysicianCommentHistory, $txtID);
        if ($notification) {
            return response()->json([
                'result' => true,
                'message' => '登録が完了しました。'
            ]);
        } else {
            return response()->json([
                'result' => false,
                'message' => 'アップロードに失敗しました。'
            ]);
        }
    }
}
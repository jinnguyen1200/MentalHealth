<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 12/10/2017
 * Time: 14:25
 */
namespace MentalHealthAI\Repositories\Implementations;

use MentalHealthAI\Models\IndustrialPhysicianCommentHistory;
use MentalHealthAI\Repositories\Interfaces\IIndustrialPhysicianCommentHistoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class IndustrialPhysicianCommentHistoryRepository implements IIndustrialPhysicianCommentHistoryRepository
{

    public function create(IndustrialPhysicianCommentHistory $industrialPhysicianCommentHistory)
    {
        return $industrialPhysicianCommentHistory->save();
    }

    public function update(IndustrialPhysicianCommentHistory $industrialPhysicianCommentHistory, $id)
    {
        return IndustrialPhysicianCommentHistory::where('id', '=', $id)->update($industrialPhysicianCommentHistory->toArray());
    }

    public function getCommentList($id)
    {
        $list = DB::table('industrial_physician_comment_history')
        ->select('industrial_physician_comment_history.*')
            ->where('employee_id', '=', $id)
            ->orderBy('industrial_physician_comment_history.interview_date', 'desc')
            ->orderBy('industrial_physician_comment_history.updated_at', 'desc')
            ->paginate(Config::get('constants.pagination_limit'));
        return $list;
    }
}
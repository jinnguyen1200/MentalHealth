<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 12/10/2017
 * Time: 14:25
 */

namespace MentalHealthAI\Repositories\Interfaces;

use MentalHealthAI\Models\IndustrialPhysicianCommentHistory;
interface IIndustrialPhysicianCommentHistoryRepository
{
    public function create(IndustrialPhysicianCommentHistory $industrialPhysicianCommentHistory);

    public function update(IndustrialPhysicianCommentHistory $industrialPhysicianCommentHistory, $id);

    public function getCommentList($id);
}
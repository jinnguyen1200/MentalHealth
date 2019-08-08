<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/4/17
 * Time: 2:29 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\Factor;

interface IFactorRepository
{
    public function getAll($group, $factorId);

    public function get($id);

    public function delete($id);

    public function create(Factor $factor);

    public function update(Factor $factor);

}
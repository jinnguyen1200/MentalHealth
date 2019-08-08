<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 8/31/17
 * Time: 2:36 PM
 */

namespace MentalHealthAI\Repositories\Interfaces;

use MentalHealthAI\Models\SystemAdministrator;

interface ISystemAdministratorRepository
{
    public function search($name);

    public function getAll();

    public function save(SystemAdministrator $admin);

}
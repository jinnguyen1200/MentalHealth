<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 8/31/17
 * Time: 2:38 PM
 */

namespace MentalHealthAI\Repositories\Implementations;

use Illuminate\Support\Facades\Config;
use MentalHealthAI\Repositories\Interfaces\ISystemAdministratorRepository;
use MentalHealthAI\Models\SystemAdministrator;

class SystemAdministratorRepository implements ISystemAdministratorRepository {

    public function search($mailAddress)
    {
        return SystemAdministrator::where('mail_address', 'LIKE', '% ' . $mailAddress . '%')
            -> get();
    }

    public function getAll()
    {
        return SystemAdministrator::paginate(Config::get('constants.pagination_limit'));
    }

    public function save(SystemAdministrator $admin)
    {
        $admin -> save();
    }

}
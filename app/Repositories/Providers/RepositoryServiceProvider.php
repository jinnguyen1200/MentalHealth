<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 9/25/17
 * Time: 4:10 PM
 */

namespace MentalHealthAI\Repositories\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(){

        $models = [
            'SystemAdministrator',
            'Employee',
            'Department',
            'Office',
            'Company',
            'Administrator',
            'Factor',
            'FactorSettingValue',
            'Attendance',
            'HealthCheck',
            'StressCheck',
            'IndustrialPhysicianCommentHistory',
            'CalculationPoint',
            'UserCompany',
            'UserOffice'
        ];

        foreach ($models as $model) {
            $this->app->bind(
                "MentalHealthAI\Repositories\Interfaces\\I{$model}Repository",
                "MentalHealthAI\Repositories\Implementations\\{$model}Repository"
            );
        }

//        $this->app->bind(
//            'MentalHealthAI\Repositories\Interfaces\ISystemAdministratorRepository',
//            // To change the data source, replace this class name
//            // with another implementation
//            'MentalHealthAI\Repositories\Implementations\SystemAdministratorRepository'
//        );
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IEmployeeRepository',
//            'MentalHealthAI\Repositories\Implementations\EmployeeRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IDepartmentRepository',
//            'MentalHealthAI\Repositories\Implementations\DepartmentRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IOfficeRepository',
//            'MentalHealthAI\Repositories\Implementations\OfficeRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\ICompanyRepository',
//            'MentalHealthAI\Repositories\Implementations\CompanyRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IAdministratorRepository',
//            'MentalHealthAI\Repositories\Implementations\AdministratorRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IFactorRepository',
//            'MentalHealthAI\Repositories\Implementations\FactorRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IFactorSettingValueRepository',
//            'MentalHealthAI\Repositories\Implementations\FactorSettingValueRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IAttendanceRepository',
//            'MentalHealthAI\Repositories\Implementations\AttendanceRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IHealthCheckRepository',
//            'MentalHealthAI\Repositories\Implementations\HealthCheckRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IStressCheckRepository',
//            'MentalHealthAI\Repositories\Implementations\StressCheckRepository');
//
//        $this->app->bind('MentalHealthAI\Repositories\Interfaces\IIndustrialPhysicianCommentHistoryRepository',
//            'MentalHealthAI\Repositories\Implementations\IndustrialPhysicianCommentHistoryRepository');

        //Add more here...
    }
}
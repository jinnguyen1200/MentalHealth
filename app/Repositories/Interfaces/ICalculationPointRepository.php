<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/26/17
 * Time: 10:27 AM
 */

namespace MentalHealthAI\Repositories\Interfaces;


use MentalHealthAI\Models\CalculationPoint;

interface ICalculationPointRepository
{
    public function get($id);

    public function getAll();

    public function save(CalculationPoint $calculationPoint);

    public function delete($id);

    public function deleteByPeriod($period);

    public function insertBloodPressurePoint($period, $companyId);

    public function insertAbsentPoint($period, $companyId);

    public function insertLateArrivalPoint($period, $companyId);

    public function insertOTPerMonthPoint($period, $companyId);

    public function insertMondayAbsentPoint($period, $companyId);

    public function insertMondayLatePoint($period, $companyId);

    public function insertStressCheckPoint($period, $companyId);

    public function insertIndustryPoint($period, $companyId);

    public function insertAdjustmentPoint($period, $companyId);

    public function insertNumberOfEmployeePoint($period, $companyId);

    public function deleteZeroPoints();
}
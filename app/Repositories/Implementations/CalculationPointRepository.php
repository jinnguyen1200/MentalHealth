<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/26/17
 * Time: 10:47 AM
 */

namespace MentalHealthAI\Repositories\Implementations;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MentalHealthAI\Models\CalculationPoint;
use MentalHealthAI\Repositories\Interfaces\ICalculationPointRepository;
use MentalHealthAI\Repositories\Interfaces\IFactorSettingValueRepository;

class CalculationPointRepository implements ICalculationPointRepository
{
    protected $factorSettingValueRepository;

    /**
     * Class constructor
     *
     * @param FactorSettingValueRepository $factorSettingValueRepository
     */
    public function __construct(IFactorSettingValueRepository $factorSettingValueRepository)
    {
        $this->factorSettingValueRepository = $factorSettingValueRepository;
    }

    public function get($id)
    {
        return CalculationPoint::where('id', '=', $id);
    }

    public function getAll()
    {
        return CalculationPoint::where('valid_flag', '=', 1);
    }

    public function save(CalculationPoint $calculationPoint)
    {
        $calculationPoint->save();
    }

    public function delete($id)
    {
        $calculationPoint = $this->get($id);
        $calculationPoint['valid_flag'] = 0;
        $calculationPoint->save();
    }

    public function deleteByPeriod($period)
    {
        $sql = "delete from calculation_point
                    where id in (select t_p.id from calculation_point t_p
                      Inner join employee t_e
                                    On t_e.id = t_p.employee_id
                                    Inner join department t_d
                                    On t_d.id = t_e.department_id
                                    Inner join office t_o
                                    On t_o.id = t_d.office_id
                                    Inner join company t_c
                                    On t_c.id = t_o.company_id
                                    where t_p.period = '".$period."'
                                    and t_c.id = ".Auth::user()->company_id." );";
        DB::select($sql);
    }

    public function insertBloodPressurePoint($period, $companyId)
    {
        $selectMaxSQL = "select max(period) as maxperiod from health_check t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    where t_h.period <= '".$period."'
                    and t_h.valid_flag = 1;";


        $maxPeriod = DB::select($selectMaxSQL)[0]->maxperiod;
        if (!$maxPeriod){
            $maxPeriod = $period;
        }



        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)
                    SELECT nextval('calculation_point_id_seq'),
                    		t_e.id, 
                            9,
                            CASE 
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(9);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN coalesce(max_blood_pressure_2nd, max_blood_pressure_1st) >= '.$factor['upper_limit'].' THEN '.$factor['point'].PHP_EOL;
        }

        $sql.="             ELSE 10
                            END as blood_pressure, 
                            '".$period."',
                            NOW()::timestamp(0),'".
                            Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From health_check t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    WHERE t_h.valid_flag = 1
                    and t_h.period = '".$maxPeriod."';";


        return DB::select($sql);
    }

    public function insertAbsentPoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            2, -- factor_id Absent work point
                            CASE 
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(2);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_h.absence_num >= '.$factor['upper_limit'].' THEN '.$factor['point'].PHP_EOL;
        }
        $sql.="             ELSE 0
                            END as absence_point, 
                            '".$period."',
                            NOW()::timestamp(0),'".
                            Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From attendance t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and 
                    t_h.period ='".$period."';";


        return DB::select($sql);
    }

    public function insertLateArrivalPoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            1, -- factor_id Late arrival point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(1);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_h.late_times >= '.$factor['upper_limit'].' THEN '.$factor['point'].PHP_EOL;
        }
        $sql.="             ELSE 0
                            END as late_point,  
                            '".$period."',
                            NOW()::timestamp(0),'"
                            .Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From attendance t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and 
                    t_h.period = '".$period."';";


        return DB::select($sql);
    }

    public function insertOTPerMonthPoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            8, -- actor_id Overtime hours point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(8);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_h.overtime_hours >= '.$factor['upper_limit'].' THEN '.$factor['point'].PHP_EOL;
        }
        $sql.="             ELSE 0
                            END as overtime_hours,  
                            '".$period."',
                            NOW()::timestamp(0),'"
                            .Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From attendance t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and 
                    t_h.period = '".$period."';";


        return DB::select($sql);
    }

    public function insertMondayAbsentPoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            10, -- actor_id Overtime hours point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(10);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_h.monday_absence >= '.$factor['upper_limit'].' THEN '.$factor['point'].PHP_EOL;
        }
        $sql.="             ELSE 0
                            END as monday_absence,  
                            '".$period."',
                            NOW()::timestamp(0),'"
                            .Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From attendance t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and 
                    t_h.period = '".$period."';";


        return DB::select($sql);
    }

    public function insertMondayLatePoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            3, -- actor_id Overtime hours point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(3);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_h.monday_late_arrival >= '.$factor['upper_limit'].' THEN '.$factor['point'].PHP_EOL;
        }
        $sql.="             ELSE 0
                            END as monday_late_arrival,  
                            '".$period."',
                            NOW()::timestamp(0),'"
            .Auth::user()->email.
            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From attendance t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and 
                    t_h.period = '".$period."';";


        return DB::select($sql);
    }

    public function insertStressCheckPoint($period, $companyId)
    {
        $selectMaxSQL = "select max(period) as maxperiod from stress_check t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    where t_h.period <= '".$period."'
                    and t_h.valid_flag = 1;";


        $maxPeriod = DB::select($selectMaxSQL)[0]->maxperiod;
        if (!$maxPeriod){
            $maxPeriod = $period;
        }

        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            11, -- actor_id Overtime hours point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(11);
        if (count($factors) == 0){
            return;
        }

        $sql .= 'WHEN t_h.is_high_stress_judgement = true THEN '.$factors[1]['point'].PHP_EOL;
        $sql.="             ELSE 0
                            END as is_high_stress_judgement,  
                            '".$period."',
                            NOW()::timestamp(0),'"
            .Auth::user()->email.
            "',NOW()::timestamp(0),
                            '高ストレス',
                            NULL,
                            NULL,
                            1
                    From stress_check t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and t_h.period = '".$maxPeriod."';";

        $sql2 = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            11, -- actor_id Overtime hours point
                            CASE
                            ";
        $sql2 .= 'WHEN t_h.is_present = true THEN '.$factors[0]['point'].PHP_EOL;
        $sql2.="             ELSE 0
                            END as is_high_stress_judgement,  
                            '".$period."',
                            NOW()::timestamp(0),'"
            .Auth::user()->email.
            "',NOW()::timestamp(0),
                            'ストレスチェック受診していない',
                            NULL,
                            NULL,
                            1
                    From stress_check t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department d
                    ON d.id = t_e.department_id
                    Inner join office o 
                    On o.id = d.office_id
                    Inner join company c
                    On c.id = o.company_id AND c.id = ".$companyId."
                    Where t_h.valid_flag = 1
                    and t_h.period = '".$maxPeriod."';";


        DB::select($sql);
        return DB::select($sql2);
    }

    public function insertIndustryPoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            6, -- factor_id Late arrival point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(6);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_c.industry_id = '.$factor['upper_limit'].' THEN t_h.total_point * '.$factor['point'].'/100 - t_h.total_point'.PHP_EOL;
        }

        $sql.="             ELSE 0
                            END as late_point, 
                            '".$period."',
                            NOW()::timestamp(0),'"
                            .Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From (SELECT employee_id, sum(point) as total_point
                          from calculation_point
                          where period = '".$period."'
                          group by employee_id) t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department t_d
                    On t_d.id = t_e.department_id
                    Inner join office t_o
                    On t_o.id = t_d.office_id
                    Inner join company t_c
                    On t_c.id = t_o.company_id AND t_c.id = ".$companyId.";";

        return DB::select($sql);
    }

    public function insertAdjustmentPoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            999,
                            t_h.total_point * (t_c.point_factor/100 * t_o.point_factor/100 * t_d.point_factor/100)  - t_h.total_point, 
                            '".$period."',
                            NOW()::timestamp(0),'"
                            .Auth::user()->email.
                            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From (SELECT employee_id, sum(point) as total_point
                          from calculation_point
                          where period = '".$period."'
                          group by employee_id) t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department t_d
                    On t_d.id = t_e.department_id
                    Inner join office t_o
                    On t_o.id = t_d.office_id
                    Inner join company t_c
                    On t_c.id = t_o.company_id AND t_c.id = ".$companyId.";";

        return DB::select($sql);
    }

    public function insertNumberOfEmployeePoint($period, $companyId)
    {
        $sql = "INSERT INTO calculation_point(
                    id, employee_id, factor_id, point, period, created_at, created_by, updated_at, note_1, note_2, note_3, valid_flg)    
                    SELECT nextval('calculation_point_id_seq'),
                            t_e.id, 
                            7, -- factor_id Late arrival point
                            CASE
                            ";

        $factors = $this->factorSettingValueRepository->getByFactorId(7);
        if (count($factors) == 0){
            return;
        }
        foreach ($factors as $factor){
            $sql .= 'WHEN t_count.count_emp >= '.$factor['upper_limit'].' THEN t_h.total_point * '.$factor['point'].'/100 - t_h.total_point'.PHP_EOL;
        }

        $sql.="             ELSE 0
                            END as late_point, 
                            '".$period."',
                            NOW()::timestamp(0),'"
            .Auth::user()->email.
            "',NOW()::timestamp(0),
                            NULL,
                            NULL,
                            NULL,
                            1
                    From (SELECT employee_id, sum(point) as total_point
                          from calculation_point
                          where period = '".$period."'
                          group by employee_id) t_h
                    Inner join employee t_e
                    On t_e.id = t_h.employee_id
                    Inner join department t_d
                    On t_d.id = t_e.department_id
                    Inner join office t_o
                    On t_o.id = t_d.office_id
                    Inner join company t_c
                    On t_c.id = t_o.company_id AND t_c.id = ".$companyId."
                    Left join (select t_o1.id, count(e.id) as count_emp
                        from office t_o1
                        inner join department d
                        on d.office_id = t_o1.id
                        inner join employee e
                        on e.department_id = d.id 
                        where 1=1
                        and e.valid_flag = 1
                        and e.entry_ym <= '".$period."'
                        group by t_o1.id) t_count
                    ON t_count.id = t_o.id";

//dd($sql);
        return DB::select($sql);
    }

    protected function getBooleanFromInt($int){
        return $int == 0 ? 'FALSE' : 'TRUE';
    }

    public function deleteZeroPoints(){
        $sql = "delete from calculation_point
                    where point = 0;";
        DB::select($sql);
    }


}
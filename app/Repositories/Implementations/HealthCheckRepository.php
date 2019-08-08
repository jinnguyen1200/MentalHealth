<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/10/17
 * Time: 4:16 PM
 */

namespace MentalHealthAI\Repositories\Implementations;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use MentalHealthAI\Models\HealthCheck;
use MentalHealthAI\Models\UploadHistory;
use MentalHealthAI\Repositories\Interfaces\IHealthCheckRepository;
use Mockery\Exception;

class HealthCheckRepository implements IHealthCheckRepository
{

    public function search($name)
    {
        // TODO: Implement search() method.
    }

    public function get($id)
    {
        // TODO: Implement get() method.
    }

    public function getAll($company_id, $office_id, $department_id)
    {
        $list = DB::table('health_check')
            ->select('health_check.employee_id as em','health_check.period as pe','health_check.health_check_id as id')
            ->leftJoin('employee','employee.id','=','health_check.employee_id')
            ->leftJoin('department','employee.department_id','=','department.id')
            ->leftJoin('office','department.office_id','=','office.id')
            ->leftJoin('company','office.company_id','=','company.id')
            ->where('company.id','=',$company_id)
            ->where('health_check.valid_flag','=',1)
            ->where(function ($query) use ($office_id, $department_id) {
                if ($office_id !== 0) {
                    $query->where('office.id', '=', $office_id);
                }
                if ($department_id !== 0) {
                    $query->where('department.id', '=', $department_id);
                }
            })
            ->get();
        return $list;
    }

    public function save(HealthCheck $healthCheck)
    {
       $healthCheck->save();
    }


    public function saveHealthCheckFromFile($healthCheckFile, $employeeList, $currentList)
    {
        try{
            DB::beginTransaction();
            if (is_string($healthCheckFile)){
                return "File name error!";
            }
            $path = $healthCheckFile->getRealPath();
            $name = $healthCheckFile->getClientOriginalName();
            if (!is_csv($name)) {
                return "File name error!";
            }
            $splitedFileName = preg_split( "/[_.]/", $name );
            $period = substr($splitedFileName[1], 0, 6) ;
            $file_type = current($splitedFileName);
            if ($splitedFileName[0] !== "HealthCheck") {
                return "適切なファイルをインポートしてください。例えば　HealthCheck_201701.csv";
            }
            $oldUploadHitory =
                UploadHistory::where('file_type', $file_type)
                    ->where('period','=', $period)
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->where('office_id', '=', Auth::user()->office_id) // TODO recheck
                    ->where('department_id', '=', Auth::user()->department_id) // TODO recheck
                    ->where('valid_flag', '=', 1)->first();
            if ($oldUploadHitory){
//                $this->deleteAllByUploadFileID($oldUploadHitory->id);
                $oldUploadHitory['valid_flag'] = 0;
                $oldUploadHitory->save();
            }


            $uh = new UploadHistory();
            $uh['file_name'] = $name;
            $uh['file_type'] = $file_type;
            $uh['file_location'] = $path;
            $uh['status'] = 0;
            $uh['error_log'] = '';
            $uh['period'] = $period;
            $uh['company_id'] = Auth::user()->company_id;
            $uh['office_id'] = Auth::user()->office_id;
            $uh['department_id'] = Auth::user()->department_id;
            $uh->save();



            $data = Excel::load($path, function ($reader) {
                $reader->noHeading();
            })->get();

            $i = 0;
            if (!empty($data) && $data->count()) {
                $dataArray = $data->toArray();
                foreach ($dataArray as $row) {
                    if (!empty($row)) {

                        if (array_search($row, $dataArray) == 0) {
                            continue;
                        }//Skip header
                        $i++;
                        if(array_search($row[1], array_column($employeeList, 'em')) === false){
                            throw new Exception('従業員コードが存在しません。 '.$i.'行目を確認してください。');
                        }

//                        $niceNames = array(
//                            '0' => 'company_code',
//                            '1' => 'office_code',
//                            '2' => 'department_code',
//                            '3' => 'employee_code',
//                            '4' => 'birthdate',
//                            '5' => 'position',
//                            '6' => 'new_graduate_midway',
//                            '7' => 'is_absence',
//                            '8' => 'is_retirement',
//                            '9' => 'entry_ym',
//                            '10' => 'gender'
//                        );
//
//                        $validation = Validator::make($row, [
//                            '0' => 'nullable|integer',
//                            '1' => 'nullable|integer',
//                            '2' => 'nullable|integer',
//                            '3' => 'required|alpha_num',
//                            '4' => 'nullable|date_format:'.Config::get('constants.date_format'),
//                            '5' => 'nullable|string|max:255',
//                            '6' => 'nullable|integer',
//                            '7' => 'nullable|integer',
//                            '8' => 'nullable|integer',
//                            '9' => 'nullable|integer',
//                            '10' => 'nullable|alpha|in:M,F'
//                        ]);
//                        $validation->setAttributeNames($niceNames);
//                        if ($validation->fails()) {
//                            $all = "";
//                            foreach ($validation->messages()->all() as $message){
//                                $all = $all.$message.' ';
//                            }
//                            $all = $all.' Please check row ' . $i . ' again!';
//                            throw new Exception($all);
//                        }
                    }
                }
            }

            if (!empty($data) && $data->count()) {
                $dataArray = $data->toArray();
                foreach ($dataArray as $row) {
                    if (!empty($row)) {

                        if (array_search($row, $dataArray) == 0) {
                            continue;
                        }//Skip header
                        $result = array_search($row[1], array_column($employeeList, 'em'));
                        $emID = $employeeList[$result]->em_id;
                        foreach ($currentList as $oldhealthCheck){
                            if($oldhealthCheck->em === $emID){
                                if($oldhealthCheck->pe == $row[63]){
                                    $this->delete($oldhealthCheck->id);
                                }
                            }
                        }
                        Log::info($row);
                        $healthCheck = new HealthCheck();
                        $healthCheck['employee_id'] = $emID;
                        $healthCheck['business_unit'] = $row[2];
                        $healthCheck['consultation_number'] = $row[3];
                        $healthCheck['consultation_date'] = $row[4];
                        $healthCheck['body_height'] = $row[5];
                        $healthCheck['body_weight'] = $row[6];
                        $healthCheck['abdominal_girth'] = $row[7];
                        $healthCheck['standard_weight'] = $row[8];
                        $healthCheck['bmi_index'] = $row[9];
                        $healthCheck['body_mass'] = $row[10];
                        $healthCheck['measurement_judge'] = $row[11];
                        $healthCheck['uncorrected_eyesight_right'] = $row[12];
                        $healthCheck['uncorrected_eyesight_left'] = $row[13];
                        $healthCheck['corrected_eyesight_right'] = $row[14];
                        $healthCheck['corrected_eyesight_left'] = $row[15];
                        $healthCheck['eyesight_judge'] = $row[16];
                        $healthCheck['hearing_1000_right'] = $row[17];
                        $healthCheck['hearing_1000_left'] = $row[18];
                        $healthCheck['hearing_4000_right'] = $row[19];
                        $healthCheck['hearing_4000_left'] = $row[20];
                        $healthCheck['hearing_judge'] = $row[21];
                        $healthCheck['max_blood_pressure_1st'] = $row[22];
                        $healthCheck['min_blood_pressure_1st'] = $row[23];
                        $healthCheck['max_blood_pressure_2nd'] = $row[24];
                        $healthCheck['min_blood_pressure_2nd'] = $row[25];
                        $healthCheck['antihypertensive_drug'] = $row[26];
                        $healthCheck['blood_pressure_judge'] = $row[27];
                        $healthCheck['period_flag'] = $row[28];
                        $healthCheck['uric_protein'] = $row[29];
                        $healthCheck['urinary_sugar'] = $row[30];
                        $healthCheck['urine_judge'] = $row[31];
                        $healthCheck['electrocardiogram_id'] = $row[32];
                        $healthCheck['electrocardiogram_comment1'] = $row[33];
                        $healthCheck['electrocardiogram_judge'] = $row[34];
                        $healthCheck['chest_xray_id'] = $row[35];
                        $healthCheck['chest_xray_method'] = $row[36];
                        $healthCheck['chest_xray_comment1'] = $row[37];
                        $healthCheck['chest_xray_comment2'] = $row[38];
                        $healthCheck['chest_xray_judge'] = $row[39];
                        $healthCheck['internal_medicine_comment1'] = $row[40];
                        $healthCheck['internal_medicine_comment2'] = $row[41];
                        $healthCheck['internal_medicine_judge'] = $row[42];
                        $healthCheck['blood_draw_id'] = $row[43];
                        $healthCheck['erythrocyte_count'] = $row[44];
                        $healthCheck['hemoglobin_content'] = $row[45];
                        $healthCheck['anemia_test_judge'] = $row[46];
                        $healthCheck['lipid_drug'] = $row[47];
                        $healthCheck['neutral_lipid'] = $row[48];
                        $healthCheck['hdl_cholesterol'] = $row[49];
                        $healthCheck['ldl_cholesterol'] = $row[50];
                        $healthCheck['lipid'] = $row[51];
                        $healthCheck['got'] = $row[52];
                        $healthCheck['gpt'] = $row[53];
                        $healthCheck['gamma_gtp'] = $row[54];
                        $healthCheck['liver_function_judge'] = $row[55];
                        $healthCheck['blood_glucose_drug'] = $row[56];
                        $healthCheck['fasting_blood_glucose'] = $row[57];
                        $healthCheck['after_food'] = $row[58];
                        $healthCheck['diabetes_judge'] = $row[59];
                        $healthCheck['uric_acid'] = $row[60];
                        $healthCheck['gout_judge'] = $row[61];
                        $healthCheck['total_judge'] = $row[62];
                        $healthCheck['period'] = $row[63];
                        $healthCheck['upload_file_id'] = $uh['id'];


                        $this->save($healthCheck);

                    }
                }
            }
            $uh['status'] = 1;
            $uh->save();
            DB::commit();
            return $uh['id'];
        }catch (Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }
    }

    public function delete($id)
    {
        return HealthCheck::where('health_check_id', $id)
            ->update(array('valid_flag' => false));
    }

    private function deleteAllByUploadFileID($uploadFileId){
        HealthCheck::where('upload_file_id', $uploadFileId)
            ->update(array('valid_flag' => false));
    }
}
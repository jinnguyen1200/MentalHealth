<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/10/17
 * Time: 4:15 PM
 */

namespace MentalHealthAI\Repositories\Implementations;


use Mockery\Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MentalHealthAI\Models\Attendance;
use MentalHealthAI\Models\UploadHistory;
use MentalHealthAI\Repositories\Interfaces\IAttendanceRepository;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceRepository implements IAttendanceRepository
{

    public function search($name)
    {
        // TODO: Implement search() method.
    }

    public function get($id)
    {


    }

    public function getAll($company_id, $office_id, $department_id)
    {
        $list = DB::table('attendance')
            ->select('employee.id as em','attendance.period as pe','attendance.id as id')
            ->leftJoin('employee','employee.id','=','attendance.employee_id')
            ->leftJoin('department','employee.department_id','=','department.id')
            ->leftJoin('office','department.office_id','=','office.id')
            ->leftJoin('company','office.company_id','=','company.id')
            ->where('company.id','=',$company_id)
            ->where('attendance.valid_flag','=',1)
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

    public function save(Attendance $attendance)
    {
        $attendance->save();
    }

    public function saveAttendanceFromFile($attendanceFile, $employeeList, $currentList)
    {
        try{
            DB::beginTransaction();
            if (is_string($attendanceFile)){
                return "File name error!";
            }
            $path = $attendanceFile->getRealPath();
            $name = $attendanceFile->getClientOriginalName();
            if (!is_csv($name)) {
                return "File name error!";
            }
            $splitedFileName = preg_split( "/[_.]/", $name );
            $period = substr($splitedFileName[1], 0, 6) ;
            $file_type = current($splitedFileName);
            if ($splitedFileName[0] !== "Attendance") {
                return "適切なファイルをインポートしてください。例えば　Attendance_201701.csv";
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
                            throw new Exception('従業員コードが存在しません。 '.$i.'行目を確認してください。!');
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
                        foreach ($currentList as $oldAttendance){
                            if($oldAttendance->em === $emID){
                                if($oldAttendance->pe == $row[10]){
                                    $this->delete($oldAttendance->id);
                                }
                            }
                        }
                        Log::info($row);
                        $attendance = new Attendance();
                        $attendance['employee_id'] = $emID;
                        $attendance['attendence_num'] = $row[2];
                        $attendance['absence_num'] = $row[3];
                        $attendance['overtime_hours'] = $row[4];
                        $attendance['late_times'] = $row[5];
                        $attendance['monday_absence'] = $row[6];
                        $attendance['monday_late_arrival'] = $row[7];
                        $attendance['measuring_date'] = $row[8];
                        $attendance['registration_date'] = $row[9];
                        $attendance['period'] = $row[10];
                        $attendance['upload_file_id'] = $uh['id'];
                        $this->save($attendance);

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
        return Attendance::where('id', $id)
            ->update(array('valid_flag' => false));
    }

    public function update($id,Attendance $attendance)
    {
        return Attendance::where('id','=',$id)->update($attendance->toArray());
    }


    private function deleteAllByUploadFileID($uploadFileId){
        Attendance::where('upload_file_id', $uploadFileId)
            ->update(array('valid_flag' => false));
    }


}
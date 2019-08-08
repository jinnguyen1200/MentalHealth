<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 07/09/2017
 * Time: 17:42
 */

namespace MentalHealthAI\Http\Controllers;


use Maatwebsite\Excel\Facades\Excel;
use MentalHealthAI\Models\Administrator;
use Illuminate\Http\Request;

class ImportController
{

    public function import(){
        return view('import');
    }

    public function upload(Request $request){
        if($request->file('csvFile'))
        {
            $path = $request->file('csvFile')->getRealPath();
            $data = Excel::load($path, function($reader) {
                $reader->noHeading();
            })->get();

            if(!empty($data) && $data->count())
            {
                    foreach ($data->toArray() as $row)
                    {
                        if(!empty($row))
                        {
                            $temp = new Administrator([
                                'account_type' => $row[0],
                                'company_id' => $row[1],
                                'office_id' => $row[2],
                                'department_id' => $row[3],
                                'email' => $row[4],
                                'password' => $row[5]
                            ]);
                            $temp->save();
                        }
                    }
            }
        }
        return back();
    }

    public function export(){
        $items = Administrator::all();
        Excel::create('items', function($excel) use($items) {
            $excel->sheet('ExportFile', function($sheet) use($items) {
                $sheet->fromArray($items);
            });
        })->export('csv');
    }

}
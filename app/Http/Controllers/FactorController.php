<?php
/**
 * Created by PhpStorm.
 * User: thienpg
 * Date: 10/4/17
 * Time: 2:43 PM
 */

namespace MentalHealthAI\Http\Controllers;


use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use MentalHealthAI\Models\FactorSettingValue;
use MentalHealthAI\Repositories\Interfaces\IFactorRepository;
use MentalHealthAI\Repositories\Interfaces\IFactorSettingValueRepository;
use MentalHealthAI\User;

class FactorController extends Controller
{
    protected $factorRepository;
    protected $factorSettingValueRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(IFactorRepository $factorRepository,
                                IFactorSettingValueRepository $factorSettingValueRepository)
    {
        $this->factorRepository = $factorRepository;
        $this->factorSettingValueRepository = $factorSettingValueRepository;

        $this->middleware('auth');
        $this->middleware('roles:' . User::ADMIN);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(Request $request)
    {
        Validator::make($request->all(), [
            'department_name' => 'required|string|max:255',
            'contact_phone_number' => 'required|string|max:255',
        ])->validate();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($g, $f)
    {
        list($factors, $factorSettingValues) = $this->factorRepository->getAll($g, $f);
        if (count($factors) > 0 && $f == 0) {
            $f = $factors[0]->id;
        }

        return view('admin.factor')
            ->with('selectedFactorGroup', $g)
            ->with('selectedFactor', $f)
            ->with('factors', $factors)
            ->with('factorSettingValues', $factorSettingValues);


    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $fsv = $this->factorSettingValueRepository->get($request['id']);

        if ($request->has('point')) {
            if ($request->has('selectedFactorGroup')) {
                if ($request['selectedFactorGroup'] == 5){
                    $point = hexdec($request['point']) ;
                }else {
                    $point = $request['point'];
                }
                $fsv['point'] = $point;
            }
        }
        if ($request->has('limit')) {
            $fsv['upper_limit'] = $request['limit'];
        }

        return $this->factorSettingValueRepository->update($fsv);
    }

    /**
     * Create factor
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->has('selectedFactorGroup')) {
            if ($request['selectedFactorGroup'] == 5){
                $this->validateAlertFactor($request);
            }
        }else{
            $this->validateFactor($request);
        }

        if(is_null($request['txtPoint'])){
            return response()->json([
                'result' => false,
                'message' => 'ポイントを入力してください。'
            ]);
        }
        if(is_null($request['txtUpperLimit'])){
            return response()->json([
                'result' => false,
                'message' => '血圧上限を入力してください。'
            ]);
        }
        $fsv = new FactorSettingValue();
        $fsv['factor_id'] = $request['selectedFactor'];
        $fsv['upper_limit'] = $request['txtUpperLimit'];
        $point = $request['txtPoint'];
        if ($request->has('selectedFactorGroup')) {
            if ($request['selectedFactorGroup'] == 5){
                $point = hexdec($request['txtPoint']) ;
            }
        }
        $fsv['point'] = $point;
//        $fsv['point'] = $request['txtPoint'];

        try{
            $this->factorSettingValueRepository->create($fsv);
        } catch (QueryException $exception){
            return response()->json([
                'result' => false,
                'message' => '何らかのエラーが発生しました。'
            ]);
        }


        return response()->json([
            'result' => true,
            'message' => '正常に作成されました。'
        ]);
    }

    
    protected function validateFactor(Request $request)
    {
        $messages = [
            "txtPoint.required" => "入力してください。",
            "txtUpperLimit.required" => "入力してください。",
            
        ];
        Validator::make($request->all(), [
            'txtUpperLimit' => 'required',
            'txtPoint'=> 'required'
        ], $messages)->validate();
    }

    protected function validateAlertFactor(Request $request)
    {
        $messages = [
            "txtPoint.required" => "入力してください。",
            "txtPoint.regex" => "カラーコードを入力してください",
            "txtUpperLimit.required" => "入力してください。",
        ];

        Validator::make($request->all(), [
            'txtUpperLimit' => 'required',
            'txtPoint'=> array(
                'required',
                'regex:/^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})/u'
            )



        ], $messages)->validate();
    }


    /**
     * Delete factor
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if(is_null($request['id'])){
            return response()->json([
                'result' => false,
                'message' => 'ポイントを入力してください。'
            ]);
        }
        $id = $request['id'];

        try{
            $this->factorSettingValueRepository->delete($id);
        } catch (QueryException $exception){
            return response()->json([
                'result' => false,
                'message' => '何らかのエラーが発生しました。'
            ]);
        }

        return response()->json([
            'result' => true,
            'message' => '正常に作成されました。'
        ]);
    }
}
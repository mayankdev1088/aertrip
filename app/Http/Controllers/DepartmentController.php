<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Department;
use Validator;
use Helper;

class DepartmentController extends Controller
{
    private $rules = [
		'name' => 'required|unique:department'
	];

	private $messages = [
		'name.required' => 'Name is required',
		'name.unique' => 'Name already exists'
    ];
    
    public function store(Request $request){

        $request_all = $request->all();

        $request_all['name'] = Helper::sanitize($request_all['name']);

        $validator = Validator::make($request_all, $this->rules, $this->messages);

		if($validator->fails()){
			return Helper::displayErrors($validator);
        }
        

        $department = Department::create(['name' => $request_all['name']]);


        return response()->json([
            'result' => true, 
            'status_code' => Response::HTTP_OK, 
            'message' => 'Department created',
            'data' => [
                $department
            ]
            
        ], Response::HTTP_OK);
    }

}

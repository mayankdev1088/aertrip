<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Employee;
use App\EmployeeDepartment;
use App\EmployeeAddress;
use App\EmployeeContactNumber;
use Validator;
use Helper;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    private $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'date_of_birth' => 'required|date',
        'department' => 'required|integer|exists:department,id',
        'address.*.name' => 'required',
        'address.*.country' => 'required|exists:country,id',
        'address.*.address' => 'required',
        'address.*.state' => 'required',
        'address.*.city' => 'required',
        'address.*.postal_code' => 'required',
        'contact_numbers.*.phone_cc' => 'required',
        'contact_numbers.*.phone' => 'required'
	];

	private $messages = [
        'first_name.required' => 'First name is required',
        'last_name.required' => 'Last name is required',
        'date_of_birth.required' => 'DOB is required',
        'date_of_birth.date' => 'DOB is not a valid date',
        'department.required' => 'Please specify a department',
        'department.integer' => 'Department should be an integer reference',
        'department.exists' => 'This department does not exist'
    ];

    public function store(Request $request, $employee_id = null){
        
        $request_all = $request->all();

        $request_all = Helper::cleanAll($request_all);

        $validator = Validator::make($request_all, $this->rules, $this->messages);

        if($validator->fails()){
			return Helper::displayErrors($validator);
        }

        $id = $employee_id;

        if(!empty($employee_id)){
            $employee_exists = Employee::where('id', $id)->count();

            if(!$employee_exists){

                return response()->json([
                    'result' => false,
                    'status_code' => Response::HTTP_NOT_ACCEPTABLE, 
                    'message' => 'Could not find employee'
                ], Response::HTTP_NOT_ACCEPTABLE );
            }

            $text = 'updated';
        }else{
            $text = 'created';
        }

        $employee_data = [
            'id' => $id,
            'first_name' => $request_all['first_name'],
            'last_name' => $request_all['last_name'],
            'date_of_birth' => Carbon::createFromFormat('d/m/Y', $request_all['date_of_birth'])->toDateString()
        ];

        //Add/update employee
        $employee = Employee::updateOrCreate(['id' => $id], $employee_data);

        if(empty($id)){
            Helper::generateEmployeeNumber($employee);
        }

        //Assign department to the employee
        EmployeeDepartment::updateOrCreate(['employee_id' => $employee->id], [
            'employee_id' => $employee->id,
            'department_id' => $request_all['department'],
        ]);

        //Add/update address
        if(empty($request_all['address'])){
            EmployeeAddress::where('employee_id', $employee->id)->delete();
        }else{
            
            foreach($request_all['address'] as $address){

                $address_data = [
                    'id' => empty($address['id']) ? null : $address['id'],
                    'employee_id' => $employee->id,
                    'name' => $address['name'],
                    'country' => $address['country'],
                    'address' => $address['address'],
                    'state' => $address['state'],
                    'city' => $address['city'],
                    'postal_code' => $address['postal_code']
                ];

                EmployeeAddress::updateOrCreate(['id' => $address['id']], $address_data);
            }
        }

        //Add/update contact number
        if(empty($request_all['contact_numbers'])){
            EmployeeContactNumber::where('employee_id', $employee->id)->delete();
        }else{
            
            foreach($request_all['contact_numbers'] as $contact_number){

                $contact_data = [
                    'id' => empty($contact_number['id']) ? null : $contact_number['id'],
                    'employee_id' => $employee->id,
                    'phone_cc' => $contact_number['phone_cc'],
                    'phone' => $contact_number['phone']
                ];

                EmployeeContactNumber::updateOrCreate(['id' => $contact_number['id']], $contact_data);
            }
        }

        $employee = Employee::with('address', 'contact')->find($employee->id);

        

        return response()->json([
            'result' => true, 
            'status_code' => Response::HTTP_OK, 
            'message' => "Employee $text",
            'data' => ['employee' => $employee]
            
        ], Response::HTTP_OK);

    }
}

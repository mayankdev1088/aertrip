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
    
    public function store(Request $request){
        
        $request_all = $request->all();

        $request_all = Helper::cleanAll($request_all);
        
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'date_of_birth' => 'required|date',
            'department' => 'required|integer|exists:department,id',
            'address.*.id' => 'present',
            'address.*.name' => 'required',
            'address.*.country' => 'required|exists:country,id',
            'address.*.address' => 'required',
            'address.*.state' => 'required',
            'address.*.city' => 'required',
            'address.*.postal_code' => 'required',
            'contact_numbers.*.id' => 'present',
            'contact_numbers.*.phone_cc' => 'required',
            'contact_numbers.*.phone' => 'required'
        ];
    
        $messages = [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'date_of_birth.required' => 'DOB is required',
            'date_of_birth.date' => 'DOB is not a valid date',
            'department.required' => 'Please specify a department',
            'department.integer' => 'Department should be an integer reference',
            'department.exists' => 'This department does not exist',
            'address.*.id.present' => 'Address id is required',
            'address.*.name.required' => 'Name is required',
            'address.*.country.required' => 'Country is required',
            'address.*.address.required' => 'Address is required',
            'address.*.state.required' => 'State is required',
            'address.*.city.required' => 'City is required',
            'contact_numbers.*.id.present' => 'Contact id is required',
            'contact_numbers.*.phone_cc.required' => 'Country code is required',
            'contact_numbers.*.phone.required' => 'Phone number is required'
        ];

        $validator = Validator::make($request_all, $rules, $messages);

        if($validator->fails()){
			return Helper::displayErrors($validator);
        }

        $id = $request->id;

        if(!empty($id)){
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
            
            foreach($request_all['address'] as &$address){

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

                $employee_address = EmployeeAddress::updateOrCreate(['id' => $address['id']], $address_data);

                $address['id'] = $employee_address->id;

            }

            //Remove addresses which were not sent back
            $address_not_to_remove =  array_column($request_all['address'], 'id');

            EmployeeAddress::where('employee_id', $employee->id)->whereNotIn('id', $address_not_to_remove)->delete();
            
            
            
        }

        //Add/update contact number
        if(empty($request_all['contact_numbers'])){
            EmployeeContactNumber::where('employee_id', $employee->id)->delete();
        }else{
            
            foreach($request_all['contact_numbers'] as &$contact_number){

                $contact_data = [
                    'id' => empty($contact_number['id']) ? null : $contact_number['id'],
                    'employee_id' => $employee->id,
                    'phone_cc' => $contact_number['phone_cc'],
                    'phone' => $contact_number['phone']
                ];

                $employee_contact_number = EmployeeContactNumber::updateOrCreate(['id' => $contact_number['id']], $contact_data);

                $contact_number['id'] = $employee_contact_number->id;
            }

            //Remove numbers which were not sent back
            $contact_numbers_not_to_remove =  array_column($request_all['contact_numbers'], 'id');

            EmployeeContactNumber::where('employee_id', $employee->id)->whereNotIn('id', $contact_numbers_not_to_remove)->delete();
        }

        $employee = Employee::with(['address' =>function($query){
            $query->selectRaw('id, employee_id, name, address, country, state, city, postal_code');
        }])->with(['contact_numbers' => function($query){
            $query->selectRaw('id, employee_id, phone_cc, phone');
        }])->detail()->find($employee->id);

        

        return response()->json([
            'result' => true, 
            'status_code' => Response::HTTP_OK, 
            'message' => "Employee $text",
            'data' => ['employee' => $employee]
            
        ], Response::HTTP_OK);

    }

    public function delete(Request $request){
        $request_all = $request->all();

        $request_all = Helper::cleanAll($request_all);
        
        $rules = [
            'id' => 'required|integer'
        ];

        $messages = [
            'id.required' => 'Id is required',
            'id.integer' => 'Id should be an integer'
        ];

        $validator = Validator::make($request_all, $rules, $messages);

        if($validator->fails()){
			return Helper::displayErrors($validator);
        }

        $id = $request->id;

        $employee_exists = Employee::where('id', $id)->count();

        if(!$employee_exists){

            return response()->json([
                'result' => false,
                'status_code' => Response::HTTP_NOT_ACCEPTABLE, 
                'message' => 'Could not find employee'
            ], Response::HTTP_NOT_ACCEPTABLE );
        }

        Employee::where('id', $id)->delete();

        return response()->json([
            'result' => true, 
            'status_code' => Response::HTTP_OK, 
            'message' => "Employee deleted"
            
        ], Response::HTTP_OK);
    }

    public function view(Request $request){
        $request_all = $request->all();

        $request_all = Helper::cleanAll($request_all);
        
        $rules = [
            'id' => 'required|integer'
        ];

        $messages = [
            'id.required' => 'Id is required',
            'id.integer' => 'Id should be an integer'
        ];

        $validator = Validator::make($request_all, $rules, $messages);

        if($validator->fails()){
			return Helper::displayErrors($validator);
        }

        $id = $request->id;

        $employee_exists = Employee::where('id', $id)->count();

        if(!$employee_exists){

            return response()->json([
                'result' => false,
                'status_code' => Response::HTTP_NOT_ACCEPTABLE, 
                'message' => 'Could not find employee'
            ], Response::HTTP_NOT_ACCEPTABLE );
        }

        $employee = Employee::with(['address' =>function($query){
            $query->selectRaw('id, employee_id, name, address, country, state, city, postal_code');
        }])->with(['contact_numbers' => function($query){
            $query->selectRaw('id, employee_id, phone_cc, phone');
        }])->detail()->find($id);

        return response()->json([
            'result' => true, 
            'status_code' => Response::HTTP_OK, 
            'data' => ['employee' => $employee]
            
        ], Response::HTTP_OK);
    }

    public function search(Request $request){
        $request_all = $request->all();

        $request_all = Helper::cleanAll($request_all);
        
        $rules = [
            'keyword' => 'present'
        ];

        $messages = [
            'keyword.present' => 'Keyword is required'
        ];

        $validator = Validator::make($request_all, $rules, $messages);

        if($validator->fails()){
			return Helper::displayErrors($validator);
        }

        $employees = Employee::with(['address' =>function($query){
            $query->selectRaw('id, employee_id, name, address, country, state, city, postal_code');
        }])->with(['contact_numbers' => function($query){
            $query->selectRaw('id, employee_id, phone_cc, phone');
        }])->detail($request)->get();

        return response()->json([
            'result' => true, 
            'status_code' => Response::HTTP_OK, 
            'data' => ['employees' => $employees]
            
        ], Response::HTTP_OK);
    }

}

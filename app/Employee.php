<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';

    protected $guarded = [];

    public function scopeDetail($query, $request = null){
        
        $query = $query->selectRaw("distinct employee.id, employee.employee_number, employee.first_name, employee.last_name, DATE_FORMAT(employee.date_of_birth, '%d/%m/%Y') as date_of_birth, ed.department_id as department, d.name as department_name")
        ->join('employee_department as ed', 'ed.employee_id', '=', 'employee.id')
        ->join('department as d', 'd.id', '=', 'ed.department_id')
        ->leftJoin('employee_address as ea', 'ea.employee_id', '=', 'employee.id')
        ->leftJoin('employee_contact_number as ecn', 'ecn.employee_id', '=', 'employee.id')
        ->leftJoin('country as c', 'ea.country', '=', 'c.id');

        if($request && $request->has('keyword') && !empty($request->keyword)){
            $keyword = $request->keyword;

            $query->whereRaw("(employee.first_name like '%$keyword%' OR employee.last_name like '%$keyword%' OR employee.employee_number like '%$keyword%' OR d.name like '%$keyword%' OR ea.address like '%$keyword%' OR c.name like '%$keyword%' OR ea.state like '%$keyword%' OR ea.city like '%$keyword%' OR ecn.phone like '%$keyword%')");
        }

        return $query;
    }

    public function address(){
        return $this->hasMany('App\EmployeeAddress', 'employee_id');
    }

    public function contact_numbers(){
        return $this->hasMany('App\EmployeeContactNumber', 'employee_id');
    }
}

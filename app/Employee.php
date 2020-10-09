<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';

    protected $guarded = [];

    public function scopeDetail($query){
        return $query->selectRaw('employee.id, employee.first_name, employee.last_name, employee.date_of_birth, ed.department_id as department, d.name as department_name')
        ->join('employee_department as ed', 'ed.employee_id', '=', 'employee.id')
        ->join('department as d', 'd.id', '=', 'ed.department_id');
    }

    public function address(){
        return $this->hasMany('App\EmployeeAddress', 'employee_id');
    }

    public function contact_numbers(){
        return $this->hasMany('App\EmployeeContactNumber', 'employee_id');
    }
}

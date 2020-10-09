<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';

    protected $guarded = [];

    public function department(){
        return $this->hasOne('App\EmployeeDepartment', 'employee_id');
    }

    public function address(){
        return $this->hasMany('App\EmployeeAddress', 'employee_id');
    }

    public function contact(){
        return $this->hasMany('App\EmployeeContactNumber', 'employee_id');
    }
}

<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/department/add', 'DepartmentController@store');

Route::post('/employee/add', 'EmployeeController@store');

Route::post('/employee/{employee_id}/edit', 'EmployeeController@store');

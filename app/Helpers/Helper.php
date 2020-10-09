<?php

namespace App\Helpers;

use Illuminate\Http\Response;


class Helper
{
    
    public static function displayErrors($validator, $return = false)
    {
        $errors = $validator->errors()->toArray();

        $error_messages = [];
        foreach ($errors as $key => $error) {
            $error_messages[] = [
                'key' => $key,
                'message' => $error[0]
            ];
        }

        if ($return) {
            return $error_messages;
        } else {
            return response()->json([
                'result' => false,
                'status_code' => Response::HTTP_BAD_REQUEST, 
                'errors' => $error_messages
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public static function displayInternalError($e){
        return response()->json(
            [
                'result' => false,
                'message' => $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile()
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public static function cleanAll($values){
        array_walk_recursive($values, function(&$value){
            return $value = self::sanitize($value);
        });

        return $values;
    }

    public static function generateEmployeeNumber($employee){

        $number = $employee->id;
    
        $year = date('y', strtotime($employee->created_at));
    
        $month = date('m', strtotime($employee->created_at));
    
        $employee_number = sprintf('EMP%s%s%s', $year, $month, $number);
    
        $employee->employee_number = $employee_number;
    
        $employee->save();
    }

    public static function sanitize($value)
    {
        $value = trim(strip_tags($value));

        return $value;
    }

        
}

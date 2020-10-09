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

    public static function sanitize($value)
    {
        $value = trim(strip_tags($value));

        return $value;
    }

        
}

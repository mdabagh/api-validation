<?php

namespace php;

class AV
{
    public $errors = null;
    public function __construct()
    {
        $this->errors = [];
    }
    function errorsAV($responseCode = null, $ResponseMessage = null, $Result = null)
    {
        if (isset($responseCode) and isset($responseCode[$ResponseMessage])) {
            $ResponseCode = $responseCode[$ResponseMessage];
        }
        $ResponseCode = null;

        $this->errors[] = (array)[
            "ResponseCode" => $ResponseCode ? $ResponseCode : null,
            "ResponseMessage" =>  $ResponseMessage . '.!',
            "Result" => $Result
        ];
        return $this->errors;
    }

    function ApiValidation($request, $rules, $responseCode = null)
    {
        foreach ($rules as $val => $rule) {
            $isRule = array_key_exists($val, $rules);
            if ($isRule) {
                $isDefinRequest = array_key_exists($val, $request);
                if ($isDefinRequest) {
                    $this->CheckTypeAV($request, $val, $rule, $responseCode);
                    $this->CheckNullable($request, $val, $rule, $responseCode);
                    $this->CheckModalClass($request, $val, $rule, $responseCode);
                    $this->CheckChild($request, $val, $rule, $responseCode);
                    $this->CheckMinCountArray($request, $val, $rule, $responseCode);
                    $this->CheckMaxCountArray($request, $val, $rule, $responseCode);
                } else {
                    $ResponseMessage = $val . ' Is Not Find On Request';
                    $this->errorsAV($responseCode, $ResponseMessage);
                }
            }
        }
        return $this->errors ? $this->errors : 1;
    }

    function CheckTypeAV($request, $val, $rule, $responseCode)
    {
        if (isset($rule['type'])) {
            switch ($rule['type']) {
                case 'str':
                    $type = "is_string";
                    break;
                case 'int':
                    $type = "is_int";
                    break;
                case 'array':
                    $type = "is_array";
                    break;

                case 'Base64':
                    $type = "Base64";
                    break;
                default:
                    $type = "null";
                    break;
            }

            if ($type != "Base64") {
                if (isset($type) and !$type($request->$val)) {
                    $ResponseMessage = $val . ' Is Not Of Type ' . $rule['type'];
                    $this->errorsAV($responseCode, $ResponseMessage);
                    $type = null;
                }
            }
        }
    }


    function CheckNullable($request, $val, $rule, $responseCode)
    {
        if (isset($rule['nullable']) and $rule['nullable'] == false and !isset($request->$val)) {
            $ResponseMessage = 'This ' . $val . ' must not be null';
            $this->errorsAV($responseCode, $ResponseMessage);
        }
    }

    function CheckModalClass($request, $val, $rule, $responseCode)
    {
        if (isset($rule['modalclass']) and isset($request->$val)) {
            $model = $rule['modalclass'];

            $model = $model::find($request->$val);
            if (!isset($model)) {
                $ResponseMessage = $val . ' Invalid';
                $this->errorsAV($responseCode, $ResponseMessage);
            }
        }
    }

    function CheckChild($request, $val, $rule, $responseCode)
    {
        if (isset($rule['child'])) {
            foreach ($rule['child'] as $keyRuleChild => $valueRuleChild) {
                foreach ($request->$val as $keyRequest => $valueRequest) {
                    $isDefinRequest = array_key_exists($keyRuleChild, $valueRequest);
                    if ($isDefinRequest) {
                        $this->CheckTypeAV($valueRequest, $keyRuleChild, $rule['child'][$keyRuleChild], $responseCode);
                        $this->CheckNullable($valueRequest, $keyRuleChild, $rule['child'][$keyRuleChild], $responseCode);
                        $this->CheckModalClass($valueRequest, $keyRuleChild, $rule['child'][$keyRuleChild], $responseCode);
                        $this->CheckChild($valueRequest, $keyRuleChild, $rule['child'][$keyRuleChild], $responseCode);
                        $this->CheckMinCountArray($request, $val, $rule, $responseCode);
                        $this->CheckMaxCountArray($request, $val, $rule, $responseCode);
                        if ($keyRuleChild == "Base64") {
                            $this->CheckBase64($valueRequest->$keyRuleChild, $keyRuleChild, $responseCode);
                            if ($rule['child']['Base64']) {
                                foreach ($rule['child']['Base64'] as $key => $value) {
                                    if ($key == "max") {
                                        $this->CheckBase64Size($valueRequest->$keyRuleChild, $value, $keyRuleChild, $responseCode);
                                    }
                                    if ($key == "mimes") {
                                        $this->CheckMimes($valueRequest->$keyRuleChild, $value, $keyRuleChild, $responseCode);
                                    }
                                }
                            }
                        }
                    } else {
                        $ResponseMessage = $keyRuleChild . ' Is Not Find On Request';
                        $this->errorsAV($responseCode, $ResponseMessage);
                    }
                }
            }
        }
    }

    function CheckBase64($base64, $key, $responseCode)
    {
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $return  = true;

        // Check if there are valid base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $base64)) $return  = false;

        // Decode the string in strict mode and check the results
        $decoded = base64_decode($base64, true);
        if (false === $decoded) $return  = false;

        // Encode the string again
        if (base64_encode($decoded) != $base64) $return  = false;


        if (!$return) {
            $ResponseMessage = $key . ' Is Not Of Type Base64';
            $this->errorsAV($responseCode, $ResponseMessage);
        }
    }
    function CheckBase64Size($base64, $maxSizeKB, $key, $responseCode)
    {
        $size_in_bytes = (int) (strlen(rtrim($base64, '=')) * 3 / 4);
        $size_in_kb    = round($size_in_bytes / 1024);
        if ($size_in_kb > $maxSizeKB) {
            $ResponseMessage = 'The ' . $key . ' size is ' . $size_in_kb . ' KB, which is more than ' . $maxSizeKB . ' KB';
            $this->errorsAV($responseCode, $ResponseMessage);
        }
    }

    function CheckMimes($base64, $mimsSet, $key, $responseCode)
    {
        $mims = explode(';', $base64)[0];  // "data:image/png"
        $mims = explode('/', $mims)[1];  // "png"
        $mimsSetArray = explode(',', $mimsSet);
        $result = in_array($mims, $mimsSetArray);
        if (!$result) {
            $ResponseMessage = 'The format of the submitted file is ' . $mims . ' and it is not correct';
            $this->errorsAV($responseCode, $ResponseMessage);
        }
    }

    function CheckMinCountArray($array, $val, $key, $responseCode)
    {
        if (isset($key['minCount'])) {
            $result = count($array->$val) >= $key['minCount'];
            if (!$result) {
                $ResponseMessage = 'The number of ' . $val . ' is more than ' . $key['minCount'];
                $this->errorsAV($responseCode, $ResponseMessage);
            }
        }
    }

    function CheckMaxCountArray($array, $val, $key, $responseCode)
    {
        if (isset($key['maxCount'])) {
            $result = count($array->$val) > $key['maxCount']; // 2 > 2 false
            if ($result) {
                $ResponseMessage = 'The number of ' . $val . ' is less than ' . $key['maxCount'];
                $this->errorsAV($responseCode, $ResponseMessage);
            }
        }
    }
}

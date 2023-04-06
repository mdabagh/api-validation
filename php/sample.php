<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use php\AV;
use App\Models\USER;

class ContractController extends Controller
{
    public function store(Request $request)
    {
        $AV = new AV;
        $request = json_decode($request->getContent());
        $rules = [
            "UserId" => [
                "type" => "str",
                "nullable" => false,
                "modalclass" => USER::class
            ],
            'Files' => [
                "type" => "array",
                "nullable" => false,
                "minCount" => 1,
                "maxCount" => 2,
                "child" => [
                    "Base64" => [
                        "type" => "Base64",
                        "nullable" => false,
                        "max" => UPLOAD_MAX_SIZE,
                        "mimes" => UPLOAD_MIMES
                    ],
                    "TypeId" => [
                        "type" => "int",
                        "nullable" => false,
                    ]
                ]
            ],
        ];
        $responseCode = [
            "user Is Not Of Type Str" => "300",
            "Files Is Not Of Type Array" => "301",

            "This user must not be null" => "302",
            "This Files must not be null" => "303",

            "user Invalid" => "304",

            "The minimum number of files should be minCount" => "305",
            "The maximum number of files should be maxCount" => "306",
        ];

        $ApiContractCreateValidation = $AV->ApiValidation($request, $rules, $responseCode);

        if ($ApiContractCreateValidation) {
            return response()->json($ApiContractCreateValidation, 400);
        }
    }
}

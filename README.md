# AV Class - API Input Validation

## Overview

The `AV` class is a comprehensive validation tool for API input data in PHP. It ensures that incoming requests conform to specified validation rules, enhancing data integrity and security within your application. This document provides a detailed explanation of the class's features, including how to use them effectively.

## Class Structure

### Properties

- **`public $errors`**: An array that stores error messages generated during validation. It is initialized as an empty array in the constructor.

### Constructor

```php
public function __construct()
{
    $this->errors = [];
}
```

The constructor initializes the `errors` property to an empty array, preparing it to store any validation errors that may occur.

## Methods

### 1. `errorsAV($responseCode = null, $ResponseMessage = null, $Result = null)`

This method is responsible for adding error messages to the `errors` array. It constructs an error message with a response code, message, and result.

- **Parameters**:
  - `$responseCode`: An optional response code that can be used to identify the type of error.
  - `$ResponseMessage`: A message describing the error.
  - `$Result`: Any additional result information related to the error.

- **Usage**: This method is called whenever a validation check fails, allowing the class to accumulate error messages.

### 2. `ApiValidation($request, $rules, $responseCode = null)`

This method performs the main validation logic. It iterates through the provided rules and checks each corresponding value in the request.

- **Parameters**:
  - `$request`: The incoming request data (usually decoded from JSON).
  - `$rules`: An associative array defining the validation rules for each field.
  - `$responseCode`: An optional array mapping error messages to response codes.

- **Returns**: An array of errors if validation fails, or `1` if validation passes.

- **Usage**: This method is the entry point for validation, calling various helper methods to perform specific checks.

### 3. `CheckTypeAV($request, $val, $rule, $responseCode)`

This method checks if the type of the value in the request matches the expected type defined in the rules.

- **Parameters**:
  - `$request`: The incoming request data.
  - `$val`: The key of the value being checked.
  - `$rule`: The validation rule for the key.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called within `ApiValidation` to ensure that the data type of each field is correct.

### 4. `CheckNullable($request, $val, $rule, $responseCode)`

This method checks if a field that is marked as non-nullable is present in the request.

- **Parameters**:
  - `$request`: The incoming request data.
  - `$val`: The key of the value being checked.
  - `$rule`: The validation rule for the key.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called within `ApiValidation` to ensure that required fields are not null.

### 5. `CheckModalClass($request, $val, $rule, $responseCode)`

This method checks if the value of a field corresponds to a valid record in the specified model class.

- **Parameters**:
  - `$request`: The incoming request data.
  - `$val`: The key of the value being checked.
  - `$rule`: The validation rule for the key, which includes the `modalclass`.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is crucial for validating foreign key relationships. If the value does not correspond to a valid record in the database, an error is recorded.

- **Example**: If the rule specifies a `modalclass` of `USER::class`, this method will check if the `User Id` provided in the request exists in the `users` table.

### 6. `CheckChild($request, $val, $rule, $responseCode)`

This method validates nested structures within arrays. It is essential for validating complex data structures.

- **Parameters**:
  - `$request`: The incoming request data.
  - `$val`: The key of the array being checked.
  - `$rule`: The validation rule for the array, which includes child rules.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called when the validation rule for a field includes a `child` key, allowing for recursive validation of nested elements.

### 7. `CheckBase64($base64, $key, $responseCode)`

This method validates if a string is a valid Base64 encoded string.

- **Parameters**:
  - `$base64`: The Base64 string to validate.
  - `$key`: The key associated with the Base64 data.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called when a field is expected to contain Base64 data, ensuring that the data is correctly formatted.

### 8. `CheckBase64Size($base64, $maxSizeKB, $key, $responseCode)`

This method checks if the size of the Base64 data exceeds a specified limit.

- **Parameters**:
  - `$base64`: The Base64 string to check.
  - `$maxSizeKB`: The maximum allowed size in kilobytes.
  - `$key`: The key associated with the Base64 data.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called to enforce size limits on Base64 data, ensuring that uploaded files do not exceed the specified size.

### 9. `CheckMimes($base64, $mimsSet, $key, $responseCode)`

This method checks if the MIME type of the Base64 data is valid.

- **Parameters**:
  - `$base64`: The Base64 string to check.
  - `$mimsSet`: A string of allowed MIME types.
  - `$key`: The key associated with the Base64 data.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called to ensure that the uploaded file's MIME type is among the allowed types.

### 10. `CheckMinCountArray($array, $val, $key, $responseCode)`

This method checks if the number of elements in an array meets a minimum count requirement.

- **Parameters**:
  - `$array`: The array being checked.
  - `$val`: The key of the array.
  - `$key`: The validation rule for the array.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called to enforce minimum counts on array inputs.

### 11. `CheckMaxCountArray($array, $val, $key, $responseCode)`

This method checks if the number of elements in an array exceeds a maximum count requirement.

- **Parameters**:
  - `$array`: The array being checked.
  - `$val`: The key of the array.
  - `$key`: The validation rule for the array.
  - `$responseCode`: The response code mapping.

- **Usage**: This method is called to enforce maximum counts on array inputs.

## Example Usage

### Step 1: Create a Helper

To utilize the `AV` class for validating API inputs, create a helper function or class that will handle the validation process. Below is an example of how to implement this in a Laravel controller.

### Step 2: Define Validation Rules

Define the validation rules for your API inputs. The rules should specify the expected data types, whether fields can be null, and any additional constraints such as minimum and maximum counts for arrays.

### Example Implementation

Here’s an example of how to use the `AV` class in a Laravel controller:

```php
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
        
        // Define validation rules
        $rules = [
            "User Id" => [
                "type" => "str",
                "nullable" => false,
                "modalclass" => USER::class // Validates if UserId exists in the USER model
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

        // Define response codes for error messages
        $responseCode = [
            "user Is Not Of Type Str" => "300",
            "Files Is Not Of Type Array" => "301",
            "This user must not be null" => "302",
            "This Files must not be null" => "303",
            "user Invalid" => "304",
            "The minimum number of files should be minCount" => "305",
            "The maximum number of files should be maxCount" => "306",
        ];

        // Perform validation
        $ApiContractCreateValidation = $AV->ApiValidation($request, $rules, $responseCode);

        // Handle validation errors
        if ($ApiContractCreateValidation) {
            return response()->json($ApiContractCreateValidation, 400);
        }

        // Continue with processing the valid request...
    }
}
```

### Step 3: Send a Request

You can test the validation by sending a JSON request to your API endpoint. Here’s an example of a valid request body:

```json
{
    "User Id": "1",
    "Files": [
        {
            "Base64": "data:image/png;base64,iVBORwECBAgAECBAgQIECAAAECBAgQIECAAAECBAgQIECAAAECBAgQIECAAAECBAgEPKB+hvqdJywAAAAASUVORK5CYII=",
            "TypeId": 28
        }
    ]
}
```

### Conclusion

The `AV` class provides a robust solution for validating API inputs in PHP applications. By following the outlined steps, developers can ensure that their applications handle data securely and efficiently. For any questions or contributions, feel free to open an issue or submit a pull request on the GitHub repository.

### Additional Notes

- **Customization**: You can customize the error messages and response codes to fit your application's needs.
- **Extensibility**: The `AV` class can be extended to include additional validation methods as required by your application.
- **Testing**: It is recommended to write unit tests for your validation logic to ensure it behaves as expected under various scenarios.

<?php

namespace App\Http\Requests\Api\v1\Location;

use App\Http\Requests\Api\OutronApiRequest;

/**
 * @property-read string ip
 */

class FindLocationRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            'ip' => ['required', 'ip'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ip' => 'IP',
        ];
    }
}

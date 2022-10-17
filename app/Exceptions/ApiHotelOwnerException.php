<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ApiHotelOwnerException extends ApiException
{
    protected $code = ResponseAlias::HTTP_UNAUTHORIZED;
    protected $message = 'User is not the hotel owner.';
}

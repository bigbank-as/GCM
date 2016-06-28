<?php
namespace Bigbank\Gcm;

/**
 * Exception settings for Gcm
 */
class Exception extends \Exception
{

    const ILLEGAL_API_KEY = 1;
    const AUTHENTICATION_ERROR = 2;
    const MALFORMED_REQUEST = 3;
    const UNKNOWN_ERROR = 4;
    const MALFORMED_RESPONSE = 5;

}

<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a social login cannot be completed. The message is safe to show to the user.
 */
class SocialLoginException extends RuntimeException
{
    //
}

<?php
namespace Modules\Articles\Exceptions;

use Exception;
use Illuminate\Validation\Validator;

class FormValidationException extends Exception
{
    protected $code = 28;

    function __construct($src = null)
    {
        if ($src instanceof Validator) {
            $src = $src->errors();
        }

        parent::__construct($src);
    }
}
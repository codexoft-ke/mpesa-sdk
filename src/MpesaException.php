<?php
namespace codexoft\MpesaSdk;

class MpesaException extends \Exception
{
    public function __toString(): string
    {
        // Only return the error message, not the file and line information
        return $this->getMessage();
    }
}
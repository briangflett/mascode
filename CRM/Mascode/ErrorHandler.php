<?php

class CRM_Mascode_ErrorHandler
{
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Check if this error code is included in error_reporting
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $message = sprintf(
            "Error: [%s] %s in %s on line %d",
            $errno,
            $errstr,
            $errfile,
            $errline
        );

        // Log the error using CiviCRM's logging mechanism
        Civi::log()->warning($message);

        // Don't execute PHP's internal error handler
        return true;
    }
}

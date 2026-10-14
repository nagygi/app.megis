<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A diagnosis change that the rules do not allow; the message is shown to the admin.
 */
class DiagnosisException extends RuntimeException {}

<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A file that cannot be read as text or audio; the message is shown to the user.
 */
class UnreadableMaterialException extends RuntimeException {}

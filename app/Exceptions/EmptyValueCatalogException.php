<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The engine found no Értéktár value to match to; reported to the alert list,
 * while the material is still processed without matching (06 · 6.4).
 */
class EmptyValueCatalogException extends RuntimeException {}

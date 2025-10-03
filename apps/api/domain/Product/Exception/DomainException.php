<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

use DomainException as BaseDomainException;

/**
 * Base Domain Exception
 * 
 * All domain exceptions extend from this base class.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-014: Domain layer includes custom exceptions for business rule violations
 */
abstract class DomainException extends BaseDomainException
{
    //
}

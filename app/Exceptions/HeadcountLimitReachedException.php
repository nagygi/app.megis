<?php

namespace App\Exceptions;

use App\Models\Organization;
use RuntimeException;

/**
 * Adding or reactivating a user would exceed the organization's headcount limit (02 · 2.5).
 */
class HeadcountLimitReachedException extends RuntimeException
{
    public function __construct(public readonly Organization $organization)
    {
        parent::__construct(__('admin.members.limit_reached', [
            'count' => $organization->headcount(),
            'limit' => $organization->effectiveHeadcountLimit(),
        ]));
    }
}

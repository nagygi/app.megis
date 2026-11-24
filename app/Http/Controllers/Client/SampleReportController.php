<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\View\View;

/**
 * A sample report for an invited member before their own (26 · 26.4): made-up
 * data, marked as a sample; the real one comes after the mégis approved it.
 */
class SampleReportController extends Controller
{
    public function __invoke(Organization $organization): View
    {
        return view('client.sample-report', ['organization' => $organization]);
    }
}

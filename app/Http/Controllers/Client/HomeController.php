<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The organization's home. The diagnosis pages (23, 25, 26) replace its content.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, Organization $organization): View
    {
        return view('client.home', [
            'organization' => $organization,
            'user' => $request->user(),
        ]);
    }
}

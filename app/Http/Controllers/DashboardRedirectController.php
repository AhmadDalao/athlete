<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        return redirect($request->user()->landingPath());
    }
}

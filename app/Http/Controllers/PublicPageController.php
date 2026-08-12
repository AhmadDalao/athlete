<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function features(): View
    {
        abort_unless(PlatformSetting::enabled('public_features_enabled', true), 404);

        return view('public.features');
    }

    public function pricing(): View
    {
        abort_unless(PlatformSetting::enabled('public_pricing_enabled', true), 404);

        return view('public.pricing');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteHomeController extends Controller
{
    public function __invoke(): Response
    {
        $settings = PlatformSetting::values();

        return Inertia::render('welcome', [
            'content' => [
                'eyebrow' => $settings['home_eyebrow'],
                'headline' => $settings['home_headline'],
                'subheadline' => $settings['home_subheadline'],
            ],
        ]);
    }
}

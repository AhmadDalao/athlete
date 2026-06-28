<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ContactPageController extends Controller
{
    public function __invoke(): Response
    {
        $requestedCoach = trim(request()->string('coach')->value());
        $goal = trim(request()->string('goal')->value());

        return Inertia::render('contact', [
            'prefill' => [
                'requestedCoach' => $requestedCoach !== '' ? $requestedCoach : null,
                'message' => $requestedCoach === ''
                    ? null
                    : trim("I want to learn more about coaching with {$requestedCoach}".($goal !== '' ? " around {$goal}" : '').'.'),
            ],
        ]);
    }
}

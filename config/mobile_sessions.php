<?php

return [
    'standard_lifetime_minutes' => (int) env('MOBILE_SESSION_LIFETIME', 720),
    'remembered_lifetime_minutes' => (int) env('MOBILE_REMEMBERED_SESSION_LIFETIME', 129600),
];

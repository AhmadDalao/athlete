<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('throughline:about', function (): void {
    $this->info('Throughline clean coaching MVP');
});

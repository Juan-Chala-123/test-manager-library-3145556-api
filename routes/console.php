<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('library:ensure-admin', function (): void {
    $username = strtolower(trim((string) env('ADMIN_USERNAME')));
    $password = (string) env('ADMIN_PASSWORD');
    if ($username === '' || $password === '') {
        $this->error('ADMIN_USERNAME y ADMIN_PASSWORD son obligatorios.');
        return;
    }

    DB::table('library.user')->updateOrInsert(
        ['username' => $username],
        ['id' => (string) Str::uuid(), 'password_hash' => password_hash($password, PASSWORD_ARGON2ID), 'role' => 'admin']
    );
    $this->info('Administrador inicial configurado.');
});

use Illuminate\Foundation\Inspiring;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

<?php

declare(strict_types=1);

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'public_password' => 'The demo account password only works from this computer (localhost). For an online site, set your own ADMIN_EMAIL and ADMIN_PASSWORD in .env, run php artisan db:seed --force, then sign in with that account.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
];

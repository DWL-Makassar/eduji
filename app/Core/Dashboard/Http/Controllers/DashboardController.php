<?php

declare(strict_types=1);

namespace App\Core\Dashboard\Http\Controllers;

use App\Core\Dashboard\WidgetRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(Request $request, WidgetRegistry $widgets): Response
    {
        return Inertia::render('admin/Dashboard', [
            'widgets' => $widgets->resolveFor($request->user()),
        ]);
    }
}

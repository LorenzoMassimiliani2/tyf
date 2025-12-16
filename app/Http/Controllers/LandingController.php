<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        return Inertia::render('Home', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function game(string $code)
    {
        return Inertia::render('Game', [
            'code' => strtoupper($code),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        return Inertia::render('Home');
    }

    public function host()
    {
        return Inertia::render('Host', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function join()
    {
        return Inertia::render('Join');
    }

    public function game(string $code)
    {
        return Inertia::render('Game', [
            'code' => strtoupper($code),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChallengeController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Challenges', [
            'challenges' => Challenge::with('category')->orderByDesc('id')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'is_active' => ['boolean'],
        ]);

        Challenge::create($data);

        return redirect()->back()->with('success', 'Prova creata.');
    }

    public function update(Request $request, Challenge $challenge)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'is_active' => ['boolean'],
        ]);

        $challenge->update($data);

        return redirect()->back()->with('success', 'Prova aggiornata.');
    }

    public function destroy(Challenge $challenge)
    {
        $challenge->delete();

        return redirect()->back()->with('success', 'Prova eliminata.');
    }
}

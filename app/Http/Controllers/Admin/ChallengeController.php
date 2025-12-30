<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChallengeController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'category_id' => $request->integer('category_id'),
            'level' => $request->integer('level'),
        ];

        $challenges = Challenge::with('category')
            ->when($filters['category_id'], fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['level'], fn ($query, $level) => $query->where('level', $level))
            ->orderBy('level')
            ->orderBy('category_id')
            ->orderByDesc('id')
            ->get();

        $stats = Challenge::selectRaw('category_id, level, COUNT(*) as total')
            ->groupBy('category_id', 'level')
            ->orderBy('level')
            ->orderBy('category_id')
            ->get();

        return Inertia::render('Admin/Challenges', [
            'challenges' => $challenges,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'stats' => $stats,
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

    // Fallback per DELETE senza id in path
    public function destroyByPayload(Request $request)
    {
        $id = $request->input('id');

        if (!$id) {
            return response()->noContent();
        }

        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:challenges,id'],
        ]);

        $challenge = Challenge::find($data['id']);
        $challenge->delete();

        return response()->json(['message' => 'Prova eliminata.']);
    }
}

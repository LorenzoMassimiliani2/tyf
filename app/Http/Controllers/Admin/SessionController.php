<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Inertia\Inertia;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        $games = Game::query()
            ->withCount(['players', 'turns'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($game) {
                return [
                    'id' => $game->id,
                    'code' => $game->code,
                    'status' => $game->status,
                    'players_count' => $game->players_count,
                    'turns_count' => $game->turns_count,
                    'started_at' => optional($game->started_at)->toIso8601String(),
                    'ended_at' => optional($game->ended_at)->toIso8601String(),
                    'created_at' => optional($game->created_at)->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/Sessions', [
            'games' => $games,
        ]);
    }

    public function destroy(Game $game)
    {
        $game->delete();

        return redirect()->back()->with('success', 'Sessione eliminata.');
    }

    // Fallback per chiamate DELETE senza ID nella path
    public function destroyByPayload(Request $request)
    {
        $gameId = $request->input('game_id');
        $code = $request->input('code');

        $game = null;
        if ($gameId) {
            $game = Game::find($gameId);
        } elseif ($code) {
            $game = Game::where('code', $code)->first();
        }

        if ($game) {
            $game->delete();
            return response()->noContent();
        }

        return response()->json(['message' => 'Sessione non trovata.'], 404);
    }
}

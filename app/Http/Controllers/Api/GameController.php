<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Turn;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'host_name' => ['required', 'string', 'max:50'],
            'avatar_url' => ['nullable', 'string', 'max:255'],
            'total_turns' => ['required', 'integer', 'min:0', 'max:200'],
            'starting_difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'difficulty_step_turns' => ['required', 'integer', 'min:1', 'max:20'],
            'candidate_count' => ['required', 'integer', 'min:1', 'max:5'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $game = DB::transaction(function () use ($data) {
            $game = Game::create([
                'code' => $this->generateCode(),
                'status' => 'lobby',
                'total_turns' => $data['total_turns'],
                'starting_difficulty' => $data['starting_difficulty'],
                'difficulty_step_turns' => $data['difficulty_step_turns'],
                'candidate_count' => $data['candidate_count'],
            ]);

            if (!empty($data['category_ids'])) {
                $game->categories()->sync($data['category_ids']);
            }

            $player = $game->players()->create([
                'name' => $data['host_name'],
                'avatar_url' => $data['avatar_url'] ?? null,
                'token' => Str::random(64),
                'is_host' => true,
                'turn_order' => 1,
            ]);

            $game->update(['host_player_id' => $player->id]);

            return $game->load('players', 'categories');
        });

        $host = $game->players->firstWhere('is_host', true);

        return response()->json([
            'game' => $this->gamePayload($game),
            'player' => $this->playerPayload($host),
        ], 201);
    }

    public function join(Request $request, string $code)
    {
        $game = Game::where('code', strtoupper($code))->firstOrFail();

        if ($game->status === 'finished') {
            return response()->json(['message' => 'La partita è finita.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'avatar_url' => ['nullable', 'string', 'max:255'],
        ]);

        $player = DB::transaction(function () use ($game, $data) {
            $existing = $game->players()
                ->get()
                ->first(function ($p) use ($data) {
                    return Str::lower($p->name) === Str::lower($data['name']);
                });

            // Nuovo collegamento per giocatore esistente: va approvato.
            if ($existing) {
                $existing->update([
                    'token' => Str::random(64),
                    'avatar_url' => $data['avatar_url'] ?? $existing->avatar_url,
                    'status' => 'pending',
                ]);

                return $existing;
            }

            $turnOrder = ($game->players()->max('turn_order') ?? 0) + 1;

            $status = $game->status === 'active' ? 'pending' : 'active';

            return $game->players()->create([
                'name' => $data['name'],
                'avatar_url' => $data['avatar_url'] ?? null,
                'token' => Str::random(64),
                'turn_order' => $turnOrder,
                'status' => $status,
            ]);
        });

        return response()->json([
            'game' => $this->gamePayload($game->fresh('players')),
            'player' => $this->playerPayload($player),
            'message' => 'Richiesta inviata, attendi approvazione.',
        ], 201);
    }

    public function start(Request $request, string $code)
    {
        $game = Game::where('code', strtoupper($code))->with('players')->firstOrFail();
        $player = $this->playerFromRequest($request, $game);

        if (!$player) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        if ($game->status !== 'lobby') {
            return response()->json(['message' => 'La partita è già attiva.'], 422);
        }

        // Si consente l'avvio anche con un solo giocatore.

        DB::transaction(function () use ($game) {
            $game->update([
                'status' => 'active',
                'started_at' => now(),
                'current_turn_number' => 1,
            ]);

            if ($game->categories()->count() === 0) {
                $game->categories()->sync(
                    Category::where('is_active', true)->pluck('id')
                );
            }

            $this->ensureTurn($game->fresh(['players', 'categories']));
        });

        return $this->state($request, $code);
    }

    public function state(Request $request, string $code)
    {
        $game = Game::where('code', strtoupper($code))
            ->with([
                'players' => fn ($q) => $q->orderBy('turn_order'),
                'categories',
                'turns.votes',
                'turns.challenge.category',
            ])
            ->firstOrFail();

        $player = $this->playerFromRequest($request, $game);

        if ($game->status === 'active') {
            $this->ensureTurn($game);
            $game->refresh();
        }

        $turn = $game->turns()->latest('turn_number')->first();

        // Se il viewer non è attivo, limitiamo i dati visibili.
        $isActiveViewer = $player && $player->status === 'active';

        return response()->json([
            'game' => $this->gamePayload($game),
            'player' => $this->playerPayload($player),
            'players' => $isActiveViewer
                ? $game->players->where('status', 'active')->map(fn ($p) => $this->playerPayload($p, $game))
                : collect(),
            'turn' => $isActiveViewer && $turn ? $this->turnPayload($turn, $game, $player) : null,
            'leaderboard' => $isActiveViewer ? $this->leaderboard($game) : [],
            'join_requests' => $game->players
                ->where('status', 'pending')
                ->values()
                ->map(fn ($p) => $this->playerPayload($p, $game)),
        ]);
    }

    public function chooseChallenge(Request $request, string $code)
    {
        $game = Game::where('code', strtoupper($code))->with(['players', 'turns.challenge'])->firstOrFail();
        $player = $this->playerFromRequest($request, $game);

        if (!$player) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        $turn = $game->turns()->latest('turn_number')->first();

        if (!$turn || $turn->player_id !== $player->id) {
            return response()->json(['message' => 'Non è il tuo turno.'], 403);
        }

        if ($turn->status !== 'pending') {
            return response()->json(['message' => 'La prova è già stata scelta.'], 422);
        }

        $data = $request->validate([
            'challenge_id' => ['required', 'integer', 'exists:challenges,id'],
        ]);

        $candidates = $turn->candidate_challenges ?? [];

        if (!in_array($data['challenge_id'], $candidates, true)) {
            return response()->json(['message' => 'Prova non disponibile per questo turno.'], 422);
        }

        $challenge = Challenge::find($data['challenge_id']);
        $playerCount = $game->players()->where('status', 'active')->count();

        $turn->update([
            'challenge_id' => $data['challenge_id'],
            'status' => 'voting',
            'selected_at' => now(),
            'difficulty' => $challenge?->level ?? $turn->difficulty,
            'max_score' => ($challenge?->level ?? $turn->difficulty) * $playerCount,
        ]);

        return $this->state($request, $code);
    }

    public function vote(Request $request, string $code)
    {
        $game = Game::where('code', strtoupper($code))->with('players')->firstOrFail();
        $player = $this->playerFromRequest($request, $game);

        if (!$player) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        $turn = $game->turns()->latest('turn_number')->with('votes')->first();

        if (!$turn || $turn->status !== 'voting' || !$turn->challenge_id) {
            return response()->json(['message' => 'Nessun turno in fase di voto.'], 422);
        }

        if ($turn->player_id === $player->id) {
            return response()->json(['message' => 'Non puoi votare il tuo turno.'], 403);
        }

        $data = $request->validate([
            'success' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($turn, $player, $data, $game) {
            Vote::updateOrCreate(
                [
                    'turn_id' => $turn->id,
                    'voter_id' => $player->id,
                ],
                ['success' => $data['success']]
            );

            $turn->refresh();

            $playerCount = $game->players()->count();
            $votesNeeded = max(0, $playerCount - 1);

            if ($turn->votes()->count() >= $votesNeeded) {
                $this->finalizeTurn($turn, $game);
            }
        });

        return $this->state($request, $code);
    }

    protected function finalizeTurn(Turn $turn, Game $game): void
    {
        $successCount = $turn->votes()->where('success', true)->count();
        $challengeLevel = $turn->challenge?->level ?? $turn->difficulty;
        $score = $challengeLevel * $successCount;

        $turn->update([
            'score_awarded' => $score,
            'status' => 'scored',
            'completed_at' => now(),
        ]);

        $turn->player()->increment('score', $score);

        $nextNumber = $turn->turn_number + 1;

        if ($game->total_turns > 0 && $nextNumber > $game->total_turns) {
            $game->update(['status' => 'finished', 'ended_at' => now()]);
            return;
        }

        $game->update(['current_turn_number' => $nextNumber]);

        $this->ensureTurn($game);
    }

    protected function ensureTurn(Game $game): ?Turn
    {
        $latest = $game->turns()->latest('turn_number')->first();

        if ($game->status !== 'active') {
            return $latest;
        }

        if ($latest && $latest->status !== 'scored') {
            return $latest;
        }

        if ($game->total_turns > 0 && $game->current_turn_number > $game->total_turns) {
            $game->update(['status' => 'finished', 'ended_at' => now()]);
            return $latest;
        }

        if ($game->players()->where('status', 'active')->count() === 0) {
            return $latest;
        }

        $turnNumber = $latest ? $latest->turn_number + 1 : 1;
        $player = $this->playerForTurn($game, $turnNumber);
        $difficulty = $this->difficultyForTurn($game, $turnNumber);
        $playerCount = $game->players()->where('status', 'active')->count();

        $candidateIds = $this->pickChallenges($game, $difficulty, $game->candidate_count ?? 3);

        return $game->turns()->create([
            'player_id' => $player->id,
            'turn_number' => $turnNumber,
            'difficulty' => $difficulty,
            'status' => 'pending',
            'candidate_challenges' => $candidateIds,
            'max_score' => $difficulty * $playerCount,
        ]);
    }

    protected function playerForTurn(Game $game, int $turnNumber): GamePlayer
    {
        $players = $game->players()->where('status', 'active')->orderBy('turn_order')->get();

        abort_if($players->isEmpty(), 422, 'Nessun giocatore nella partita.');

        $index = ($turnNumber - 1) % $players->count();

        return $players[$index];
    }

    protected function pickChallenges(Game $game, int $difficulty, int $count = 3): array
    {
        $count = max(1, min(5, $count));

        $levels = [];
        for ($i = 0; $i < $count; $i++) {
            $levels[] = min(5, $difficulty + $i);
        }

        $categoryIds = $game->categories()
            ->where('categories.is_active', true)
            ->pluck('categories.id')
            ->all();

        if (empty($categoryIds)) {
            $categoryIds = Category::where('is_active', true)->pluck('id')->all();
        }

        $selected = [];
        $usedCategories = [];

        foreach ($levels as $level) {
            $challenge = Challenge::query()
                ->where('is_active', true)
                ->where('level', $level)
                ->when(!empty($categoryIds), fn ($q) => $q->whereIn('category_id', $categoryIds))
                ->when(!empty($usedCategories), fn ($q) => $q->whereNotIn('category_id', $usedCategories))
                ->inRandomOrder()
                ->first();

            if (!$challenge) {
                $challenge = Challenge::query()
                    ->where('is_active', true)
                    ->where('level', $level)
                    ->when(!empty($categoryIds), fn ($q) => $q->whereIn('category_id', $categoryIds))
                    ->inRandomOrder()
                    ->first();
            }

            if ($challenge) {
                $selected[] = $challenge->id;
                $usedCategories[] = $challenge->category_id;
            }
        }

        if (count($selected) < 3) {
            $fallbacks = Challenge::query()
                ->where('is_active', true)
                ->when(!empty($categoryIds), fn ($q) => $q->whereIn('category_id', $categoryIds))
                ->inRandomOrder()
                ->take($count - count($selected))
                ->pluck('id')
                ->all();

            $selected = array_values(array_unique(array_merge($selected, $fallbacks)));
        }

        return array_slice($selected, 0, $count);
    }

    protected function difficultyForTurn(Game $game, int $turnNumber): int
    {
        $stepIncrease = intdiv(max(0, $turnNumber - 1), max(1, $game->difficulty_step_turns));
        $difficulty = $game->starting_difficulty + $stepIncrease;

        return min(5, $difficulty);
    }

    public function approveJoin(Request $request, string $code, GamePlayer $player)
    {
        $game = Game::where('code', strtoupper($code))->firstOrFail();
        $actor = $this->playerFromRequest($request, $game);

        if (!$actor) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        if ($player->game_id !== $game->id) {
            abort(404);
        }

        if ($player->status !== 'pending') {
            return response()->json(['message' => 'Richiesta già gestita.'], 422);
        }

        $order = ($game->players()->where('status', 'active')->max('turn_order') ?? 0) + 1;
        $player->update([
            'status' => 'active',
            'turn_order' => $order,
        ]);

        return $this->state($request, $code);
    }

    public function rejectJoin(Request $request, string $code, GamePlayer $player)
    {
        $game = Game::where('code', strtoupper($code))->firstOrFail();
        $actor = $this->playerFromRequest($request, $game);

        if (!$actor) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        if ($player->game_id !== $game->id) {
            abort(404);
        }

        if ($player->status !== 'pending') {
            return response()->json(['message' => 'Richiesta già gestita.'], 422);
        }

        $player->delete();

        return $this->state($request, $code);
    }

    public function removePlayer(Request $request, string $code, GamePlayer $player)
    {
        $game = Game::where('code', strtoupper($code))->firstOrFail();
        $actor = $this->playerFromRequest($request, $game);

        if (!$actor) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        if ($player->game_id !== $game->id) {
            abort(404);
        }

        $player->update([
            'status' => 'removed',
            'token' => null,
        ]);

        return $this->state($request, $code);
    }

    public function leave(Request $request, string $code)
    {
        $game = Game::where('code', strtoupper($code))->firstOrFail();
        $player = $this->playerFromRequest($request, $game);

        if (!$player) {
            return response()->json(['message' => 'Giocatore non trovato.'], 401);
        }

        $player->delete();

        return response()->json(['message' => 'Uscito dalla partita.']);
    }

    protected function playerFromRequest(Request $request, Game $game): ?GamePlayer
    {
        $token = $request->header('X-PLAYER-TOKEN') ?? $request->input('player_token');

        if (!$token) {
            return null;
        }

        return $game->players()->where('token', $token)->first();
    }

    protected function gamePayload(Game $game): array
    {
        return [
            'id' => $game->id,
            'code' => $game->code,
            'status' => $game->status,
            'total_turns' => $game->total_turns,
            'starting_difficulty' => $game->starting_difficulty,
            'difficulty_step_turns' => $game->difficulty_step_turns,
            'candidate_count' => $game->candidate_count,
            'current_turn_number' => $game->current_turn_number,
            'started_at' => optional($game->started_at)->toIso8601String(),
            'ended_at' => optional($game->ended_at)->toIso8601String(),
            'categories' => $game->categories()->get()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $c->color,
            ])->values(),
        ];
    }

    protected function playerPayload(?GamePlayer $player, ?Game $game = null): ?array
    {
        if (!$player) {
            return null;
        }

        $turnsPlayed = $game
            ? $game->turns()->where('player_id', $player->id)->count()
            : null;

        return [
            'id' => $player->id,
            'name' => $player->name,
            'avatar_url' => $player->avatar_url,
            'is_host' => $player->is_host,
            'status' => $player->status,
            'score' => $player->score,
            'turn_order' => $player->turn_order,
            'turns_played' => $turnsPlayed,
            'token' => $player->token,
        ];
    }

    protected function turnPayload(Turn $turn, Game $game, ?GamePlayer $viewer): array
    {
        $playerCount = $game->players()->count();
        $candidateIds = $turn->candidate_challenges ?? [];

        $candidates = Challenge::whereIn('id', $candidateIds)
            ->with('category')
            ->get()
            ->map(function ($challenge) use ($playerCount) {
                return [
                    'id' => $challenge->id,
                    'title' => $challenge->title,
                    'description' => $challenge->description,
                    'level' => $challenge->level,
                    'category' => $challenge->category?->name,
                    'category_color' => $challenge->category?->color,
                    'max_score' => $challenge->level * $playerCount,
                ];
            })
            ->values();

        $votes = $turn->votes()->with('voter')->get();

        $waiting = $game->players
            ->where('status', 'active')
            ->where('id', '!=', $turn->player_id)
            ->filter(fn ($p) => !$votes->contains('voter_id', $p->id))
            ->values()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]);

        $selected = null;
        if ($turn->challenge_id) {
            $challenge = Challenge::with('category')->find($turn->challenge_id);
            if ($challenge) {
                $selected = [
                    'id' => $challenge->id,
                    'title' => $challenge->title,
                    'description' => $challenge->description,
                    'level' => $challenge->level,
                    'category' => $challenge->category?->name,
                    'category_color' => $challenge->category?->color,
                    'max_score' => $challenge->level * $playerCount,
                ];
            }
        }

        return [
            'id' => $turn->id,
            'number' => $turn->turn_number,
            'difficulty' => $turn->difficulty,
            'status' => $turn->status,
            'player' => $this->playerPayload($turn->player, $game),
            'candidates' => $candidates,
            'selected_challenge' => $selected,
            'votes' => $votes->map(fn ($vote) => [
                'voter_id' => $vote->voter_id,
                'voter_name' => $vote->voter?->name,
                'success' => $vote->success,
            ])->values(),
            'waiting_for' => $waiting,
            'can_choose' => $viewer && $viewer->status === 'active' && $viewer->id === $turn->player_id && $turn->status === 'pending',
            'can_vote' => $viewer && $viewer->status === 'active' && $viewer->id !== $turn->player_id && $turn->status === 'voting',
        ];
    }

    protected function leaderboard(Game $game)
    {
        return $game->players()
            ->where('status', 'active')
            ->get()
            ->map(fn ($player) => [
                'id' => $player->id,
                'name' => $player->name,
                'score' => $player->score,
                'turns' => $game->turns()->where('player_id', $player->id)->count(),
                'avatar_url' => $player->avatar_url,
            ])
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    protected function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(5));
        } while (Game::where('code', $code)->exists());

        return $code;
    }
}

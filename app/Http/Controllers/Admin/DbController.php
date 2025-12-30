<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DbController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Database', $this->props($request));
    }

    public function query(Request $request)
    {
        $sql = trim($request->input('sql', ''));

        if ($sql === '') {
            return redirect()->back()->with('query_error', 'Inserisci una query.')->withInput();
        }

        if (!preg_match('/^\\s*select/i', $sql)) {
            return redirect()->back()->with('query_error', 'Sono consentite solo query SELECT.')->withInput();
        }

        try {
            $result = DB::select($sql);
            return redirect()
                ->route('admin.db.index')
                ->with('query_result', $result)
                ->with('query_sql', $sql);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('query_error', $e->getMessage())
                ->withInput();
        }
    }

    protected function props(Request $request): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        $sizeBytes = null;

        if ($driver === 'mysql') {
            $row = DB::selectOne('SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS size FROM information_schema.TABLES WHERE table_schema = ?', [$database]);
            $sizeBytes = $row?->size ? (int) $row->size : null;
        } elseif ($driver === 'sqlite') {
            $pageCount = DB::selectOne('PRAGMA page_count');
            $pageSize = DB::selectOne('PRAGMA page_size');
            if ($pageCount && $pageSize) {
                $sizeBytes = (int) $pageCount->page_count * (int) $pageSize->page_size;
            }
        }

        return [
            'db' => [
                'driver' => $driver,
                'database' => $database,
                'size_bytes' => $sizeBytes,
                'size_human' => $sizeBytes ? $this->formatBytes($sizeBytes) : 'N/D',
            ],
            'query_result' => $request->session()->get('query_result'),
            'query_sql' => $request->session()->get('query_sql'),
            'query_error' => $request->session()->get('query_error'),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}

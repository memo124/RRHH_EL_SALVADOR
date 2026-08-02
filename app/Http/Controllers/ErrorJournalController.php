<?php

namespace App\Http\Controllers;

use App\Services\ErrorJournalService;
use Illuminate\Http\Request;

class ErrorJournalController extends Controller
{
    public function __construct(protected ErrorJournalService $journal)
    {
    }

    public function index()
    {
        return response()->json([
            'files' => $this->journal->listFiles(),
            'max_files' => 3,
            'days_per_file' => 3,
        ]);
    }

    public function show(Request $request, string $filename)
    {
        return response()->json($this->journal->getJournal($filename));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;

class AnalyzeController extends Controller
{
    private SentimentAnalysisService $sentimentService;

    public function __construct(SentimentAnalysisService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    /**
     * Analyser un texte et retourner sentiment, score et topics
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:10',
        ]);

        $analysis = $this->sentimentService->analyze($request->text);

        return response()->json([
            'sentiment' => $analysis['sentiment'],
            'score' => $analysis['score'],
            'topics' => $analysis['topics'],
        ]);
    }
}

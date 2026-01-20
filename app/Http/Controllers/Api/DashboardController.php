<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Récupérer les statistiques du dashboard
     */
    public function stats(Request $request)
    {
        // Seuls les admins peuvent voir toutes les stats
        $query = Review::query();
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $totalReviews = $query->count();
        
        if ($totalReviews === 0) {
            return response()->json([
                'total_reviews' => 0,
                'positive_percentage' => 0,
                'negative_percentage' => 0,
                'neutral_percentage' => 0,
                'average_score' => 0,
                'top_topics' => [],
                'latest_reviews' => [],
            ]);
        }

        // Calcul des pourcentages de sentiment
        $positiveCount = (clone $query)->where('sentiment', 'positif')->count();
        $negativeCount = (clone $query)->where('sentiment', 'négatif')->count();
        $neutralCount = (clone $query)->where('sentiment', 'neutre')->count();

        $positivePercentage = round(($positiveCount / $totalReviews) * 100, 2);
        $negativePercentage = round(($negativeCount / $totalReviews) * 100, 2);
        $neutralPercentage = round(($neutralCount / $totalReviews) * 100, 2);

        // Score moyen
        $averageScore = round((clone $query)->avg('score'), 2);

        // Top topics
        $allTopics = (clone $query)->get()->pluck('topics')->flatten();
        $topTopics = $allTopics->countBy()->sortDesc()->take(5)->map(function ($count, $topic) {
            return [
                'topic' => $topic,
                'count' => $count,
            ];
        })->values();

        // Dernières reviews
        $latestReviews = (clone $query)
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'total_reviews' => $totalReviews,
            'positive_percentage' => $positivePercentage,
            'negative_percentage' => $negativePercentage,
            'neutral_percentage' => $neutralPercentage,
            'average_score' => $averageScore,
            'top_topics' => $topTopics,
            'latest_reviews' => $latestReviews,
        ]);
    }
}

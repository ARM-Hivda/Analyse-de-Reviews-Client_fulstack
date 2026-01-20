<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    private SentimentAnalysisService $sentimentService;

    public function __construct(SentimentAnalysisService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    /**
     * Liste toutes les reviews avec pagination
     */
    public function index(Request $request)
    {
        $query = Review::with('user');

        // Les users normaux ne voient que leurs propres reviews
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        // Pagination : 10 reviews par page par défaut
        $perPage = $request->get('per_page', 10);
        $reviews = $query->latest()->paginate($perPage);

        return response()->json($reviews);
    }

    /**
     * Créer une nouvelle review avec analyse IA automatique
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|min:10',
        ]);

        // Analyse IA automatique
        $analysis = $this->sentimentService->analyze($request->content);

        $review = Review::create([
            'content' => $request->content,
            'user_id' => $request->user()->id,
            'sentiment' => $analysis['sentiment'],
            'score' => $analysis['score'],
            'topics' => $analysis['topics'],
        ]);

        $review->load('user');

        return response()->json($review, 201);
    }

    /**
     * Afficher une review spécifique
     */
    public function show(Request $request, string $id)
    {
        $review = Review::with('user')->findOrFail($id);

        // Vérifier que l'utilisateur peut voir cette review
        if (!$request->user()->isAdmin() && $review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json($review);
    }

    /**
     * Mettre à jour une review
     */
    public function update(Request $request, string $id)
    {
        $review = Review::findOrFail($id);

        // Vérifier les permissions
        if (!$request->user()->isAdmin() && $review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'content' => 'required|string|min:10',
        ]);

        // Toujours réanalyser le contenu lors de la mise à jour
        $analysis = $this->sentimentService->analyze($request->content);
        
        $review->content = $request->content;
        $review->sentiment = $analysis['sentiment'];
        $review->score = $analysis['score'];
        $review->topics = $analysis['topics'];
        $review->save();

        $review->load('user');

        return response()->json($review);
    }

    /**
     * Supprimer une review
     */
    public function destroy(Request $request, string $id)
    {
        $review = Review::findOrFail($id);

        // Seuls les admins peuvent supprimer n'importe quelle review
        // Les users peuvent supprimer leurs propres reviews
        if (!$request->user()->isAdmin() && $review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review supprimée avec succès'], 200);
    }
}

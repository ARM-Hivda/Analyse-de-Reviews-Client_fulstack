<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use App\Services\SentimentAnalysisService;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    private SentimentAnalysisService $sentimentService;

    public function __construct()
    {
        $this->sentimentService = new SentimentAnalysisService();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé. Créez d\'abord des utilisateurs.');
            return;
        }

        $sampleReviews = [
            // Positifs
            'Excellent produit, livraison rapide et qualité au rendez-vous ! Je recommande vivement.',
            'Service client très professionnel, réponse rapide et efficace. Très satisfait de mon achat.',
            'Produit de très bonne qualité, conforme à la description. Livraison dans les délais.',
            'Super expérience ! Le produit dépasse mes attentes. Prix très compétitif.',
            'Très bon rapport qualité-prix. Service impeccable et livraison express.',
            'Produit excellent, finition soignée. Je suis ravi de mon achat.',
            'Service au top, équipe réactive et professionnelle. Je recommande sans hésitation.',
            'Qualité irréprochable, produit durable. Livraison rapide et soignée.',
            'Très satisfait, produit conforme et livraison dans les temps. Merci !',
            'Excellent service, produit de qualité supérieure. Je reviendrai certainement.',
            
            // Négatifs
            'Je suis déçu, le produit est arrivé cassé et le service client n\'a pas répondu à mes questions.',
            'Livraison très en retard, produit de mauvaise qualité. Pas satisfait du tout.',
            'Service client inexistant, produit défectueux. Je ne recommande pas.',
            'Très déçu par la qualité, le produit ne correspond pas à la description.',
            'Livraison problématique, produit endommagé. Service client peu réactif.',
            'Qualité médiocre, produit fragile. Je suis très insatisfait.',
            'Problème de livraison, produit incomplet. Service client lent à répondre.',
            'Déçu par la qualité du produit, ne correspond pas aux attentes.',
            'Service client déplorable, produit de mauvaise qualité. À éviter.',
            'Livraison retardée, produit défectueux. Très mauvaise expérience.',
            
            // Neutres
            'Produit reçu dans les délais. Correspond à la description. Rien de spécial.',
            'Livraison correcte, produit standard. Pas de problème particulier.',
            'Produit conforme, livraison normale. Expérience correcte sans plus.',
            'Service standard, produit basique. Rien à redire mais rien d\'exceptionnel.',
            'Livraison dans les temps, produit correct. Expérience moyenne.',
        ];

        $this->command->info('Création de reviews...');

        foreach ($sampleReviews as $index => $content) {
            $user = $users->random();
            
            // Analyse IA automatique
            $analysis = $this->sentimentService->analyze($content);

            Review::create([
                'user_id' => $user->id,
                'content' => $content,
                'sentiment' => $analysis['sentiment'],
                'score' => $analysis['score'],
                'topics' => $analysis['topics'],
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            if (($index + 1) % 10 === 0) {
                $this->command->info("Créé " . ($index + 1) . " reviews...");
            }
        }

        $this->command->info('✅ ' . count($sampleReviews) . ' reviews créées avec succès !');
    }
}



<?php

namespace App\Services;

class SentimentAnalysisService
{
    // Mots-clés pour l'analyse de sentiment
    private array $positiveWords = [
        'excellent', 'super', 'génial', 'parfait', 'merveilleux', 'fantastique',
        'bon', 'bien', 'satisfait', 'content', 'heureux', 'recommandé', 'recommandé',
        'rapide', 'efficace', 'qualité', 'professionnel', 'professionnelle', 'professionnels', 'professionnelles',
        'aimable', 'sympa', 'top', 'extra', 'formidable', 'remarquable', 'impressionnant',
        'dépasse', 'dépasser', 'compétitif', 'compétitifs', 'expérience', 'expériences',
        'réactive', 'réactif', 'réactifs', 'réactives', 'ravi', 'ravie', 'ravis',
        'irréprochable', 'irréprochables', 'durable', 'durables', 'soignée', 'soigné', 'soignés', 'soignées',
        'supérieure', 'supérieur', 'supérieurs', 'supérieures', 'reviendrai', 'reviendrons', 'reviendront',
        'sans hésitation', 'certainement', 'sûrement'
    ];
    
    // Mots neutres (ni positifs ni négatifs, mais pas négatifs)
    private array $neutralWords = [
        'correcte', 'correct', 'corrects', 'correctes', 'standard', 'standards',
        'normal', 'normale', 'normaux', 'normales', 'acceptable', 'acceptables'
    ];
    
    // Mots de négation qui inversent le sens
    private array $negationWords = [
        'pas de', 'pas d\'', 'sans', 'aucun', 'aucune', 'aucuns', 'aucunes',
        'ne...pas', 'ne pas', 'n\'est pas', 'n\'est pas de',
        'rien de', 'rien d\'', 'jamais de', 'jamais d\''
    ];
    
    // Expressions positives complexes (détectées en premier)
    private array $positivePhrases = [
        'dépasse mes attentes', 'dépasse les attentes', 'dépassé mes attentes',
        'au-delà des attentes', 'au delà des attentes', 'surpasse mes attentes'
    ];
    
    // Expressions neutres-positives (petit bonus, pas très positif)
    private array $neutralPositivePhrases = [
        'pas de problème', 'pas de problèmes', 'sans problème', 'sans problèmes',
        'aucun problème', 'aucune problème', 'aucuns problèmes'
    ];

    private array $negativeWords = [
        'mauvais', 'nul', 'déçu', 'décevant', 'horrible', 'terrible',
        'lent', 'cher', 'trop cher', 'problème', 'problématique', 'problématiques',
        'défaut', 'cassé', 'endommagé', 'endommager', 'endommagés',
        'incompétent', 'médiocre', 'insatisfait', 'déplorable', 'catastrophique',
        'refusé', 'annulé', 'retard', 'erreur', 'bug', 'panne',
        'fragile', 'fragiles', 'cassant', 'cassants', 'délicat', 'délicats',
        // Mots liés à la santé et au bien-être négatif
        'malade', 'mal à l\'aise', 'fatigue', 'fatiguer', 'fatigué', 'mal', 'douleur',
        'souffrance', 'souffrant', 'maladie', 'symptôme', 'fièvre', 'nausée',
        // Mots émotionnels négatifs
        'triste', 'déprimé', 'anxieux', 'stressé', 'inquiet', 'peur', 'angoisse',
        'désespéré', 'désespérant', 'désolé', 'regret', 'honte', 'culpabilité',
        // Autres mots négatifs courants
        'difficile', 'compliqué', 'impossible', 'échec', 'raté', 'perdu',
        'abandonné', 'rejeté', 'ignoré', 'oublié', 'négligé',
        // Service client et réactivité
        'peu réactif', 'pas réactif', 'non réactif', 'lent à répondre',
        'inefficace', 'inefficaces', 'défaillant', 'défaillants',
        // Produits défectueux et service inexistant
        'inexistant', 'inexistante', 'inexistants', 'inexistantes',
        'défectueux', 'défectueuse', 'défectueux', 'défectueuses'
    ];
    
    // Expressions négatives complexes (détectées en premier avec poids important)
    private array $negativePhrases = [
        'ne correspond pas', 'ne correspond', 'pas satisfait', 'pas content',
        'pas bon', 'pas bien', 'ne fonctionne pas', 'ne marche pas',
        'ne dépasse pas', 'ne dépasse', 'ne dépassent pas',
        'peu réactif', 'pas réactif', 'non réactif',
        'ne recommande pas', 'ne recommande', 'ne recommanderai pas', 
        'ne recommanderais pas', 'ne recommanderont pas', 'ne recommanderions pas'
    ];

    // Topics à détecter
    private array $topicKeywords = [
        'livraison' => ['livraison', 'livré', 'expédition', 'transport', 'colis', 'délai', 'rapidité'],
        'prix' => ['prix', 'coût', 'tarif', 'cher', 'bon marché', 'gratuit', 'réduction', 'promotion'],
        'qualité' => ['qualité', 'produit', 'matériau', 'finition', 'durable', 'résistant'],
        'service' => ['service', 'client', 'support', 'accueil', 'conseil', 'aide'],
        'satisfaction' => ['satisfait', 'recommandé', 'avis', 'note', 'étoile']
    ];

    /**
     * Analyse le sentiment d'un texte
     */
    public function analyze(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        
        // Analyse du sentiment
        $sentiment = $this->detectSentiment($text);
        
        // Calcul du score (0-100)
        $score = $this->calculateScore($text);
        
        // Détection des topics
        $topics = $this->detectTopics($text);
        
        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'topics' => $topics
        ];
    }

    /**
     * Vérifie si un mot est dans un contexte de négation
     */
    private function isInNegationContext(string $text, string $word, int $position): bool
    {
        // Chercher les négations avant le mot (dans un rayon de 5 mots)
        $beforeText = substr($text, max(0, $position - 100));
        
        foreach ($this->negationWords as $negation) {
            // Nettoyer la négation pour la recherche
            $cleanNegation = str_replace(['...', '\''], ['', ' '], $negation);
            $negationParts = explode(' ', trim($cleanNegation));
            
            // Vérifier si une négation précède le mot
            foreach ($negationParts as $part) {
                if (str_contains($beforeText, $part)) {
                    // Vérifier la distance (max 5 mots entre négation et mot)
                    $wordsBefore = explode(' ', $beforeText);
                    $wordIndex = array_search($word, $wordsBefore);
                    if ($wordIndex !== false && $wordIndex < 5) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Détecte le sentiment (positif, neutre, négatif)
     */
    private function detectSentiment(string $text): string
    {
        $positiveCount = 0;
        $negativeCount = 0;
        
        // Détecter d'abord les expressions positives complexes
        foreach ($this->positivePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $positiveCount += 2; // Poids plus important pour les phrases
            }
        }
        
        // Détecter les expressions neutres-positives (petit bonus)
        foreach ($this->neutralPositivePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $positiveCount += 1; // Petit bonus pour "pas de problème"
            }
        }
        
        // Détecter d'abord les expressions négatives complexes (poids important)
        foreach ($this->negativePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $negativeCount += 2; // Poids plus important pour les phrases négatives
            }
        }
        
        // Détecter les mots positifs
        foreach ($this->positiveWords as $word) {
            // Ignorer les phrases déjà détectées
            if (!in_array($word, $this->positivePhrases) && str_contains($text, $word)) {
                $positiveCount++;
            }
        }
        
        // Détecter les mots négatifs (en excluant ceux dans un contexte de négation)
        foreach ($this->negativeWords as $word) {
            // Ignorer les phrases déjà détectées
            if (!in_array($word, $this->negativePhrases) && str_contains($text, $word)) {
                // Vérifier si le mot est dans un contexte de négation
                $position = mb_strpos($text, $word);
                if ($position !== false) {
                    // Vérifier si c'est dans "pas de problème" ou similaire
                    $contextBefore = mb_substr($text, max(0, $position - 20), 20);
                    $hasNegation = false;
                    
                    foreach ($this->negationWords as $negation) {
                        $cleanNegation = str_replace(['...', '\''], ['', ' '], $negation);
                        if (str_contains($contextBefore, $cleanNegation)) {
                            $hasNegation = true;
                            $positiveCount++; // Inverser : négation + mot négatif = positif
                            break;
                        }
                    }
                    
                    // Vérifier si le mot est précédé d'un amplificateur négatif
                    $amplifiers = ['très', 'extrêmement', 'vraiment', 'totalement', 'complètement'];
                    $hasAmplifier = false;
                    foreach ($amplifiers as $amplifier) {
                        if (str_contains($contextBefore, $amplifier)) {
                            $hasAmplifier = true;
                            $negativeCount += 1; // Bonus pour amplificateur + mot négatif
                            break;
                        }
                    }
                    
                    if (!$hasNegation && !$hasAmplifier) {
                        $negativeCount++;
                    }
                } else {
                    $negativeCount++;
                }
            }
        }
        
        // Si les compteurs sont très proches, considérer comme neutre
        $difference = abs($positiveCount - $negativeCount);
        if ($difference <= 1 && ($positiveCount + $negativeCount) < 3) {
            return 'neutre';
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positif';
        } elseif ($negativeCount > $positiveCount) {
            return 'négatif';
        }
        
        return 'neutre';
    }

    /**
     * Calcule un score de 0 à 100
     */
    private function calculateScore(string $text): int
    {
        $positiveCount = 0;
        $negativeCount = 0;
        $totalWords = str_word_count($text);
        
        if ($totalWords === 0) {
            return 50; // Score neutre par défaut
        }
        
        // Compter les expressions positives complexes (poids x3)
        foreach ($this->positivePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $positiveCount += 3; // Poids important pour les phrases positives
            }
        }
        
        // Compter les expressions neutres-positives (poids x1 - petit bonus)
        foreach ($this->neutralPositivePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $positiveCount += 1; // Petit bonus pour "pas de problème"
            }
        }
        
        // Compter les expressions négatives complexes (poids x3)
        foreach ($this->negativePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $negativeCount += 3; // Poids important pour les phrases négatives
            }
        }
        
        // Compter les mots positifs
        foreach ($this->positiveWords as $word) {
            // Ignorer les phrases déjà comptées
            if (!in_array($word, $this->positivePhrases) && !in_array($word, $this->neutralPositivePhrases)) {
                $positiveCount += substr_count($text, $word);
            }
        }
        
        // Compter les mots neutres (très petit bonus, presque neutre)
        $neutralCount = 0;
        foreach ($this->neutralWords as $word) {
            $neutralCount += substr_count($text, $word);
        }
        // Les mots neutres n'ajoutent presque rien au positif
        $positiveCount += $neutralCount * 0.3; // Très faible poids
        
        // Compter les mots négatifs (en excluant ceux dans un contexte de négation)
        foreach ($this->negativeWords as $word) {
            // Ignorer les phrases déjà comptées
            if (!in_array($word, $this->negativePhrases)) {
                $count = substr_count($text, $word);
                if ($count > 0) {
                    // Vérifier chaque occurrence pour voir si elle est dans un contexte de négation ou d'amplification
                    $offset = 0;
                    $foundInNegation = false;
                    $amplifiers = ['très', 'extrêmement', 'vraiment', 'totalement', 'complètement'];
                    while (($pos = mb_strpos($text, $word, $offset)) !== false) {
                        $contextBefore = mb_substr($text, max(0, $pos - 20), 20);
                        foreach ($this->negationWords as $negation) {
                            $cleanNegation = str_replace(['...', '\''], ['', ' '], $negation);
                            if (str_contains($contextBefore, $cleanNegation)) {
                                $positiveCount++; // Inverser : négation + mot négatif = positif
                                $foundInNegation = true;
                                break;
                            }
                        }
                        // Vérifier si le mot est précédé d'un amplificateur négatif
                        foreach ($amplifiers as $amplifier) {
                            if (str_contains($contextBefore, $amplifier)) {
                                $negativeCount += 2; // Poids important pour amplificateur + mot négatif
                                break;
                            }
                        }
                        $offset = $pos + mb_strlen($word);
                    }
                    if (!$foundInNegation) {
                        $negativeCount += $count;
                    }
                }
            }
        }
        
        // Détecter les amplificateurs positifs
        if (str_contains($text, 'très') && $positiveCount > 0) {
            $positiveCount += 1; // Bonus pour "très" + mot positif
        }
        
        // Calcul du score basé sur le ratio positif/négatif
        // Utiliser un calcul plus sensible pour les textes courts
        $difference = $positiveCount - $negativeCount;
        
        // Si le texte contient principalement des mots neutres et "pas de problème", score proche de 50
        $hasNeutralPhrase = false;
        foreach ($this->neutralPositivePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                $hasNeutralPhrase = true;
                break;
            }
        }
        
        $neutralWordCount = 0;
        foreach ($this->neutralWords as $word) {
            $neutralWordCount += substr_count($text, $word);
        }
        
        // Si le texte est principalement neutre (mots neutres + "pas de problème"), score proche de 50
        if ($hasNeutralPhrase && $neutralWordCount > 0 && abs($difference) < 3) {
            $score = 50 + ($difference * 2); // Petit ajustement autour de 50 (max ±6 points)
        } elseif ($neutralWordCount > 0 && $positiveCount <= 2 && $negativeCount == 0) {
            // Texte avec seulement des mots neutres, score proche de 50
            $score = 50 + ($positiveCount * 2); // Max 54 pour texte neutre
        } elseif ($difference > 0) {
            // Score positif : base 50 + bonus selon le ratio
            // Utiliser un calcul plus généreux pour les textes positifs
            $ratio = min($difference / max($totalWords * 0.7, 1), 1.0); // Ratio plus favorable
            $score = 50 + ($ratio * 40); // Base score
            
            // Bonus supplémentaire pour les textes très positifs (plusieurs mots positifs)
            if ($positiveCount >= 3) {
                $score += 8; // Bonus de 8 points pour 3+ mots positifs
            }
            if ($positiveCount >= 5) {
                $score += 10; // Bonus supplémentaire de 10 points pour 5+ mots positifs
            }
            if ($positiveCount >= 7) {
                $score += 5; // Bonus supplémentaire de 5 points pour 7+ mots positifs
            }
            
            // Bonus pour les expressions positives complexes
            $hasPositivePhrase = false;
            foreach ($this->positivePhrases as $phrase) {
                if (str_contains($text, $phrase)) {
                    $score += 5; // Bonus pour expressions positives
                    $hasPositivePhrase = true;
                    break;
                }
            }
            
            // Bonus pour les amplificateurs positifs ("très" + mot positif)
            $amplifiers = ['très', 'extrêmement', 'vraiment', 'totalement', 'complètement'];
            $hasAmplifier = false;
            foreach ($amplifiers as $amplifier) {
                if (str_contains($text, $amplifier)) {
                    // Vérifier si l'amplificateur précède un mot positif
                    foreach ($this->positiveWords as $positiveWord) {
                        if (str_contains($text, $amplifier . ' ' . $positiveWord)) {
                            $score += 8; // Bonus important pour amplificateur + mot positif
                            $hasAmplifier = true;
                            break 2;
                        }
                    }
                }
            }
            
            // Bonus pour les mots très positifs
            $veryPositiveWords = ['excellent', 'parfait', 'irréprochable', 'fantastique', 'merveilleux', 'génial'];
            $veryPositiveCount = 0;
            foreach ($veryPositiveWords as $word) {
                if (str_contains($text, $word)) {
                    $veryPositiveCount++;
                }
            }
            if ($veryPositiveCount > 0) {
                $score += min($veryPositiveCount * 2, 6); // Bonus limité pour éviter les scores à 100
            }
            
            // Limiter le score maximum à 95 pour éviter les scores parfaits
            $score = min($score, 95);
        } elseif ($difference < 0) {
            // Score négatif : base 50 - pénalité selon le ratio
            $ratio = min(abs($difference) / max($totalWords, 1), 1.0);
            $score = 50 - ($ratio * 45); // Min 5 pour très négatif
            
            // Pénalité supplémentaire pour les amplificateurs négatifs
            $amplifiers = ['très', 'extrêmement', 'vraiment', 'totalement', 'complètement'];
            $hasAmplifier = false;
            foreach ($amplifiers as $amplifier) {
                if (str_contains($text, $amplifier)) {
                    // Vérifier si l'amplificateur précède un mot négatif
                    foreach ($this->negativeWords as $negativeWord) {
                        if (str_contains($text, $amplifier . ' ' . $negativeWord) || 
                            str_contains($text, $amplifier . ' ' . mb_substr($negativeWord, 0, -1))) {
                            $hasAmplifier = true;
                            break 2; // Sortir des deux boucles
                        }
                    }
                }
            }
            if ($hasAmplifier) {
                $score = max(5, $score - 15); // Pénalité supplémentaire de 15 points
            }
        } else {
            $score = 50; // Neutre
        }
        
        // Limitation entre 0 et 100
        return max(0, min(100, (int) round($score)));
    }

    /**
     * Détecte les topics mentionnés dans le texte
     */
    private function detectTopics(string $text): array
    {
        $detectedTopics = [];
        
        foreach ($this->topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    if (!in_array($topic, $detectedTopics)) {
                        $detectedTopics[] = $topic;
                    }
                    break; // Un seul mot-clé suffit pour détecter le topic
                }
            }
        }
        
        return $detectedTopics;
    }
}


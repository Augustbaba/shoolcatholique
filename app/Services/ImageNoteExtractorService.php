<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🤖 SERVICE IA — Extraction de notes depuis une image
 * API Mistral Vision (pixtral-12b-2409)
 *
 * FIXES :
 *  - ?string  → évite TypeError quand MISTRAL_API_KEY absent
 *  - Compression GD avant envoi → évite timeout 30s
 *  - set_time_limit(120) → étend PHP pour requêtes longues
 */
class ImageNoteExtractorService
{
    protected ?string $apiKey;                        // ← ?string OBLIGATOIRE (nullable)
    protected string  $baseUrl = 'https://api.mistral.ai/v1';
    protected string  $model   = 'pixtral-12b-2409';

    const MAX_IMAGE_PX = 1200; // pixels max (côté le plus long)
    const JPEG_QUALITY = 75;   // qualité JPEG après redim

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key') ?: null;

        if (!$this->apiKey) {
            Log::error('🚨 MISTRAL_API_KEY manquante — vérifiez .env + php artisan config:clear');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // EXTRACTION DEPUIS IMAGE
    // ─────────────────────────────────────────────────────────────────

    public function extractNotesFromImage(string $imagePath, array $context = []): array
    {
        @set_time_limit(120); // étendre PHP à 120s pour cette requête

        try {
            if (!$this->apiKey) {
                return $this->error(
                    'Clé API Mistral non configurée. ' .
                    'Vérifiez que MISTRAL_API_KEY est dans .env puis lancez : php artisan config:clear'
                );
            }

            if (!file_exists($imagePath)) {
                return $this->error("Image introuvable : $imagePath");
            }

            // Compresser avant envoi (évite le timeout)
            $compressed = $this->compressImage($imagePath);
            $b64        = base64_encode(file_get_contents($compressed));
            $imageUrl   = 'data:image/jpeg;base64,' . $b64;
            if ($compressed !== $imagePath) @unlink($compressed);

            Log::info('📷 Base64 image: ' . round(strlen($b64) / 1024) . ' KB');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($this->apiKey),
                'Content-Type'  => 'application/json',
            ])
            ->timeout(90)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->model,
                'max_tokens'  => 1500,
                'temperature' => 0.1,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es un assistant expert en lecture de documents scolaires. Réponds UNIQUEMENT en JSON valide brut, sans markdown ni backticks.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                            ['type' => 'text',      'text'      => $this->buildPrompt($context)],
                        ],
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('❌ Mistral HTTP ' . $response->status() . ': ' . $response->body());
                return $this->error('Erreur API Mistral HTTP ' . $response->status());
            }

            $content = $response->json('choices.0.message.content', '');
            Log::info('📝 Mistral réponse: ' . substr($content, 0, 300));

            return $this->parseResponse($content);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('❌ Mistral timeout: ' . $e->getMessage());
            return $this->error('Délai dépassé. Réessayez avec une photo plus petite.');
        } catch (\Exception $e) {
            Log::error('❌ ImageNoteExtractor: ' . $e->getMessage());
            return $this->error($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // COMPRESSION IMAGE via GD
    // ─────────────────────────────────────────────────────────────────

    protected function compressImage(string $src): string
    {
        if (!extension_loaded('gd')) {
            Log::warning('⚠️ GD absent — image envoyée sans compression');
            return $src;
        }

        try {
            $ext  = strtolower(pathinfo($src, PATHINFO_EXTENSION));
            $img  = match($ext) {
                'png'  => @imagecreatefrompng($src),
                'gif'  => @imagecreatefromgif($src),
                'webp' => @imagecreatefromwebp($src),
                default => @imagecreatefromjpeg($src),
            };

            if (!$img) return $src;

            $w = imagesx($img);
            $h = imagesy($img);

            if ($w > self::MAX_IMAGE_PX || $h > self::MAX_IMAGE_PX) {
                $ratio = min(self::MAX_IMAGE_PX / $w, self::MAX_IMAGE_PX / $h);
                $nw    = (int)($w * $ratio);
                $nh    = (int)($h * $ratio);
                $dst   = imagecreatetruecolor($nw, $nh);
                // fond blanc pour PNG transparents
                imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocate($dst, 255, 255, 255));
                imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img);
                $img = $dst;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'ccpa_') . '.jpg';
            imagejpeg($img, $tmp, self::JPEG_QUALITY);
            imagedestroy($img);

            Log::info("✅ Image compressée → " . round(filesize($tmp) / 1024) . ' KB');
            return $tmp;

        } catch (\Throwable $e) {
            Log::warning('⚠️ Compression échouée: ' . $e->getMessage());
            return $src;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // PROMPT
    // ─────────────────────────────────────────────────────────────────

    protected function buildPrompt(array $context): string
    {
        $ctx = '';
        if (!empty($context['classe']))    $ctx .= "- Classe : {$context['classe']}\n";
        if (!empty($context['matiere']))   $ctx .= "- Matière : {$context['matiere']}\n";
        if (!empty($context['periode']))   $ctx .= "- Période : {$context['periode']}\n";
        if (!empty($context['type_note'])) $ctx .= "- Type : {$context['type_note']}\n";
        $ctxBlock = $ctx ? "Contexte connu :\n$ctx\n" : '';

        return <<<TXT
{$ctxBlock}
Analyse cette image de fiche de notes scolaires et extrais toutes les données visibles.

Réponds UNIQUEMENT avec ce JSON brut (sans backticks, sans explication) :
{
  "success": true,
  "metadata": {"classe":"","matiere":"","periode":"","type_note":"","enseignant":""},
  "notes": [
    {"matricule":"","nom":"","prenom":"","note":15.5,"commentaire":""}
  ],
  "confidence": "high",
  "warnings": []
}

RÈGLES :
- note = null si absent ou illisible (jamais inventé)
- notes entre 0 et 20 uniquement
- Noms en MAJUSCULES
- JSON BRUT UNIQUEMENT
TXT;
    }

    // ─────────────────────────────────────────────────────────────────
    // PARSE + NORMALIZE
    // ─────────────────────────────────────────────────────────────────

    protected function parseResponse(string $content): array
    {
        $text = trim($content);

        foreach ([
            fn($t) => json_decode($t, true),
            fn($t) => preg_match('/```(?:json)?\s*(.*?)\s*```/s', $t, $m) ? json_decode($m[1], true) : null,
            fn($t) => preg_match('/\{.*\}/s', $t, $m) ? json_decode($m[0], true) : null,
        ] as $try) {
            $json = $try($text);
            if (is_array($json) && !empty($json)) {
                return $this->normalize($json);
            }
        }

        return $this->error('Réponse IA illisible. Réessayez avec une photo plus nette.');
    }

    protected function normalize(array $data): array
    {
        $notes = [];
        foreach (($data['notes'] ?? []) as $n) {
            $notes[] = [
                'matricule'   => strtoupper(trim($n['matricule'] ?? '')),
                'nom'         => strtoupper(trim($n['nom'] ?? '')),
                'prenom'      => ucwords(strtolower(trim($n['prenom'] ?? ''))),
                'note'        => isset($n['note']) && is_numeric($n['note'])
                                    ? round((float)$n['note'], 2)
                                    : null,
                'commentaire' => trim($n['commentaire'] ?? ''),
            ];
        }

        return [
            'success'    => $data['success'] ?? true,
            'metadata'   => $data['metadata'] ?? [],
            'notes'      => $notes,
            'confidence' => $data['confidence'] ?? 'medium',
            'warnings'   => $data['warnings'] ?? [],
            'errors'     => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // MATCH AVEC LA BDD
    // ─────────────────────────────────────────────────────────────────

    public function matchWithDatabase(array $extractedNotes, array $dbEleves): array
    {
        $byMat  = [];
        $byName = [];
        foreach ($dbEleves as $e) {
            $byMat[strtoupper(trim($e['matricule']))]  = $e;
            $key = strtoupper(trim($e['nom'])) . '_' . strtoupper(trim($e['prenom']));
            $byName[$key] = $e;
        }

        $matched = [];
        $unmatched = [];

        foreach ($extractedNotes as $en) {
            $mat   = strtoupper(trim($en['matricule'] ?? ''));
            $nkey  = strtoupper(trim($en['nom'] ?? '')) . '_' . strtoupper(trim($en['prenom'] ?? ''));
            $found = $byMat[$mat] ?? $byName[$nkey] ?? null;

            if ($found) {
                $matched[$found['id']] = [
                    'valeur'      => $en['note'],
                    'commentaire' => $en['commentaire'] ?? '',
                ];
            } else {
                $unmatched[] = $en;
            }
        }

        return compact('matched', 'unmatched');
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────

    protected function error(string $msg): array
    {
        return [
            'success'    => false,
            'metadata'   => [],
            'notes'      => [],
            'confidence' => 'low',
            'warnings'   => [],
            'errors'     => [$msg],
        ];
    }
}
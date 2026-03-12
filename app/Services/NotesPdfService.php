<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 📄 SERVICE — Génération PDF CCPA
 * ✅ 100% PHP via DomPDF (barryvdh/laravel-dompdf)
 * ✅ Fonctionne Windows (Laragon/Herd) + Linux
 * ✅ Zéro Python, Zéro TCPDF
 *
 * Deux méthodes :
 *  - generate()       → fiche avec les notes remplies
 *  - generateVierge() → fiche VIERGE pour écriture manuelle
 *
 * Installation : composer require barryvdh/laravel-dompdf
 */
class NotesPdfService
{
    // ─────────────────────────────────────────────────────────────────
    // FICHE AVEC NOTES REMPLIES
    // ─────────────────────────────────────────────────────────────────

    public function generate(array $data): string
    {
        return $this->makePdf($data, false);
    }

    // ─────────────────────────────────────────────────────────────────
    // FICHE VIERGE — cases à remplir à la main
    // ─────────────────────────────────────────────────────────────────

    public function generateVierge(array $data): string
    {
        return $this->makePdf($data, true);
    }

    // ─────────────────────────────────────────────────────────────────
    // MOTEUR COMMUN
    // ─────────────────────────────────────────────────────────────────

    protected function makePdf(array $data, bool $vierge): string
    {
        $tmpDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $prefix     = $vierge ? 'vierge_' : 'notes_';
        $outputFile = $tmpDir . DIRECTORY_SEPARATOR . $prefix . uniqid() . '.pdf';

        // Logo base64 (DomPDF ne lit pas les chemins locaux Windows)
        $logoPath   = public_path('assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'LOGOCCPA.jpeg');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
            : '';

        $stats = $vierge ? null : $this->calcStats($data['notes'] ?? []);
        $html  = $vierge
            ? $this->buildHtmlVierge($data, $logoBase64)
            : $this->buildHtml($data, $stats, $logoBase64);

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->getDomPDF()->set_option('defaultFont', 'DejaVu Sans');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', false);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);

        file_put_contents($outputFile, $pdf->output());

        $type = $vierge ? 'VIERGE' : 'REMPLIE';
        Log::info("✅ PDF CCPA $type: $outputFile (" . filesize($outputFile) . ' bytes)');

        return $outputFile;
    }

    // ─────────────────────────────────────────────────────────────────
    // HTML — FICHE VIERGE (pour impression + écriture manuelle)
    // ─────────────────────────────────────────────────────────────────

    protected function buildHtmlVierge(array $data, string $logoBase64): string
    {
        $classe   = htmlspecialchars($data['classe'] ?? '', ENT_QUOTES);
        $matiere  = htmlspecialchars($data['matiere'] ?? '', ENT_QUOTES);
        $periode  = htmlspecialchars($data['periode'] ?? '', ENT_QUOTES);
        $typeNote = htmlspecialchars($data['type_note'] ?? '', ENT_QUOTES);
        $today    = date('d/m/Y');
        $nbEleves = count($data['notes'] ?? []);

        $logoImg = $logoBase64
            ? "<img src=\"{$logoBase64}\" style=\"height:26px;width:auto;vertical-align:middle;margin-right:5pt;\">"
            : '';

        $rowsHtml = '';
        foreach (($data['notes'] ?? []) as $i => $eleve) {
            $bg  = ($i % 2 === 0) ? '#ffffff' : '#f4f7fb';
            $nom = htmlspecialchars(strtoupper($eleve['nom'] ?? ''));
            $pre = htmlspecialchars($eleve['prenom'] ?? '');
            $mat = htmlspecialchars($eleve['matricule'] ?? '');
            $num = $eleve['num'] ?? $i + 1;
            $rowsHtml .= "
            <tr style=\"background:{$bg};\">
                <td style=\"text-align:center;color:#aaa;font-size:8pt;\">{$num}</td>
                <td style=\"text-align:center;font-family:Courier,monospace;font-size:8pt;\">{$mat}</td>
                <td style=\"font-weight:bold;\">{$nom}</td>
                <td>{$pre}</td>
                <td style=\"background:#fffef0;border-bottom:1.5pt dashed #003366 !important;\">&nbsp;</td>
                <td style=\"background:#fffef0;border-bottom:1pt dashed #aaa !important;\">&nbsp;</td>
            </tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:DejaVu Sans,Arial,sans-serif; font-size:9pt; color:#222; }
  .hdr { width:100%; border-collapse:collapse; background:#EEF3FA;
         border-top:2pt solid #003366; border-bottom:2.5pt solid #C8A951; margin-bottom:5pt; }
  .hdr td { padding:5pt 8pt; vertical-align:middle; }
  .h-etab { width:55%; font-size:10pt; font-weight:bold; color:#003366; }
  .h-titre{ width:30%; text-align:center; background:#003366;
            color:#fff; font-size:13pt; font-weight:bold; padding:5pt 4pt; }
  .h-date { width:15%; text-align:right; font-size:8pt; color:#003366; }
  .info { width:100%; border-collapse:collapse; margin-bottom:5pt; font-size:8.5pt; }
  .info td { padding:4pt 7pt; background:#EEF3FA; border:0.4pt solid #ccc; }
  .ik { font-weight:bold; color:#003366; }
  .iv { font-weight:bold; }
  .iw { background:#fffef0; border-bottom:1.2pt solid #003366 !important; }
  .notes { width:100%; border-collapse:collapse; font-size:9pt; margin-bottom:6pt; }
  .notes thead td { padding:6pt 4pt; font-weight:bold; text-align:center; border:0.8pt solid #C8A951; }
  .notes tbody td { padding:6pt 4pt; border:0.4pt solid #ddd; vertical-align:middle; }
  .sig { width:100%; border-collapse:collapse; }
  .sig td { text-align:center; width:33.3%; padding:3pt 6pt; font-size:8.5pt;
            font-weight:bold; color:#003366; border-top:0.5pt solid #C8A951; }
  .sline { display:block; margin-top:18pt; border-top:0.8pt solid #999; }
</style>
</head>
<body>

<table class="hdr"><tr>
  <td class="h-etab">{$logoImg}COLLÈGE CATHOLIQUE PÈRE AUPIAIS &mdash; Cotonou, Bénin</td>
  <td class="h-titre">FICHE DE NOTES</td>
  <td class="h-date">{$today}</td>
</tr></table>

<table class="info">
  <tr>
    <td class="ik" style="width:12%">Classe :</td>   <td class="iv" style="width:18%">{$classe}</td>
    <td class="ik" style="width:12%">Matière :</td>  <td class="iv" style="width:20%">{$matiere}</td>
    <td class="ik" style="width:10%">Période :</td>  <td class="iv" style="width:28%">{$periode}</td>
  </tr>
  <tr>
    <td class="ik">Type :</td>      <td class="iv">{$typeNote}</td>
    <td class="ik">Effectif :</td>  <td class="iv">{$nbEleves} élèves</td>
    <td class="ik">Enseignant :</td><td class="iw">&nbsp;</td>
  </tr>
</table>

<table class="notes">
  <thead><tr>
    <td style="width:4%;background:#003366;color:#fff;">#</td>
    <td style="width:14%;background:#003366;color:#fff;">Matricule</td>
    <td style="width:28%;background:#003366;color:#fff;">Nom</td>
    <td style="width:18%;background:#003366;color:#fff;">Prénom</td>
    <td style="width:12%;background:#FFF0C0;color:#003366;">Note /20</td>
    <td style="width:24%;background:#FFF0C0;color:#003366;">Commentaire</td>
  </tr></thead>
  <tbody>{$rowsHtml}</tbody>
</table>

<table class="sig"><tr>
  <td>Le Chef d'Établissement<span class="sline">&nbsp;</span></td>
  <td>L'Enseignant(e)<span class="sline">&nbsp;</span></td>
  <td>Date et Cachet<span class="sline">&nbsp;</span></td>
</tr></table>

</body>
</html>
HTML;
    }

    // ─────────────────────────────────────────────────────────────────
    // HTML — FICHE AVEC NOTES REMPLIES
    // ─────────────────────────────────────────────────────────────────

    protected function buildHtml(array $data, array $stats, string $logoBase64): string
    {
        $classe     = htmlspecialchars($data['classe'] ?? '', ENT_QUOTES);
        $annee      = htmlspecialchars($data['annee_scolaire'] ?? '', ENT_QUOTES);
        $matiere    = htmlspecialchars($data['matiere'] ?? '', ENT_QUOTES);
        $periode    = htmlspecialchars($data['periode'] ?? '', ENT_QUOTES);
        $typeNote   = htmlspecialchars($data['type_note'] ?? '', ENT_QUOTES);
        $enseignant = htmlspecialchars($data['enseignant'] ?? '', ENT_QUOTES);
        $today      = date('d/m/Y');
        $now        = date('d/m/Y à H:i');

        $logoHtml = $logoBase64
            ? "<img src=\"{$logoBase64}\" style=\"height:40px;width:auto;display:block;margin:0 auto 4px;\">"
            : "<div style=\"font-size:22pt;color:#003366;text-align:center;\">&#10013;</div>";

        $rowsHtml = '';
        foreach (($data['notes'] ?? []) as $i => $eleve) {
            [$mentionTxt, $mentionColor] = $this->getMention($eleve['note'] ?? null);
            $noteStr  = ($eleve['note'] !== null && $eleve['note'] !== '') ? htmlspecialchars((string)$eleve['note']) : '&mdash;';
            $bg       = ($i % 2 === 0) ? '#ffffff' : '#f4f7fb';
            $num      = $eleve['num'] ?? $i + 1;

            $rowsHtml .= sprintf(
                '<tr style="background:%s;">
                    <td style="text-align:center;color:#999;font-size:8pt;">%s</td>
                    <td style="text-align:center;font-size:8pt;font-family:Courier,monospace;">%s</td>
                    <td style="font-weight:bold;font-size:9pt;">%s</td>
                    <td style="font-size:9pt;">%s</td>
                    <td style="text-align:center;font-size:13pt;font-weight:bold;color:%s;">%s</td>
                    <td style="font-size:8pt;color:#555;">%s</td>
                    <td style="text-align:center;font-weight:bold;font-size:8pt;color:%s;">%s</td>
                </tr>',
                $bg, $num,
                htmlspecialchars($eleve['matricule'] ?? ''),
                htmlspecialchars(strtoupper($eleve['nom'] ?? '')),
                htmlspecialchars($eleve['prenom'] ?? ''),
                $mentionColor, $noteStr,
                htmlspecialchars($eleve['commentaire'] ?? ''),
                $mentionColor, $mentionTxt
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size:9pt; color:#222; }
  .hdr { width:100%; border-top:2pt solid #003366; border-bottom:3pt solid #C8A951; background:#EEF3FA; margin-bottom:5pt; }
  .hdr td { padding:7pt 6pt; vertical-align:middle; }
  .h-l { width:30%; color:#003366; font-size:8pt; line-height:1.7; text-align:left; }
  .h-m { width:40%; text-align:center; }
  .h-r { width:30%; color:#003366; font-size:8pt; line-height:1.7; text-align:right; }
  .etab { font-size:12pt; font-weight:bold; color:#003366; line-height:1.5; }
  .devise { font-size:7pt; font-style:italic; color:#666; }
  .titre { background:#003366; color:#fff; font-size:14pt; font-weight:bold; text-align:center; padding:7pt; margin-bottom:5pt; }
  .info { width:100%; border-collapse:collapse; margin-bottom:5pt; }
  .info td { padding:5pt 8pt; border-bottom:0.5pt solid #ccc; background:#EEF3FA; font-size:9pt; }
  .ik { font-weight:bold; color:#003366; width:20%; }
  .iv { font-weight:bold; width:30%; }
  .notes { width:100%; border-collapse:collapse; font-size:8.5pt; margin-bottom:5pt; }
  .notes thead td { background:#003366; color:#fff; font-weight:bold; text-align:center; padding:6pt 4pt; border:0.8pt solid #C8A951; }
  .notes tbody td { padding:5pt 4pt; border:0.5pt solid #ddd; vertical-align:middle; }
  .stats { width:100%; border-collapse:collapse; margin-bottom:10pt; }
  .stats td { text-align:center; padding:6pt; background:#FFF8E1; border:1.5pt solid #C8A951; }
  .sl { display:block; color:#666; font-size:7pt; }
  .sv { display:block; color:#003366; font-size:10pt; font-weight:bold; }
  .sig { width:100%; border-collapse:collapse; margin-bottom:6pt; }
  .sig td { text-align:center; padding:3pt; width:33%; }
  .st { font-weight:bold; color:#003366; font-size:9pt; display:block; margin-bottom:18pt; }
  .sl2 { border-top:0.8pt solid #C8A951; padding-top:3pt; }
  .sn { font-style:italic; color:#666; font-size:8pt; }
  .footer { border-top:1.5pt solid #003366; border-bottom:0.8pt solid #C8A951; padding:2pt; text-align:center; color:#999; font-size:6.5pt; }
</style>
</head>
<body>

<table class="hdr"><tr>
  <td class="h-l"><strong>République du Bénin</strong><br>Ministère des Enseignements<br>Secondaire, Technique et de la<br>Formation Professionnelle</td>
  <td class="h-m">{$logoHtml}<div class="etab">COLLÈGE CATHOLIQUE<br>PÈRE AUPIAIS</div><div style="color:#C8A951;font-size:9pt;">&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;</div><div class="devise">Excellence &middot; Intégrité &middot; Service</div></td>
  <td class="h-r">Cotonou &mdash; Bénin<br>Tél : +229 21 30 XX XX<br>Email : ccpa@ccpa.bj<br>Date : {$today}</td>
</tr></table>

<div class="titre">FICHE DE NOTES</div>

<table class="info">
  <tr><td class="ik">Classe :</td><td class="iv">{$classe}</td><td class="ik">Matière :</td><td class="iv">{$matiere}</td></tr>
  <tr><td class="ik">Année scolaire :</td><td class="iv">{$annee}</td><td class="ik">Période :</td><td class="iv">{$periode}</td></tr>
  <tr><td class="ik">Type d'évaluation :</td><td class="iv">{$typeNote}</td><td class="ik">Enseignant(e) :</td><td class="iv">{$enseignant}</td></tr>
</table>

<table class="notes">
  <thead><tr>
    <td style="width:4%">#</td>
    <td style="width:13%">Matricule</td>
    <td style="width:20%">Nom</td>
    <td style="width:17%">Prénom</td>
    <td style="width:10%">Note /20</td>
    <td style="width:20%">Commentaire</td>
    <td style="width:16%">Mention</td>
  </tr></thead>
  <tbody>{$rowsHtml}</tbody>
</table>

<table class="stats"><tr>
  <td><span class="sl">Effectif évalué</span><span class="sv">{$stats['effectif']}</span></td>
  <td><span class="sl">Moyenne de classe</span><span class="sv">{$stats['moyenne']}</span></td>
  <td><span class="sl">Note maximale</span><span class="sv">{$stats['max']}</span></td>
  <td><span class="sl">Note minimale</span><span class="sv">{$stats['min']}</span></td>
  <td><span class="sl">Taux de réussite</span><span class="sv">{$stats['taux']}</span></td>
</tr></table>

<table class="sig"><tr>
  <td><span class="st">Le Chef d'Établissement</span><div class="sl2">&nbsp;</div></td>
  <td><span class="st">L'Enseignant(e)</span><div class="sl2"><span class="sn">{$enseignant}</span></div></td>
  <td><span class="st">Cachet et Date</span><div class="sl2"><span class="sn">{$today}</span></div></td>
</tr></table>

<div class="footer">Collège Catholique Père Aupiais &mdash; Cotonou, Bénin &nbsp;|&nbsp; Document officiel &nbsp;|&nbsp; Généré le {$now}</div>

</body>
</html>
HTML;
    }

    // ─────────────────────────────────────────────────────────────────
    // STATISTIQUES
    // ─────────────────────────────────────────────────────────────────

    protected function calcStats(array $notes): array
    {
        $valid = array_filter($notes, fn($n) => isset($n['note']) && $n['note'] !== null && $n['note'] !== '');
        $vals  = array_map(fn($n) => (float)$n['note'], $valid);
        $eff   = count($valid);
        $tot   = count($notes);

        return [
            'effectif' => "$eff / $tot",
            'moyenne'  => $eff ? number_format(array_sum($vals) / $eff, 2) . ' / 20' : '— / 20',
            'max'      => $eff ? max($vals) . ' / 20' : '—',
            'min'      => $eff ? min($vals) . ' / 20' : '—',
            'taux'     => $eff ? round(count(array_filter($vals, fn($v) => $v >= 10)) / $eff * 100, 1) . ' %' : '0 %',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // MENTION
    // ─────────────────────────────────────────────────────────────────

    protected function getMention($note): array
    {
        if ($note === null || $note === '') return ['Absent',     '#888888'];
        $n = (float)$note;
        if ($n >= 18) return ['Excellent',    '#005500'];
        if ($n >= 16) return ['Très Bien',    '#1a7a1a'];
        if ($n >= 14) return ['Bien',         '#2e6e2e'];
        if ($n >= 12) return ['Assez Bien',   '#7a5000'];
        if ($n >= 10) return ['Passable',     '#b85c00'];
        return             ['Insuffisant',    '#cc0000'];
    }
}
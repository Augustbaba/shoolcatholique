<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Eleve;
use App\Models\ClasseAnnee;
use App\Models\Parents as ParentModel; // ou Parent selon votre modèle
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class ImportEleveController extends Controller
{
    /**
     * Formulaire d'upload (avec choix de la classe-année)
     */
    public function create()
    {
        $classesAnnees = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->get();
        return view('back.pages.eleves.import_create', compact('classesAnnees'));
    }

    /**
     * Prévisualisation des données du fichier
     */
    public function preview(Request $request)
    {
        $request->validate([
            'classe_annee_id' => 'required|exists:classe_annees,id',
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->findOrFail($request->classe_annee_id);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Supprimer les 3 premières lignes (titre, vide, en-têtes)
        array_shift($rows); // titre
        array_shift($rows); // vide
        $header = array_shift($rows); // en-têtes

        $data = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            // Ignorer les lignes vides
            if (empty(array_filter($row))) {
                continue;
            }

            // Ordre des colonnes : NOM, PRÉNOM, SEXE, DATE NAISSANCE, TÉLÉPHONE PARENT, NOM PARENT
            $nom = trim($row[0] ?? '');
            $prenom = trim($row[1] ?? '');
            $sexe = strtoupper(trim($row[2] ?? ''));
            $dateNaissanceFr = trim($row[3] ?? '');
            $telParent = trim($row[4] ?? '');
            $nomParentComplet = trim($row[5] ?? '');

            $rowErrors = [];

            if (empty($nom)) $rowErrors[] = "Nom manquant";
            if (empty($prenom)) $rowErrors[] = "Prénom manquant";
            if (!in_array($sexe, ['M', 'F'])) $rowErrors[] = "Sexe invalide (M ou F)";
            if (empty($dateNaissanceFr)) $rowErrors[] = "Date de naissance manquante";
            if (empty($telParent)) $rowErrors[] = "Téléphone parent manquant";
            if (empty($nomParentComplet)) $rowErrors[] = "Nom parent manquant";

            if (!empty($rowErrors)) {
                $errors[$index+5] = $rowErrors; // +5 car on a enlevé 3 lignes + index 0
                continue;
            }

            // Conversion de la date française (j/m/Y) en Y-m-d
            try {
                $dateNaissance = Carbon::createFromFormat('d/m/Y', $dateNaissanceFr)->format('Y-m-d');
            } catch (\Exception $e) {
                $errors[$index+5][] = "Format de date invalide (utilisez JJ/MM/AAAA)";
                continue;
            }

            // Gestion du parent : chercher par téléphone, sinon créer
            $parent = ParentModel::where('telephone', $telParent)->first();
            if (!$parent) {
                // Séparer le nom complet en nom et prénom (premier mot = nom, reste = prénom)
                $nomParentParts = explode(' ', $nomParentComplet, 2);
                $nomParent = $nomParentParts[0];
                $prenomParent = isset($nomParentParts[1]) ? $nomParentParts[1] : '';

                // Créer un utilisateur parent
                $user = \App\Models\User::create([
                    'name' => $nomParentComplet,
                    'email' => $this->generateEmail($prenomParent ?: $nomParent, $nomParent),
                    'password' => \Hash::make('password123'),
                    'role' => 'parent',
                    'actif' => true,
                ]);

                $parent = ParentModel::create([
                    'user_id' => $user->id,
                    'nom' => $nomParent,
                    'prenom' => $prenomParent,
                    'telephone' => $telParent,
                    'whatsapp' => null,
                    'profession' => null,
                    'adresse' => null,
                ]);
            }

            // Génération d'un matricule unique (ex: TERM-2025-001)
            // On peut utiliser le préfixe de la classe + année + numéro
            $prefixe = strtoupper(substr($classeAnnee->classe->niveau->nom, 0, 3)) . '-' . $classeAnnee->anneeScolaire->libelle;
            // Compter les élèves existants dans cette classe-année pour définir le prochain numéro
            $count = Eleve::where('classe_annee_id', $classeAnnee->id)->count();
            $numero = str_pad($count + count($data) + 1, 3, '0', STR_PAD_LEFT);
            $matricule = $prefixe . '-' . $numero;

            // Éviter les doublons de matricule (au cas où plusieurs imports simultanés)
            while (Eleve::where('matricule', $matricule)->exists()) {
                $numero++;
                $matricule = $prefixe . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
            }

            // Ligne valide
            $data[] = [
                'index' => $index,
                'matricule' => $matricule,
                'nom' => $nom,
                'prenom' => $prenom,
                'sexe' => $sexe,
                'date_naissance' => $dateNaissance,
                'telephone_parent' => $telParent,
                'nom_parent' => $nomParentComplet,
                // Données pour l'insertion
                '_parent_id' => $parent->id,
                '_classe_annee_id' => $classeAnnee->id,
                '_date_naissance_obj' => $dateNaissance,
            ];
        }

        // Stocker en session
        Session::put('import_eleves_data', $data);
        Session::put('import_eleves_errors', $errors);
        Session::put('import_eleves_classe_annee', $classeAnnee->id);

        return view('back.pages.eleves.import_preview', compact('data', 'errors', 'classeAnnee'));
    }

    /**
     * Importe les lignes sélectionnées
     */
    public function store(Request $request)
    {
        $selectedIndices = $request->input('selected', []);
        $data = Session::get('import_eleves_data', []);

        if (empty($selectedIndices)) {
            return redirect()->back()->with('error', 'Aucune ligne sélectionnée.');
        }

        $imported = 0;
        $failed = [];

        foreach ($data as $item) {
            if (in_array($item['index'], $selectedIndices)) {
                try {
                    Eleve::create([
                        'matricule'        => $item['matricule'],
                        'nom'              => $item['nom'],
                        'prenom'           => $item['prenom'],
                        'sexe'             => $item['sexe'],
                        'date_naissance'   => $item['_date_naissance_obj'],
                        'photo'            => null,
                        'classe_annee_id'  => $item['_classe_annee_id'],
                        'parent_id'        => $item['_parent_id'],
                        'date_inscription' => now()->toDateString(),
                        'statut'           => 'actif',
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $failed[] = $item['matricule'] . ' : ' . $e->getMessage();
                }
            }
        }

        // Nettoyer la session
        Session::forget(['import_eleves_data', 'import_eleves_errors', 'import_eleves_classe_annee']);

        $message = "$imported élèves importés avec succès.";
        if (!empty($failed)) {
            $message .= " Échecs : " . implode('; ', $failed);
        }

        return redirect()->route('admin.eleves.import.create')->with('success', $message);
    }

    /**
     * Génère un email unique pour le parent
     */
    private function generateEmail($prenom, $nom)
    {
        $base = strtolower(trim($prenom) . '.' . trim($nom));
        $base = preg_replace('/[^a-z0-9.]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $base));
        $email = $base . '@example.com';
        $i = 1;
        while (\App\Models\User::where('email', $email)->exists()) {
            $email = $base . $i . '@example.com';
            $i++;
        }
        return $email;
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Eleve;
use App\Models\ClasseAnnee;
use App\Models\Parents as ParentModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class ImportEleveController extends Controller
{
    /**
     * Formulaire d'upload (avec choix de la classe-année et du nom de l'onglet)
     */
    public function create()
    {
        $classesAnnees = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->get();
        return view('back.pages.eleves.import_create', compact('classesAnnees'));
    }

    /**
     * Prévisualisation des données du fichier pour l'onglet spécifié
     */
    public function preview(Request $request)
    {
        $request->validate([
            'classe_annee_id' => 'required|exists:classe_annees,id',
            'file'            => 'required|mimes:xlsx,xls,csv|max:5120',
            'sheet_name'      => 'required|string|max:100', // nom exact de l'onglet
        ]);

        $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->findOrFail($request->classe_annee_id);

        // Charger le fichier
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());

        // Récupérer la feuille par son nom
        $worksheet = $spreadsheet->getSheetByName($request->sheet_name);
        if (!$worksheet) {
            return redirect()->back()->withErrors(['sheet_name' => "L'onglet '{$request->sheet_name}' n'existe pas dans le fichier."]);
        }

        // Lire les en-têtes (ligne 3)
        $headers = $worksheet->rangeToArray('A3:' . $worksheet->getHighestColumn() . '3', NULL, TRUE, FALSE)[0];
        $headers = array_map('trim', $headers);

        // Mapping des colonnes recherchées (clé => libellé attendu)
        $columnMapping = [
            'matricule'      => 'MATRICULE',
            'nom'            => 'NOM',
            'prenom'         => ['PRÉNOM', 'PRÉNOMS'], // plusieurs variantes possibles
            'sexe'           => 'SEXE',
            'date_naissance' => 'DATE DE NAISSANCE',
            'tel_parent'     => ['TÉLÉPHONE PARENT', 'TÉL. PARENT'],
            'nom_parent'     => 'NOM PARENT',
            'prenom_parent'  => 'PRÉNOM PARENT',
            'tel_eleve'      => 'N° GSM', // optionnel
        ];

        // Trouver les index des colonnes
        $colIndexes = [];
        foreach ($columnMapping as $key => $expected) {
            $expectedArray = is_array($expected) ? $expected : [$expected];
            $found = false;
            foreach ($expectedArray as $label) {
                $index = array_search($label, $headers);
                if ($index !== false) {
                    $colIndexes[$key] = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Si colonne non trouvée, on met null (elle sera ignorée)
                $colIndexes[$key] = null;
            }
        }

        // Récupérer toutes les lignes à partir de la ligne 4
        $rows = $worksheet->toArray();
        // Supprimer les 3 premières lignes (titre, vide, en-têtes)
        array_shift($rows); // titre
        array_shift($rows); // vide
        array_shift($rows); // en-têtes

        $data = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            // Ignorer les lignes vides
            if (empty(array_filter($row))) {
                continue;
            }

            // Extraire les valeurs selon le mapping
            $matricule   = isset($colIndexes['matricule']) ? trim($row[$colIndexes['matricule']] ?? '') : '';
            $nom         = isset($colIndexes['nom']) ? trim($row[$colIndexes['nom']] ?? '') : '';
            $prenom      = isset($colIndexes['prenom']) ? trim($row[$colIndexes['prenom']] ?? '') : '';
            $sexe        = isset($colIndexes['sexe']) ? strtoupper(trim($row[$colIndexes['sexe']] ?? '')) : '';
            $dateNaissanceFr = isset($colIndexes['date_naissance']) ? trim($row[$colIndexes['date_naissance']] ?? '') : '';
            $telParent    = isset($colIndexes['tel_parent']) ? trim($row[$colIndexes['tel_parent']] ?? '') : '';
            $nomParent    = isset($colIndexes['nom_parent']) ? trim($row[$colIndexes['nom_parent']] ?? '') : '';
            $prenomParent = isset($colIndexes['prenom_parent']) ? trim($row[$colIndexes['prenom_parent']] ?? '') : '';
            $telEleve     = isset($colIndexes['tel_eleve']) ? trim($row[$colIndexes['tel_eleve']] ?? '') : '';

            $rowErrors = [];

            // Validation des champs obligatoires
            if (empty($nom)) $rowErrors[] = "Nom manquant";
            if (empty($prenom)) $rowErrors[] = "Prénom manquant";
            if (empty($telParent)) $rowErrors[] = "Téléphone parent manquant";
            if (empty($nomParent) && empty($prenomParent)) {
                $rowErrors[] = "Nom du parent manquant (ni nom ni prénom parent fourni)";
            }

            // Sexe : optionnel, mais si présent doit être M ou F
            if (!empty($sexe) && !in_array($sexe, ['M', 'F'])) {
                $rowErrors[] = "Sexe invalide (doit être M ou F)";
            }

            // Date de naissance : optionnelle, mais si présente doit être valide
            if (!empty($dateNaissanceFr)) {
                try {
                    $dateNaissance = Carbon::createFromFormat('d/m/Y', $dateNaissanceFr)->format('Y-m-d');
                } catch (\Exception $e) {
                    $rowErrors[] = "Format de date invalide (utilisez JJ/MM/AAAA)";
                    $dateNaissance = null;
                }
            } else {
                $dateNaissance = null;
            }

            if (!empty($rowErrors)) {
                $errors[$index+5] = $rowErrors; // +5 car on a enlevé 3 lignes + index 0
                continue;
            }

            // --- Gestion du parent ---
            // Chercher par téléphone
            $parent = ParentModel::where('telephone', $telParent)->first();
            if (!$parent) {
                // Créer un utilisateur parent
                $nomParentFinal = $nomParent;
                $prenomParentFinal = $prenomParent;

                // Si le prénom parent est vide mais que le nom parent contient plusieurs mots, on sépare
                if (empty($prenomParentFinal) && !empty($nomParentFinal)) {
                    $parts = explode(' ', $nomParentFinal, 2);
                    $nomParentFinal = $parts[0];
                    $prenomParentFinal = $parts[1] ?? '';
                }

                // Si toujours vide, on met une valeur par défaut
                if (empty($nomParentFinal)) {
                    $nomParentFinal = 'Inconnu';
                }

                $user = \App\Models\User::create([
                    'name'     => trim($nomParentFinal . ' ' . $prenomParentFinal),
                    'email'    => $this->generateEmail($prenomParentFinal ?: $nomParentFinal, $nomParentFinal),
                    'password' => Hash::make('password123'),
                    'role'     => 'parent',
                    'actif'    => true,
                ]);

                $parent = ParentModel::create([
                    'user_id'    => $user->id,
                    'nom'        => $nomParentFinal,
                    'prenom'     => $prenomParentFinal,
                    'telephone'  => $telParent,
                    'whatsapp'   => null,
                    'profession' => null,
                    'adresse'    => null,
                ]);
            }

            // --- Gestion du matricule ---
            if (empty($matricule)) {
                // Générer un matricule si absent
                $prefixe = strtoupper(substr($classeAnnee->classe->niveau->nom, 0, 3)) . '-' . $classeAnnee->anneeScolaire->libelle;
                $count = Eleve::where('classe_annee_id', $classeAnnee->id)->count();
                $numero = str_pad($count + count($data) + 1, 3, '0', STR_PAD_LEFT);
                $matricule = $prefixe . '-' . $numero;

                // Éviter les doublons
                while (Eleve::where('matricule', $matricule)->exists()) {
                    $numero++;
                    $matricule = $prefixe . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
                }
            } else {
                // Vérifier si le matricule existe déjà
                if (Eleve::where('matricule', $matricule)->exists()) {
                    // On peut soit générer un nouveau, soit signaler une erreur
                    // Ici on génère un nouveau pour éviter les conflits
                    $originalMatricule = $matricule;
                    $matricule = $this->generateUniqueMatricule($matricule);
                    $rowErrors[] = "Matricule '$originalMatricule' déjà existant, remplacé par '$matricule'";
                    // On continue mais on note l'erreur
                }
            }

            // Ligne valide
            $data[] = [
                'index'           => $index,
                'matricule'       => $matricule,
                'nom'             => $nom,
                'prenom'          => $prenom,
                'sexe'            => $sexe ?: null,
                'date_naissance'  => $dateNaissance,
                'telephone_parent'=> $telParent,
                'nom_parent'      => $nomParent . ' ' . $prenomParent,
                // Données pour l'insertion
                '_parent_id'      => $parent->id,
                '_classe_annee_id'=> $classeAnnee->id,
                '_date_naissance_obj' => $dateNaissance,
            ];
        }

        // Stocker en session pour l'import final
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

    /**
     * Génère un matricule unique à partir d'un matricule existant
     */
    private function generateUniqueMatricule($baseMatricule)
    {
        $original = $baseMatricule;
        $i = 1;
        while (Eleve::where('matricule', $baseMatricule)->exists()) {
            $baseMatricule = $original . '-' . $i;
            $i++;
        }
        return $baseMatricule;
    }
}
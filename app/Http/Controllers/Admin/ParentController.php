<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use App\Models\Parents as ParentModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ParentController extends Controller
{
    /**
     * Affiche le formulaire d'import
     */

    public function index()
    {
        $parents = ParentModel::with('user')->paginate(15); // 15 par page
        return view('back.pages.parents.index', compact('parents'));
    }
    public function showForm()
    {
        return view('back.pages.parents.import');
    }

    /**
     * Traite l'upload et l'import avec PhpSpreadsheet
     */
    public function import(Request $request)
    {
        set_time_limit(0); // Pas de limite de temps
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', // max 5 Mo
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Supprimer la première ligne si elle contient les en-têtes
            // On suppose que la ligne 0 (index 0) est l'en-tête
            $header = array_shift($rows);

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                // Associer les colonnes selon votre fichier
                // Vérifiez l'ordre des colonnes dans votre Excel
                // Exemple : NOM, PRÉNOM, TÉLÉPHONE, WHATSAPP, PROFESSION, ADRESSE
                if (count($row) < 3) {
                    continue; // ligne incomplète
                }

                $nom = trim($row[0] ?? '');
                $prenom = trim($row[1] ?? '');
                $telephone = trim($row[2] ?? '');
                $whatsapp = trim($row[3] ?? '');
                $profession = trim($row[4] ?? '');
                $adresse = trim($row[5] ?? '');

                // Validation minimale
                if (empty($nom) || empty($prenom) || empty($telephone)) {
                    $skipped++;
                    continue;
                }

                // Vérifier si le parent existe déjà (téléphone unique)
                $existingParent = ParentModel::where('telephone', $telephone)->first();
                if ($existingParent) {
                    $skipped++;
                    continue;
                }

                // Générer un email unique
                $email = $this->generateEmail($prenom, $nom);

                // Créer l'utilisateur
                $user = User::create([
                    'name'     => $prenom . ' ' . $nom,
                    'email'    => $email,
                    'password' => Hash::make('password123'), // mot de passe par défaut (à changer)
                    'role'     => 'parent',
                    'actif'    => true,
                ]);

                // Créer le parent
                ParentModel::create([
                    'user_id'    => $user->id,
                    'nom'        => $nom,
                    'prenom'     => $prenom,
                    'telephone'  => $telephone,
                    'whatsapp'   => $whatsapp,
                    'profession' => $profession,
                    'adresse'    => $adresse,
                ]);

                $imported++;
            }

            return redirect()->back()->with('success', "Import terminé : $imported parents importés, $skipped ignorés (déjà existants ou incomplets).");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Génère une adresse email unique
     */
    private function generateEmail($prenom, $nom)
    {
        $base = strtolower(trim($prenom) . '.' . trim($nom));
        // Supprimer accents et caractères non autorisés
        $base = preg_replace('/[^a-z0-9.]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $base));
        $email = $base . '@example.com';
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base . $i . '@example.com';
            $i++;
        }
        return $email;
    }
}
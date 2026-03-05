<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — CCPA</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --vert: #1b4d2e; --vert-clair: #2d7a4a;
            --or: #b8953a; --or-clair: #d4aa50;
            --blanc: #ffffff; --creme: #faf8f4; --gris: #f2f0eb;
            --texte: #1a1a1a; --texte-doux: #6b6b6b; --bord: #e0dcd4;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--creme);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .gauche {
            background: var(--vert);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 5vw;
            position: relative;
            overflow: hidden;
        }
        .gauche::before {
            content: '';
            position: absolute;
            right: -60px;
            top: 50%;
            transform: translateY(-50%);
            width: 120px;
            height: 120%;
            background: var(--vert);
            clip-path: polygon(0 0, 0 100%, 100% 50%);
        }
        .gauche-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 4rem;
        }
        .logo-cercle {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--or);
        }
        .logo-cercle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .logo-txt strong {
            display: block;
            color: var(--blanc);
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 700;
        }
        .logo-txt span {
            color: var(--or-clair);
            font-size: 10px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .gauche-titre {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.2rem, 3.5vw, 3rem);
            font-weight: 700;
            color: var(--blanc);
            line-height: 1.1;
            margin-bottom: 1rem;
        }
        .gauche-titre em {
            color: var(--or);
            font-style: normal;
        }
        .gauche-desc {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
            line-height: 1.7;
            max-width: 380px;
            margin-bottom: 3rem;
        }

        .roles {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .role-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.25rem;
            border: 1px solid rgba(255,255,255,0.08);
            cursor: pointer;
            transition: all 0.2s;
        }
        .role-item.actif,
        .role-item:hover {
            background: rgba(255,255,255,0.07);
            border-color: var(--or);
        }
        .role-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.2rem;
            color: var(--or);
            width: 24px;
        }
        .role-info strong {
            display: block;
            color: var(--blanc);
            font-size: 14px;
            font-weight: 500;
        }
        .role-info span {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
        }

        .droite {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 5vw;
        }
        .connexion-box {
            width: 100%;
            max-width: 420px;
        }
        .conn-titre {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--texte);
            margin-bottom: 0.4rem;
        }
        .conn-sous {
            font-size: 13px;
            color: var(--texte-doux);
            margin-bottom: 2.5rem;
        }

        .onglets {
            display: flex;
            border-bottom: 1px solid var(--bord);
            margin-bottom: 2rem;
        }
        .onglet {
            padding: 0.6rem 1.25rem;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--texte-doux);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all 0.2s;
        }
        .onglet.actif {
            color: var(--vert);
            border-bottom-color: var(--vert);
        }

        .champ {
            margin-bottom: 1.1rem;
        }
        .champ label {
            display: block;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--texte-doux);
            margin-bottom: 0.5rem;
        }
        .champ input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--bord);
            background: var(--blanc);
            border-radius: 2px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--texte);
            outline: none;
            transition: border-color 0.2s;
        }
        .champ input:focus {
            border-color: var(--vert);
        }
        .champ input.is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            font-size: 11px;
            margin-top: 5px;
        }
        .oublie {
            font-size: 12px;
            color: var(--texte-doux);
            text-decoration: none;
            display: block;
            text-align: right;
            margin-bottom: 1.5rem;
        }
        .oublie:hover {
            color: var(--vert);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: var(--vert);
            color: var(--blanc);
            border: none;
            border-radius: 2px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: var(--vert-clair);
        }
        .conn-aide {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--bord);
            font-size: 12px;
            color: var(--texte-doux);
        }
        .conn-aide a {
            color: var(--vert);
            text-decoration: none;
            font-weight: 500;
        }
        .retour {
            display: inline-block;
            font-size: 12px;
            color: var(--texte-doux);
            text-decoration: none;
            margin-top: 2rem;
            letter-spacing: 0.5px;
        }
        .retour:hover {
            color: var(--texte);
        }
        /* Message d'erreur global */
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 2px;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="gauche">
        <div class="gauche-logo">
            <div class="logo-cercle">
                <img src="{{ asset('assets/images/LOGOCCPA.jpeg') }}" alt="CCPA">
            </div>
            <div class="logo-txt">
                <strong>Père Aupiais</strong>
                <span>Cotonou · Bénin</span>
            </div>
        </div>
        <h1 class="gauche-titre">Votre <em>espace</em><br>personnalisé</h1>
        <p class="gauche-desc">Connectez-vous pour accéder aux notes, bulletins, absences et à toutes les communications de l'établissement.</p>
        <div class="roles">
            <div class="role-item actif" data-role="parent">
                <span class="role-num">01</span>
                <div class="role-info">
                    <strong>Parent / Tuteur</strong>
                    <span>Suivi de la scolarité de vos enfants</span>
                </div>
            </div>
            <div class="role-item" data-role="enseignant">
                <span class="role-num">02</span>
                <div class="role-info">
                    <strong>Enseignant</strong>
                    <span>Saisie des notes et gestion des classes</span>
                </div>
            </div>
            <div class="role-item" data-role="admin">
                <span class="role-num">03</span>
                <div class="role-info">
                    <strong>Administrateur</strong>
                    <span>Direction et gestion de l'établissement</span>
                </div>
            </div>
        </div>
    </div>

    <div class="droite">
        <div class="connexion-box">
            <div class="conn-titre">Connexion</div>
            <div class="conn-sous">Saisissez vos identifiants fournis par l'établissement.</div>

            <div class="onglets">
                <div class="onglet actif" data-role="parent">Parent</div>
                <div class="onglet" data-role="enseignant">Enseignant</div>
                <div class="onglet" data-role="admin">Administrateur</div>
            </div>

            {{-- Affichage des erreurs de session --}}
            @if(session('error'))
                <div class="alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="champ">
                    <label for="login" id="login-label">Email ou téléphone</label>
                    <input type="text" name="login" id="login"
                           class="@error('login') is-invalid @enderror"
                           placeholder="ex. parent@ecole.com ou 61234567"
                           value="{{ old('login') }}"
                           required autofocus>
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="champ">
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password"
                           class="@error('password') is-invalid @enderror"
                           placeholder="••••••••"
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="oublie">Mot de passe oublié ?</a>
                @endif

                <button type="submit" class="btn-login">Se connecter</button>
            </form>

            <div class="conn-aide">
                Première connexion ou identifiants perdus ? <a href="{{ url('/contact') }}">Contactez le secrétariat</a> de l'établissement.
            </div>

            <a href="{{ url('/index') }}" class="retour">← Retour au site du collège</a>
        </div>
    </div>

    <script>
        (function() {
            const onglets = document.querySelectorAll('.onglet');
            const roles = document.querySelectorAll('.role-item');
            const loginInput = document.getElementById('login');
            const loginLabel = document.getElementById('login-label');

            // Configuration des libellés et placeholders selon le rôle
            const roleConfig = {
                parent: {
                    label: 'Email ou téléphone (parent)',
                    placeholder: 'ex. parent@ecole.com ou 61234567'
                },
                enseignant: {
                    label: 'Email ou téléphone (enseignant)',
                    placeholder: 'ex. enseignant@ecole.com ou 61234567'
                },
                admin: {
                    label: 'Email ou téléphone (admin)',
                    placeholder: 'ex. direction@ecole.com ou 61234567'
                }
            };

            function setActiveRole(role) {
                // Mettre à jour les onglets
                onglets.forEach(o => {
                    o.classList.toggle('actif', o.dataset.role === role);
                });
                // Mettre à jour les items de la colonne de gauche
                roles.forEach(r => {
                    r.classList.toggle('actif', r.dataset.role === role);
                });
                // Mettre à jour le label et le placeholder du champ login
                if (roleConfig[role]) {
                    loginLabel.textContent = roleConfig[role].label;
                    loginInput.placeholder = roleConfig[role].placeholder;
                }
            }

            // Écouteurs sur les onglets
            onglets.forEach(onglet => {
                onglet.addEventListener('click', () => {
                    setActiveRole(onglet.dataset.role);
                });
            });

            // Écouteurs sur les items de la colonne de gauche
            roles.forEach(roleItem => {
                roleItem.addEventListener('click', () => {
                    setActiveRole(roleItem.dataset.role);
                });
            });

            // Rôle actif par défaut : parent (classe "actif" déjà présente)
            const defaultRole = document.querySelector('.onglet.actif')?.dataset.role || 'parent';
            setActiveRole(defaultRole);
        })();
    </script>
</body>
</html>
{{-- ========================================================================
     Sidebar principale – Complexe Catholique Père Aupiais
     ======================================================================== --}}
<aside class="sidebar" aria-label="Navigation principale">

  {{-- Bouton de fermeture (pour mobile) --}}
  <button type="button" class="sidebar-close-btn d-xl-none" aria-label="Fermer le menu">
    <iconify-icon icon="radix-icons:cross-2" aria-hidden="true"></iconify-icon>
  </button>

  {{-- ======================================================================
       En‑tête avec logo et nom de l'établissement
       ====================================================================== --}}
  <div class="sidebar-header">
    <div class="d-flex align-items-center gap-8 mt-10 px-2 py-8 radius-8 brand-container"
         style="background:rgba(0,51,102,.06); border:1px solid rgba(0,51,102,.12);">
      <img src="{{ asset('assets/images/LOGOCCPA.jpeg') }}"
           alt="Logo CCPA"
           class="rounded flex-shrink-0"
           style="width:32px; height:32px; object-fit:contain;"
           loading="lazy">
      <div class="overflow-hidden">
        <span class="d-block text-xs fw-bold text-primary-light text-truncate"
              style="font-size:11px; letter-spacing:.3px;">
          COMPLEXE CATHOLIQUE
        </span>
        <span class="d-block text-secondary-light text-truncate"
              style="font-size:10px;">
          PÈRE AUPIAIS — Cotonou
        </span>
      </div>

      {{-- Bouton de réduction (desktop) – caché sur mobile --}}
      <button type="button"
              class="text-xxl d-none d-xl-flex line-height-1 sidebar-toggle text-neutral-500"
              aria-label="Réduire la barre latérale"
              title="Réduire/agrandir">
        <i class="ri-contract-left-line" aria-hidden="true"></i>
      </button>
    </div>
  </div>

  {{-- ======================================================================
       Bloc utilisateur connecté
       ====================================================================== --}}
  <div class="mx-16 py-12 user-profile">
    <div class="dropdown profile-dropdown">
      <button type="button"
              class="profile-dropdown__button d-flex align-items-center justify-content-between
                     p-10 w-100 overflow-hidden bg-neutral-50 radius-12"
              data-bs-toggle="dropdown"
              data-bs-display="static"
              aria-expanded="false"
              aria-haspopup="true"
              id="userDropdown"
              aria-label="Menu utilisateur">

        <span class="d-flex align-items-start gap-10">

          @php
            $authUser  = Auth::user();
            $photoPath = $authUser->photo ?? null;
            // Génération des initiales (ex: "Jean Dupont" → "JD")
            $nameParts = explode(' ', trim($authUser->name ?? ''));
            $initiales = '';
            foreach ($nameParts as $part) {
                if (!empty($part)) $initiales .= strtoupper(mb_substr($part, 0, 1));
            }
            $initiales = $initiales ?: 'U';
          @endphp

          @if($photoPath && Storage::disk('public')->exists($photoPath))
            <img src="{{ Storage::url($photoPath) }}"
                 alt=""
                 class="w-40-px h-40-px rounded-circle object-fit-cover flex-shrink-0"
                 loading="lazy">
          @else
            <span class="w-40-px h-40-px rounded-circle flex-shrink-0 d-flex align-items-center
                         justify-content-center fw-bold text-white user-avatar-fallback"
                  style="background: linear-gradient(135deg, #003366, #0066cc); font-size:14px;"
                  aria-label="Avatar par défaut">
              {{ $initiales }}
            </span>
          @endif

          <span class="profile-dropdown__contents">
            <span class="h6 mb-0 text-md d-block text-primary-light fw-semibold">
              {{ $authUser->name ?? 'Utilisateur' }}
            </span>
            <span class="text-secondary-light text-sm mb-0 d-block">
              @switch($authUser->role ?? '')
                @case('admin')       Administrateur @break
                @case('enseignant')  Enseignant     @break
                @case('parent')      Parent         @break
                @default             Utilisateur
              @endswitch
            </span>
          </span>
        </span>

        <span class="profile-dropdown__icon pe-8 text-xl d-flex line-height-1"
              aria-hidden="true">
          <i class="ri-arrow-right-s-line"></i>
        </span>
      </button>

      {{-- Ici peut venir le menu déroulant du profil (items) – non inclus dans votre code --}}
    </div>
  </div>

  {{-- ======================================================================
       Menu de navigation principal
       ====================================================================== --}}
  <div class="sidebar-menu-area">
    <ul class="sidebar-menu" id="sidebar-menu" role="menubar" aria-label="Sections">

      {{-- Dashboard --}}
      <li role="none">
        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->routeIs('admin.dashboard') ? 'active-page' : '' }}"
           role="menuitem"
           @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
          <i class="ri-home-4-line" aria-hidden="true"></i>
          <span>Dashboard</span>
        </a>
      </li>

      {{-- Parents (dropdown) --}}
      <li class="dropdown {{ request()->routeIs('admin.parents.*') ? 'open' : '' }}"
          role="none">
        <a href="javascript:void(0)"
           role="menuitem"
           aria-haspopup="true"
           aria-expanded="{{ request()->routeIs('admin.parents.*') ? 'true' : 'false' }}">
          <i class="ri-account-circle-line" aria-hidden="true"></i>
          <span>Parents</span>
          <i class="ri-arrow-down-s-line ms-auto" aria-hidden="true"></i>
        </a>
        <ul class="sidebar-submenu" role="menu" aria-label="Sous‑menu Parents">
          <li role="none">
            <a href="{{ route('admin.parents.index') }}"
               class="{{ request()->routeIs('admin.parents.index') ? 'active-page' : '' }}"
               role="menuitem"
               @if(request()->routeIs('admin.parents.index')) aria-current="page" @endif>
              <i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i>
              Liste des parents
            </a>
          </li>
          <li role="none">
            <a href="{{ route('admin.parents.import.phpoffice') }}"
               class="{{ request()->routeIs('admin.parents.import*') ? 'active-page' : '' }}"
               role="menuitem"
               @if(request()->routeIs('admin.parents.import*')) aria-current="page" @endif>
              <i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i>
              Importer parents
            </a>
          </li>
        </ul>
      </li>

      {{-- Élèves --}}
      <li class="dropdown {{ request()->routeIs('admin.eleves.*') ? 'open' : '' }}" role="none">
        <a href="javascript:void(0)"
           role="menuitem"
           aria-haspopup="true"
           aria-expanded="{{ request()->routeIs('admin.eleves.*') ? 'true' : 'false' }}">
          <i class="ri-group-line" aria-hidden="true"></i>
          <span>Élèves</span>
          <i class="ri-arrow-down-s-line ms-auto" aria-hidden="true"></i>
        </a>
        <ul class="sidebar-submenu" role="menu" aria-label="Sous‑menu Élèves">
          <li role="none">
            <a href="{{ route('admin.eleves.index') }}"
               class="{{ request()->routeIs('admin.eleves.index') ? 'active-page' : '' }}"
               role="menuitem"
               @if(request()->routeIs('admin.eleves.index')) aria-current="page" @endif>
              <i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i>
              Liste des élèves
            </a>
          </li>
          <li role="none">
            <a href="{{ route('admin.eleves.import.create') }}"
               class="{{ request()->routeIs('admin.eleves.import*') ? 'active-page' : '' }}"
               role="menuitem"
               @if(request()->routeIs('admin.eleves.import*')) aria-current="page" @endif>
              <i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i>
              Importer élèves
            </a>
          </li>
        </ul>
      </li>

      {{-- Pédagogie --}}
      <li class="dropdown {{ request()->routeIs('admin.niveaux.*','admin.classes.*','admin.classe-annees.*','admin.matieres.*','admin.periodes.*','admin.type-notes.*') ? 'open' : '' }}"
          role="none">
        <a href="javascript:void(0)"
           role="menuitem"
           aria-haspopup="true"
           aria-expanded="{{ request()->routeIs('admin.niveaux.*','admin.classes.*','admin.classe-annees.*','admin.matieres.*','admin.periodes.*','admin.type-notes.*') ? 'true' : 'false' }}">
          <i class="ri-book-open-line" aria-hidden="true"></i>
          <span>Pédagogie</span>
          <i class="ri-arrow-down-s-line ms-auto" aria-hidden="true"></i>
        </a>
        <ul class="sidebar-submenu" role="menu" aria-label="Sous‑menu Pédagogie">
          <li role="none"><a href="{{ route('admin.niveaux.index') }}" class="{{ request()->routeIs('admin.niveaux.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.niveaux.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Niveaux</a></li>
          <li role="none"><a href="{{ route('admin.classes.index') }}" class="{{ request()->routeIs('admin.classes.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.classes.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Classes</a></li>
          <li role="none"><a href="{{ route('admin.classe-annees.index') }}" class="{{ request()->routeIs('admin.classe-annees.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.classe-annees.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Classes par année</a></li>
          <li role="none"><a href="{{ route('admin.matieres.index') }}" class="{{ request()->routeIs('admin.matieres.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.matieres.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Matières</a></li>
          <li role="none"><a href="{{ route('admin.periodes.index') }}" class="{{ request()->routeIs('admin.periodes.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.periodes.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Périodes</a></li>
          <li role="none"><a href="{{ route('admin.type-notes.index') }}" class="{{ request()->routeIs('admin.type-notes.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.type-notes.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Types de notes</a></li>
          <li role="none">
            <a href="{{ route('admin.classe-matieres.index', ['classeAnnee' => '__id__']) }}"
               class="disabled-link"
               aria-disabled="true"
               role="menuitem"
               tabindex="-1">
              <i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Coefficients
              <span class="badge bg-secondary ms-auto" style="font-size:9px;">Par classe</span>
            </a>
          </li>
        </ul>
      </li>

      {{-- Notes --}}
      <li class="dropdown {{ request()->routeIs('admin.notes.*','admin.cahier-notes.*') ? 'open' : '' }}"
          role="none">
        <a href="javascript:void(0)"
           role="menuitem"
           aria-haspopup="true"
           aria-expanded="{{ request()->routeIs('admin.notes.*','admin.cahier-notes.*') ? 'true' : 'false' }}">
          <i class="ri-file-list-3-line" aria-hidden="true"></i>
          <span>Notes</span>
          <i class="ri-arrow-down-s-line ms-auto" aria-hidden="true"></i>
        </a>
        <ul class="sidebar-submenu" role="menu" aria-label="Sous‑menu Notes">
          <li role="none"><a href="{{ route('admin.notes.create') }}" class="{{ request()->routeIs('admin.notes.create','admin.notes.preview','admin.notes.store') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.notes.create','admin.notes.preview','admin.notes.store')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Saisie des notes</a></li>
          <li role="none"><a href="{{ route('admin.notes.index') }}" class="{{ request()->routeIs('admin.notes.index') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.notes.index')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Historique</a></li>
          <li role="none"><a href="{{ route('admin.cahier-notes.index') }}" class="{{ request()->routeIs('admin.cahier-notes.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.cahier-notes.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Cahier de notes</a></li>
        </ul>
      </li>

      {{-- Finance --}}
      <li class="dropdown {{ request()->routeIs('admin.scolarites.*','admin.tranches.*') ? 'open' : '' }}"
          role="none">
        <a href="javascript:void(0)"
           role="menuitem"
           aria-haspopup="true"
           aria-expanded="{{ request()->routeIs('admin.scolarites.*','admin.tranches.*') ? 'true' : 'false' }}">
          <i class="ri-money-dollar-circle-line" aria-hidden="true"></i>
          <span>Finance</span>
          <i class="ri-arrow-down-s-line ms-auto" aria-hidden="true"></i>
        </a>
        <ul class="sidebar-submenu" role="menu" aria-label="Sous‑menu Finance">
          <li role="none"><a href="{{ route('admin.scolarites.index') }}" class="{{ request()->routeIs('admin.scolarites.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.scolarites.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Scolarités & tranches</a></li>
        </ul>
      </li>

      {{-- Cantine (à venir) – désactivé proprement --}}
      <li role="none">
        <a href="#"
           class="disabled-link"
           aria-disabled="true"
           tabindex="-1"
           role="menuitem">
          <i class="ri-restaurant-line" aria-hidden="true"></i>
          <span>Cantine</span>
          <span class="badge bg-secondary ms-auto" style="font-size:9px;">Bientôt</span>
        </a>
      </li>

      {{-- Agenda / Emploi du temps (à venir) --}}
      <li role="none">
        <a href="#"
           class="disabled-link"
           aria-disabled="true"
           tabindex="-1"
           role="menuitem">
          <i class="ri-calendar-schedule-line" aria-hidden="true"></i>
          <span>Emploi du temps</span>
          <span class="badge bg-secondary ms-auto" style="font-size:9px;">Bientôt</span>
        </a>
      </li>

      {{-- Années scolaires --}}
      <li role="none">
        <a href="{{ route('admin.annees-scolaires.index') }}"
           class="{{ request()->routeIs('admin.annees-scolaires.*') ? 'active-page' : '' }}"
           role="menuitem"
           @if(request()->routeIs('admin.annees-scolaires.*')) aria-current="page" @endif>
          <i class="ri-calendar-line" aria-hidden="true"></i>
          <span>Années scolaires</span>
        </a>
      </li>

      {{-- Paramètres --}}
      <li class="dropdown {{ request()->routeIs('admin.communiques.*') ? 'open' : '' }}"
          role="none">
        <a href="javascript:void(0)"
           role="menuitem"
           aria-haspopup="true"
           aria-expanded="{{ request()->routeIs('admin.communiques.*') ? 'true' : 'false' }}">
          <i class="ri-settings-3-line" aria-hidden="true"></i>
          <span>Paramètres</span>
          <i class="ri-arrow-down-s-line ms-auto" aria-hidden="true"></i>
        </a>
        <ul class="sidebar-submenu" role="menu" aria-label="Sous‑menu Paramètres">
          <li role="none"><a href="{{ route('admin.communiques.index') }}" class="{{ request()->routeIs('admin.communiques.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('admin.communiques.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Communiqués</a></li>
          <li role="none"><a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active-page' : '' }}" role="menuitem" @if(request()->routeIs('profile.*')) aria-current="page" @endif><i class="ri-circle-fill circle-icon w-auto" aria-hidden="true"></i> Mon profil</a></li>
        </ul>
      </li>

      {{-- Déconnexion --}}
      <li role="none" class="mt-8">
        <form method="POST" action="{{ route('logout') }}" id="logout-form">
          @csrf
          <a href="#"
             onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
             class="text-danger-600"
             role="menuitem">
            <i class="ri-logout-box-r-line" aria-hidden="true"></i>
            <span>Se déconnecter</span>
          </a>
        </form>
      </li>

    </ul>
  </div>
</aside>

<style>
/* Exemples de classes supplémentaires pour améliorer la cohérence */
.disabled-link {
  opacity: 0.55;
  pointer-events: none;
  cursor: default;
  text-decoration: none;
  color: inherit;
}
.disabled-link:hover,
.disabled-link:focus {
  text-decoration: none;
}
/* Pour le fallback avatar, garder le texte bien centré */
.user-avatar-fallback {
  line-height: 1;
}
</style>
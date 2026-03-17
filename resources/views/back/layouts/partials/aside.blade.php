<aside class="sidebar">
  <button type="button" class="sidebar-close-btn">
    <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
  </button>

  {{-- Logo --}}
  <div class="">
    <div class="d-flex align-items-center gap-8 mt-10 px-2 py-8 radius-8"
         style="background:rgba(0,51,102,.06); border:1px solid rgba(0,51,102,.12);">
      <img src="{{ asset('assets/images/LOGOCCPA.jpeg') }}"
           alt="CCPA"
           class="rounded flex-shrink-0"
           style="width:32px; height:32px; object-fit:contain;">
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

      <button type="button"
              class="text-xxl d-xl-flex d-none line-height-1 sidebar-toggle text-neutral-500"
              aria-label="Collapse Sidebar">
        <i class="ri-contract-left-line"></i>
      </button>
    </div>
  </div>

  {{-- ── USER INFO ───────────────────────────────────────────────── --}}
  <div class="mx-16 py-12">
    <div class="dropdown profile-dropdown">
      <button type="button"
              class="profile-dropdown__button d-flex align-items-center justify-content-between
                     p-10 w-100 overflow-hidden bg-neutral-50 radius-12"
              data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">

        <span class="d-flex align-items-start gap-10">

          {{-- Avatar : photo profil si disponible, sinon initiales --}}
          @php
            $authUser  = Auth::user();
            $photoPath = $authUser->photo ?? null;
            $initiales = strtoupper(mb_substr($authUser->name ?? 'U', 0, 1))
                       . strtoupper(mb_substr(strstr($authUser->name ?? '', ' '), 1, 1));
          @endphp

          @if($photoPath && Storage::disk('public')->exists($photoPath))
            <img src="{{ Storage::url($photoPath) }}"
                 alt="{{ $authUser->name }}"
                 class="w-40-px h-40-px rounded-circle object-fit-cover flex-shrink-0">
          @else
            <span class="w-40-px h-40-px rounded-circle flex-shrink-0 d-flex align-items-center
                         justify-content-center fw-bold text-white"
                  style="background: linear-gradient(135deg, #003366, #0066cc); font-size:14px;">
              {{ $initiales ?: 'U' }}
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

        <span class="profile-dropdown__icon pe-8 text-xl d-flex line-height-1">
          <i class="ri-arrow-right-s-line"></i>
        </span>
      </button>

      
    </div>

    {{-- ── Logo / nom de l'école ──────────────────────────────────── --}}
    
  </div>
  {{-- ── /USER INFO ──────────────────────────────────────────────── --}}

  <div class="sidebar-menu-area">
    <ul class="sidebar-menu" id="sidebar-menu">

      {{-- Dashboard --}}
      <li>
        <a href="{{ route('admin.dashboard') }}">
          <i class="ri-home-4-line"></i>
          <span>Dashboard</span>
        </a>
      </li>

      {{-- Parents --}}
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-account-circle-line"></i>
          <span>Parents</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.parents.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Liste des parents
            </a>
          </li>
          <li>
            <a href="{{ route('admin.parents.import.phpoffice') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Importer parents
            </a>
          </li>
        </ul>
      </li>

      {{-- Élèves --}}
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-group-line"></i>
          <span>Élèves</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.eleves.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Liste des élèves
            </a>
          </li>
          <li>
            <a href="{{ route('admin.eleves.import.create') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Importer élèves
            </a>
          </li>
        </ul>
      </li>

      {{-- Pédagogie --}}
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-book-open-line"></i>
          <span>Pédagogie</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.niveaux.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Niveaux
            </a>
          </li>
          <li>
            <a href="{{ route('admin.classes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Classes
            </a>
          </li>
          <li>
            <a href="{{ route('admin.classe-annees.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Classes par année
            </a>
          </li>
          <li>
            <a href="{{ route('admin.matieres.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Matières
            </a>
          </li>
          <li>
            <a href="{{ route('admin.periodes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Périodes
            </a>
          </li>
          <li>
            <a href="{{ route('admin.type-notes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Types de notes
            </a>
          </li>
        </ul>
      </li>

      {{-- Notes --}}
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-file-list-3-line"></i>
          <span>Notes</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.notes.create') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Saisie des notes
            </a>
          </li>
          <li>
            <a href="{{ route('admin.notes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Historique
            </a>
          </li>
        </ul>
      </li>

      {{-- Finance --}}
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-money-dollar-circle-line"></i>
          <span>Finance</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.scolarites.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Scolarités
            </a>
          </li>
        </ul>
      </li>

      {{-- Cantine (à venir) --}}
      <li>
        <a href="#" style="opacity:.45; pointer-events:none; cursor:default;">
          <i class="ri-restaurant-line"></i>
          <span>Cantine</span>
          <span class="badge bg-secondary ms-auto" style="font-size:9px;">Bientôt</span>
        </a>
      </li>

      {{-- Années scolaires --}}
      <li>
        <a href="{{ route('admin.annees-scolaires.index') }}">
          <i class="ri-calendar-line"></i>
          <span>Années scolaires</span>
        </a>
      </li>

      {{-- Paramètres --}}
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-settings-3-line"></i>
          <span>Paramètres</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="#">
              <i class="ri-circle-fill circle-icon w-auto"></i> Général
            </a>
          </li>
          <li>
            <a href="{{ route('admin.communiques.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i> Communiqués
            </a>
          </li>
        </ul>
      </li>

    </ul>
  </div>
</aside>
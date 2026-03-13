<aside class="sidebar">
  <button type="button" class="sidebar-close-btn">
    <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
  </button>
  <div class="">
    <div class="sidebar-logo d-flex align-items-center justify-content-between">
      <a href="" class="">
        <img src="{{ asset('assets/images/logo.png') }}" alt="site logo" class="light-logo">
        <img src="{{ asset('assets/images/logo-light.png') }}" alt="site logo" class="dark-logo">
        <img src="{{ asset('assets/images/logo-icon.png') }}" alt="site logo" class="logo-icon">
      </a>
      <button type="button" class="text-xxl d-xl-flex d-none line-height-1 sidebar-toggle text-neutral-500"
        aria-label="Collapse Sidebar">
        <i class="ri-contract-left-line"></i>
      </button>
    </div>
  </div>
  <!-- User Info start -->
  <div class="mx-16 py-12">
    <div class="dropdown profile-dropdown">
      <button type="button"
        class="profile-dropdown__button d-flex align-items-center justify-content-between p-10 w-100 overflow-hidden bg-neutral-50 radius-12 "
        data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
        <span class="d-flex align-items-start gap-10">
          <img src="{{ asset('assets/images/thumbs/leave-request-img2.png') }}" alt="Thumbnail"
            class="w-40-px h-40-px rounded-circle object-fit-cover flex-shrink-0">
          <span class="profile-dropdown__contents">
            <span class="h6 mb-0 text-md d-block text-primary-light">Jone Copper</span>
            <span class="text-secondary-light text-sm mb-0 d-block">Admin</span>
          </span>
        </span>
        <span class="profile-dropdown__icon pe-8 text-xl d-flex line-height-1">
          <i class="ri-arrow-right-s-line"></i>
        </span>
      </button>
      <ul class="dropdown-menu dropdown-menu-lg-end border p-12">
        <li>
          <a href="student-details.html" 
            class="dropdown-item rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 d-flex align-items-center gap-2 py-6">
            <i class="ri-user-3-line"></i>
            My Profile
          </a>
        </li>
        <li>
          <a href="general.html"
            class="dropdown-item rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 d-flex align-items-center gap-2 py-6">
            <i class="ri-settings-3-line"></i>
            Setting
          </a>
        </li>
        <li>
          <a href="login.html"
            class="dropdown-item rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 d-flex align-items-center gap-2 py-6">
            <i class="ri-shut-down-line"></i>
            Log Out
          </a>
        </li>
      </ul>
    </div>
  </div>
  <!-- User Info end -->
  <div class="sidebar-menu-area">
    <ul class="sidebar-menu" id="sidebar-menu">
      <!-- Dashboard -->
      <li>
        <a href="{{ route('admin.dashboard') }}">
          <i class="ri-home-4-line"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <!-- Gestion des parents -->
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

      <!-- Gestion des élèves -->
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

      <!-- Gestion pédagogique -->
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-book-open-line"></i>
          <span>Pédagogie</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.niveaux.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Niveaux
            </a>
          </li>
          <li>
            <a href="{{ route('admin.classes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Classes
            </a>
          </li>
          <li>
            <a href="{{ route('admin.classe-annees.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Classes par année
            </a>
          </li>
          <li>
            <a href="{{ route('admin.matieres.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Matières
            </a>
          </li>
          <li>
            <a href="{{ route('admin.periodes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Périodes
            </a>
          </li>
          <li>
            <a href="{{ route('admin.type-notes.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Types de notes
            </a>
          </li>
        </ul>
      </li>
      <!-- Dans la section des menus, après "Scolarités" par exemple -->

<li class="nav-item">
    <a href="#menuNotes" data-bs-toggle="collapse" role="button" aria-expanded="false" class="nav-link">
        <i class="ri-file-list-3-line"></i> Gestion des notes
        <i class="ri-arrow-down-s-line ms-auto"></i>
    </a>
    <ul class="collapse list-unstyled" id="menuNotes">
        <li><a href="{{ route('admin.periodes.index') }}" class="nav-link ms-3"><i class="ri-calendar-todo-line"></i> Périodes</a></li>
        <li><a href="{{ route('admin.type-notes.index') }}" class="nav-link ms-3"><i class="ri-price-tag-3-line"></i> Types de notes</a></li>
        <li><a href="{{ route('admin.notes.create') }}" class="nav-link ms-3"><i class="ri-edit-box-line"></i> Saisie des notes</a></li>
        <li><a href="{{ route('admin.notes.index') }}" class="nav-link ms-3"><i class="ri-history-line"></i> Historique</a></li>
    </ul>
</li>

<!-- Lien vers la cantine (module futur) -->
<li class="nav-item">
    <a href="#" class="nav-link disabled" style="opacity:0.6; pointer-events:none;" aria-disabled="true">
        <i class="ri-restaurant-line"></i> Cantine (à venir)
    </a>
</li>

      <!-- Gestion financière -->
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-money-dollar-circle-line"></i>
          <span>Finance</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="{{ route('admin.scolarites.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Scolarités
            </a>
          </li>
          <!-- Ajouter plus tard : Paiements, etc. -->
        </ul>
      </li>

      <!-- Années scolaires (lien direct) -->
      <li>
        <a href="{{ route('admin.annees-scolaires.index') }}">
          <i class="ri-calendar-line"></i>
          <span>Années scolaires</span>
        </a>
      </li>

      <!-- Autres éléments à venir (cantine, discipline) seront ajoutés plus tard -->

      <!-- Optionnel : Paramètres -->
      <li class="dropdown">
        <a href="javascript:void(0)">
          <i class="ri-settings-3-line"></i>
          <span>Paramètres</span>
          <i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="#">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Général
            </a>
          </li>
          <li>
            <a href="{{ route('admin.communiques.index') }}">
              <i class="ri-circle-fill circle-icon w-auto"></i>
              Communiqués
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</aside>
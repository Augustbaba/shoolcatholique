@extends('back.layouts.master')
@section('title', 'Tableau de bord')
@section('content')

<div class="dashboard-main-body">

    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div class="">
            <h6 class="fw-semibold mb-0">Dashboard</h6>
            <p class="text-neutral-600 mt-4 mb-0">School -> Gérez votre école, suivez les présences, les dépenses et la trésorerie.</p>
        </div>
    </div>

    <div class="mt-24">
        <div class="row gy-4">
            <div class="col-xxl-8">
                <div class="row gy-4">
                    {{-- Carte 1 : Total Élèves --}}
                    <div class="col-xxl-4 col-sm-6">
                        <div class="card shadow-1 radius-8 gradient-bg-end-1 h-100">
                            <div class="card-body p-20">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-16">
                                    <div class="w-44-px h-44-px bg-warning-600 rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/dashboard-icon1.png') }}" alt="Icon">
                                    </div>
                                    <p class="fw-medium text-primary-light mb-1">Total Élèves</p>
                                </div>
                                <h6 class="mb-0">{{ number_format($totalEleves, 0, ',', ' ') }}</h6>
                                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center gap-1 text-primary-600 text-sm fw-semibold">
                                        {{ $elevesActifs }} actifs
                                    </span>
                                    ({{ $nouvellesInscriptions }} nouveaux ce mois)
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Carte 2 : Total Enseignants --}}
                    <div class="col-xxl-4 col-sm-6">
                        <div class="card shadow-1 radius-8 gradient-bg-end-2 h-100">
                            <div class="card-body p-20">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-16">
                                    <div class="w-44-px h-44-px bg-blue-600 rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/dashboard-icon2.png') }}" alt="Icon">
                                    </div>
                                    <p class="fw-medium text-primary-light mb-1">Total Enseignants</p>
                                </div>
                                <h6 class="mb-0">{{ number_format($totalEnseignants, 0, ',', ' ') }}</h6>
                                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center gap-1 text-primary-600 text-sm fw-semibold">
                                        {{ $totalEnseignants }} inscrits
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Carte 3 : Total Parents --}}
                    <div class="col-xxl-4 col-sm-6">
                        <div class="card shadow-1 radius-8 gradient-bg-end-3 h-100">
                            <div class="card-body p-20">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-16">
                                    <div class="w-44-px h-44-px bg-purple-600 rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/dashboard-icon3.png') }}" alt="Icon">
                                    </div>
                                    <p class="fw-medium text-primary-light mb-1">Total Parents</p>
                                </div>
                                <h6 class="mb-0">{{ number_format($totalParents, 0, ',', ' ') }}</h6>
                                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center gap-1 text-primary-600 text-sm fw-semibold">
                                        {{ $totalParents }} comptes
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Carte 4 : Total Classes --}}
                    <div class="col-xxl-4 col-sm-6">
                        <div class="card shadow-1 radius-8 gradient-bg-end-4 h-100">
                            <div class="card-body p-20">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-16">
                                    <div class="w-44-px h-44-px bg-primary-600 rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/dashboard-icon4.png') }}" alt="Icon">
                                    </div>
                                    <p class="fw-medium text-primary-light mb-1">Total Classes</p>
                                </div>
                                <h6 class="mb-0">{{ number_format($totalClasses, 0, ',', ' ') }}</h6>
                                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center gap-1 text-primary-600 text-sm fw-semibold">
                                        {{ $totalClasses }} classes
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Carte 5 : Élèves Hommes --}}
                    <div class="col-xxl-4 col-sm-6">
                        <div class="card shadow-1 radius-8 gradient-bg-end-5 h-100">
                            <div class="card-body p-20">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-16">
                                    <div class="w-44-px h-44-px bg-success-600 rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/dashboard-icon5.png') }}" alt="Icon">
                                    </div>
                                    <p class="fw-medium text-primary-light mb-1">Élèves (Hommes)</p>
                                </div>
                                <h6 class="mb-0">{{ number_format($elevesHommes, 0, ',', ' ') }}</h6>
                                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center gap-1 text-primary-600 text-sm fw-semibold">
                                        {{ $elevesHommes }} garçons
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Carte 6 : Élèves Femmes --}}
                    <div class="col-xxl-4 col-sm-6">
                        <div class="card shadow-1 radius-8 gradient-bg-end-6 h-100">
                            <div class="card-body p-20">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-16">
                                    <div class="w-44-px h-44-px bg-cyan-600 rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/dashboard-icon6.png') }}" alt="Icon">
                                    </div>
                                    <p class="fw-medium text-primary-light mb-1">Élèves (Femmes)</p>
                                </div>
                                <h6 class="mb-0">{{ number_format($elevesFemmes, 0, ',', ' ') }}</h6>
                                <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center gap-1 text-primary-600 text-sm fw-semibold">
                                        {{ $elevesFemmes }} filles
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Student Attendance (statique pour l'instant) --}}
            <div class="col-xxl-4">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                            <h6 class="text-lg mb-0">Assiduité des élèves</h6>
                        </div>
                        <div class="p-20">
                            <div class="d-flex gap-6">
                                <div class="h-44-px bg-primary-600 rounded" style="width: {{ $presentPourcent }}%;"></div>
                                <div class="h-44-px bg-warning-600 rounded" style="width: {{ $absentPourcent }}%;"></div>
                                <div class="h-44-px bg-purple-600 rounded" style="width: {{ $retardPourcent }}%;"></div>
                                <div class="h-44-px bg-success-600 rounded" style="width: {{ $demiJourneePourcent }}%;"></div>
                            </div>
                            <div class="mt-32 d-flex flex-column gap-24">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="w-12-px h-12-px radius-2 bg-primary-600"></span>
                                        <span class="text-neutral-600">Présent</span>
                                    </div>
                                    <span class="fw-semibold text-primary-light">{{ $presentPourcent }}%</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="w-12-px h-12-px radius-2 bg-warning-600"></span>
                                        <span class="text-neutral-600">Absent</span>
                                    </div>
                                    <span class="fw-semibold text-primary-light">{{ $absentPourcent }}%</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="w-12-px h-12-px radius-2 bg-purple-600"></span>
                                        <span class="text-neutral-600">Retard</span>
                                    </div>
                                    <span class="fw-semibold text-primary-light">{{ $retardPourcent }}%</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="w-12-px h-12-px radius-2 bg-success-600"></span>
                                        <span class="text-neutral-600">Demi-journée</span>
                                    </div>
                                    <span class="fw-semibold text-primary-light">{{ $demiJourneePourcent }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row gy-4">
                    <div class="col-xxl-8">
                        <div class="row gy-4">
                            {{-- Revenue Statistic --}}
                            <div class="col-12">
                                <div class="card h-100">
                                    <div class="card-body p-0">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                                            <h6 class="text-lg mb-0">Statistiques des revenus</h6>
                                        </div>
                                        <div class="p-20">
                                            <ul class="d-flex flex-wrap align-items-center justify-content-center mb-16 gap-3">
                                                <li class="d-flex align-items-center gap-8">
                                                    <span class="w-12-px h-12-px radius-2 rotate-45-deg bg-primary-600"></span>
                                                    <span class="text-secondary-light text-sm fw-semibold">
                                                        Total encaissé (année) :
                                                        <span class="text-primary-light fw-bold">{{ number_format($paiementsAnnee, 0, ',', ' ') }} FCFA</span>
                                                    </span>
                                                </li>
                                                <li class="d-flex align-items-center gap-8">
                                                    <span class="w-12-px h-12-px radius-2 rotate-45-deg bg-warning-600"></span>
                                                    <span class="text-secondary-light text-sm font-semibold">
                                                        Total historique :
                                                        <span class="text-primary-light fw-bold">{{ number_format($totalPaiements, 0, ',', ' ') }} FCFA</span>
                                                    </span>
                                                </li>
                                            </ul>
                                            <div id="revenueStatistic"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Notice Board (Communiqués) --}}
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body p-0">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                                            <h6 class="text-lg mb-0">Tableau d'affichage</h6>
                                            <div class="dropdown">
                                                <button type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <iconify-icon icon="entypo:dots-three-vertical" class="icon text-secondary-light"></iconify-icon>
                                                </button>
                                                <ul class="dropdown-menu p-12 border bg-base shadow">
                                                    <li><a href="{{ route('admin.communiques.index') }}" class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 d-flex align-items-center gap-10"><iconify-icon icon="hugeicons:view" class="icon text-lg line-height-1"></iconify-icon> Voir tout</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="ps-20 pt-20 pb-20">
                                            <div class="pe-20 d-flex flex-column gap-20 max-h-462-px overflow-y-auto scroll-sm">
                                                @forelse($communiques as $com)
                                                <div class="d-flex align-items-start gap-16">
                                                    <img src="{{ asset('assets/images/thumbs/notice-board-img1.png') }}" alt="User" class="w-40-px h-40-px rounded-circle object-fit-cover flex-shrink-0">
                                                    <div class="">
                                                        <h6 class="mb-4 text-lg">{{ $com->titre }}</h6>
                                                        <p class="text-secondary-light text-sm mb-0">{{ Str::limit($com->contenu, 80) }}</p>
                                                        <span class="text-secondary-light text-sm mb-0 mt-4">{{ $com->date_publication->format('d M Y') }}</span>
                                                    </div>
                                                </div>
                                                @empty
                                                <p class="text-secondary-light">Aucun communiqué récent.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Leave Requests (pour l'instant statique) --}}
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body p-0">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                                            <h6 class="text-lg mb-0">Demandes de congé</h6>
                                            <div class="dropdown">
                                                <button type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <iconify-icon icon="entypo:dots-three-vertical" class="icon text-secondary-light"></iconify-icon>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="ps-20 pt-20 pb-20">
                                            <div class="pe-20 d-flex flex-column gap-28 max-h-462-px overflow-y-auto scroll-sm">
                                                {{-- Ici vous pouvez mettre des données réelles si vous avez une table de congés --}}
                                                <p class="text-secondary-light">Aucune demande pour le moment.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Calendar & Upcoming Events --}}
                    <div class="col-xxl-4">
                        <div class="card h-100">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                                    <h6 class="text-lg mb-0">Calendrier</h6>
                                </div>

                                <div class="p-20">
                                    <div class="calendar">
                                        <div class="calendar__header">
                                            <button type="button" class="calendar__arrow left"><i class="ri-arrow-left-s-line"></i></button>
                                            <p class="display text-md text-secondary-light fw-semibold mb-0" id="currentMonthYear"></p>
                                            <button type="button" class="calendar__arrow right"><i class="ri-arrow-right-s-line"></i></button>
                                        </div>
                                        <div class="calendar__week week">
                                            <div class="calendar__week-text">Di</div><div class="calendar__week-text">Lu</div><div class="calendar__week-text">Ma</div><div class="calendar__week-text">Me</div><div class="calendar__week-text">Je</div><div class="calendar__week-text">Ve</div><div class="calendar__week-text">Sa</div>
                                        </div>
                                        <div class="days"></div>
                                    </div>
                                </div>

                                <div class="ps-20 pt-20 pb-20 border-top border-neutral-200">
                                    <h6 class="text-lg mb-20">Événements à venir</h6>
                                    <div class="pe-20 d-flex flex-column gap-32 overflow-y-auto max-h-500-px scroll-sm">
                                        @forelse($evenements as $event)
                                        <div class="d-flex align-items-center justify-content-between gap-16">
                                            <div class="ps-10 border-start-width-3-px border-purple-600">
                                                <div class="d-flex align-items-end gap-6">
                                                    <h6 class="text-lg fw-normal mb-0">{{ $event->date_publication->format('H:i') }}</h6>
                                                    <span class="text-xs text-secondary-light line-height-1 mb-2">{{ $event->date_publication->format('A') }}</span>
                                                </div>
                                                <p class="text-secondary-light mt-4 mb-2 text-sm">{{ $event->titre }}</p>
                                                <p class="text-xs text-secondary-light mb-0">{{ Str::limit($event->contenu, 30) }}</p>
                                            </div>
                                            <div>
                                                <a href="{{ route('admin.communiques.show', $event->id) }}" class="py-6 px-16 radius-4 bg-neutral-100 text-secondary-light fw-semibold bg-hover-primary-600 hover-text-white">Voir</a>
                                            </div>
                                        </div>
                                        @empty
                                        <p class="text-secondary-light">Aucun événement prévu.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- User Overview --}}
            <div class="col-xxl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                            <h6 class="text-lg mb-0">Aperçu des utilisateurs</h6>
                        </div>
                        <div class="p-20">
                            <div>
                                <div class="mt-40 mb-24 pe-110 position-relative max-w-288-px mx-auto">
                                    <div class="w-170-px h-170-px rounded-circle z-1 position-relative d-inline-flex justify-content-center align-items-center">
                                        <img src="{{ asset('assets/images/icons/radial-bg1.png') }}" alt="Image" class="position-absolute top-0 start-0 z-n1 w-100 h-100 object-fit-cover">
                                        <h5 class="text-white">{{ round(($totalEleves / max($totalEleves+$totalEnseignants,1))*100) }}%</h5>
                                    </div>
                                    <div class="w-144-px h-144-px rounded-circle z-1 position-relative d-inline-flex justify-content-center align-items-center position-absolute top-0 end-0 mt--36">
                                        <img src="{{ asset('assets/images/icons/radial-bg2.png') }}" alt="Image" class="position-absolute top-0 start-0 z-n1 w-100 h-100 object-fit-cover">
                                        <h5 class="text-white">{{ round(($totalEnseignants / max($totalEleves+$totalEnseignants,1))*100) }}%</h5>
                                    </div>
                                    <div class="w-110-px h-110-px rounded-circle z-1 position-relative d-inline-flex justify-content-center align-items-center position-absolute bottom-0 start-50 translate-middle-x ms-48">
                                        <img src="{{ asset('assets/images/icons/radial-bg3.png') }}" alt="Image" class="position-absolute top-0 start-0 z-n1 w-100 h-100 object-fit-cover">
                                        <h5 class="text-white">{{ round(($totalParents / max($totalEleves+$totalEnseignants+$totalParents,1))*100) }}%</h5>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center flex-wrap gap-24 justify-content-evenly">
                                    <div class="d-flex flex-column align-items-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="w-12-px h-12-px rounded-pill bg-success-600"></span>
                                            <span class="text-secondary-light text-sm fw-normal">Élèves</span>
                                        </div>
                                        <h6 class="text-primary-light fw-semibold mb-0 mt-4 text-lg">{{ $totalEleves }}</h6>
                                    </div>
                                    <div class="d-flex flex-column align-items-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="w-12-px h-12-px rounded-pill bg-warning-600"></span>
                                            <span class="text-secondary-light text-sm fw-normal">Enseignants</span>
                                        </div>
                                        <h6 class="text-primary-light fw-semibold mb-0 mt-4 text-lg">{{ $totalEnseignants }}</h6>
                                    </div>
                                    <div class="d-flex flex-column align-items-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="w-12-px h-12-px rounded-pill bg-blue-600"></span>
                                            <span class="text-secondary-light text-sm fw-normal">Parents</span>
                                        </div>
                                        <h6 class="text-primary-light fw-semibold mb-0 mt-4 text-lg">{{ $totalParents }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Income vs Expense --}}
            <div class="col-xxl-8 col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                            <h6 class="text-lg mb-0">Revenus vs Dépenses</h6>
                        </div>
                        <div class="p-20">
                            <ul class="d-flex flex-wrap align-items-center justify-content-center mb-16 gap-3">
                                <li class="d-flex align-items-center gap-8">
                                    <span class="w-12-px h-12-px rounded-circle bg-primary-600"></span>
                                    <span class="text-secondary-light text-sm fw-semibold">
                                        Revenus (année) :
                                        <span class="text-primary-light fw-bold">{{ number_format($paiementsAnnee, 0, ',', ' ') }} FCFA</span>
                                    </span>
                                </li>
                                <li class="d-flex align-items-center gap-8">
                                    <span class="w-12-px h-12-px rounded-circle bg-warning-600"></span>
                                    <span class="text-secondary-light text-sm font-semibold">
                                        Dépenses :
                                        <span class="text-primary-light fw-bold">{{ number_format($depenses, 0, ',', ' ') }} FCFA</span>
                                    </span>
                                </li>
                            </ul>
                            <div id="incomeExpense" class="apexcharts-tooltip-style-1"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Teachers --}}
            <div class="col-xxl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                            <h6 class="text-lg mb-0">Meilleurs enseignants</h6>
                            <div class="dropdown">
                                <button type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="entypo:dots-three-vertical" class="icon text-secondary-light"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        <div class="ps-20 pt-20 pb-20">
                            <div class="pe-20 d-flex flex-column gap-20 max-h-462-px overflow-y-auto scroll-sm">
                                @forelse($topEnseignants as $teacher)
                                <div class="d-flex align-items-center justify-content-between gap-16">
                                    <div class="d-flex align-items-start gap-16">
                                        <img src="{{ asset('assets/images/thumbs/top-teacher-img1.png') }}" alt="Teacher" class="w-40-px h-40-px rounded-circle object-fit-cover flex-shrink-0">
                                        <div class="">
                                            <h6 class="mb-0 text-lg">{{ $teacher->prenom }} {{ $teacher->nom }}</h6>
                                            <span class="text-secondary-light text-sm mb-0">{{ $teacher->user->email ?? 'email@exemple.com' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="d-block fw-semibold text-primary-light">{{ $teacher->matiere_principale ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                @empty
                                <p class="text-secondary-light">Aucun enseignant trouvé.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- New Admissions --}}
            <div class="col-xxl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between px-20 py-16 border-bottom border-neutral-200">
                            <h6 class="text-lg mb-0">Nouvelles inscriptions (30 jours)</h6>
                        </div>
                        <div class="p-20">
                            <div class="position-relative text-center">
                                <div id="newAdmissions" class="y-value-left apexcharts-tooltip-z-none"></div>
                                <div class="text-center position-absolute top-50 start-50 translate-middle">
                                    <h5 class="mb-4">{{ $nouvellesInscriptions }}</h5>
                                    <span class="text-secondary-light">Nouveaux élèves</span>
                                </div>
                            </div>
                            <ul class="d-flex flex-wrap align-items-center justify-content-center mt-48 gap-24">
                                <li class="d-flex align-items-center gap-2">
                                    <span class="w-12-px h-12-px radius-2 bg-success-600 rotate-45-deg"></span>
                                    <span class="text-secondary-light fw-medium">Garçons: <span class="fw-bold text-primary-light">{{ $elevesHommes }}</span></span>
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="w-12-px h-12-px radius-2 bg-blue-600 rotate-45-deg"></span>
                                    <span class="text-secondary-light fw-medium">Filles: <span class="fw-bold text-primary-light">{{ $elevesFemmes }}</span></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Student --}}
            <div class="col-xxl-4">
                <div class="card radius-12 border-0 h-100">
                    <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between py-12 px-20 border-bottom border-neutral-200">
                        <h6 class="mb-2 fw-bold text-lg">Meilleurs élèves (période en cours)</h6>
                        <div class="dropdown">
                            <button type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <iconify-icon icon="entypo:dots-three-vertical" class="icon text-secondary-light"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-28">
                            @forelse($topEtudiants as $etudiant)
                            <div class="d-flex align-items-center justify-content-between gap-10">
                                <div class="d-flex align-items-center gap-12">
                                    <span class="w-44-px h-44-px rounded-circle d-flex justify-content-center align-items-center">
                                        <img src="{{ $etudiant->photo ? asset('storage/'.$etudiant->photo) : asset('assets/images/thumbs/avatar-default.png') }}" class="w-44-px h-44-px object-fit-cover rounded-circle" alt="Avatar">
                                    </span>
                                    <div class="">
                                        <h6 class="text-sm mb-2">{{ $etudiant->prenom }} {{ $etudiant->nom }}</h6>
                                        <span class="text-xs text-secondary-light">Classe: {{ $etudiant->classeAnnee->classe->niveau->nom ?? '' }} {{ $etudiant->classeAnnee->classe->suffixe ?? '' }}</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-8">
                                    <span class="text-sm text-secondary-light">Moy.</span>
                                    <span class="text-primary-light text-sm d-block text-end">
                                        <strong>{{ number_format($etudiant->moyenne, 2) }}</strong>
                                    </span>
                                </div>
                            </div>
                            @empty
                            <p class="text-secondary-light">Aucune note disponible pour cette période.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Petit script pour mettre à jour le mois dans le calendrier (optionnel)
    document.addEventListener('DOMContentLoaded', function() {
        const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        const now = new Date();
        document.getElementById('currentMonthYear').innerText = monthNames[now.getMonth()] + ' ' + now.getFullYear();
    });
</script>
@endpush
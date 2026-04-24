<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MyEdu') }} | Accueil</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell-body text-slate-900 antialiased">
@php
    $highlights = [
        [
            'title' => 'Encadrement de qualité',
            'description' => 'Une équipe pédagogique structurée, disponible et attentive à la progression de chaque élève.',
        ],
        [
            'title' => 'Pédagogie moderne',
            'description' => 'Des outils clairs pour organiser les apprentissages, suivre les évaluations et valoriser les acquis.',
        ],
        [
            'title' => 'Suivi des élèves',
            'description' => 'Absences, résultats, observations et informations essentielles centralisés dans un même espace.',
        ],
        [
            'title' => 'Relation familles-école',
            'description' => 'Une communication plus simple avec les parents grâce à des échanges mieux organisés.',
        ],
    ];

    $features = [
        [
            'title' => 'Gestion des élèves',
            'description' => 'Inscriptions, dossiers, classes et historique scolaire dans un parcours fluide.',
        ],
        [
            'title' => 'Suivi pédagogique',
            'description' => 'Devoirs, évaluations, notes et observations accessibles selon le profil utilisateur.',
        ],
        [
            'title' => 'Communication',
            'description' => 'Informations, messages et annonces transmis avec plus de régularité et de visibilité.',
        ],
        [
            'title' => 'Emploi du temps',
            'description' => 'Une organisation claire des cours, des salles et des créneaux pour chaque niveau.',
        ],
        [
            'title' => 'Transport scolaire',
            'description' => 'Gestion des circuits, des points d’arrêt et des affectations en toute lisibilité.',
        ],
        [
            'title' => 'Paiements et services',
            'description' => 'Suivi administratif et financier avec un espace mieux structuré pour l’établissement.',
        ],
        [
            'title' => 'Actualités de l’école',
            'description' => 'Vie scolaire, temps forts et informations pratiques diffusés dans un cadre professionnel.',
        ],
        [
            'title' => 'Espaces dédiés',
            'description' => 'Interfaces distinctes pour l’administration, les enseignants, les parents et les élèves.',
        ],
    ];

    $stats = [
        ['value' => '1 portail', 'label' => 'pour coordonner les services'],
        ['value' => '3 espaces', 'label' => 'administration, enseignants, parents'],
        ['value' => '100 %', 'label' => 'pensé pour un usage quotidien'],
        ['value' => '24/7', 'label' => 'accès aux informations essentielles'],
    ];
@endphp
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

<div class="landing-page">
    <div class="landing-glow landing-glow-left" aria-hidden="true"></div>
    <div class="landing-glow landing-glow-right" aria-hidden="true"></div>

    <div class="landing-content">
    <header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-white/70 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex min-h-[4.75rem] items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-900 text-sm font-semibold tracking-[0.18em] text-white shadow-[0_18px_35px_-24px_rgba(15,23,42,0.7)]">
                    ME
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-950">{{ config('app.name', 'MyEdu') }}</p>
                    <p class="text-xs text-slate-500">Plateforme de gestion scolaire</p>
                </div>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                <a href="#presentation" class="transition hover:text-slate-950">Établissement</a>
                <a href="#atouts" class="transition hover:text-slate-950">Atouts</a>
                <a href="#plateforme" class="transition hover:text-slate-950">Plateforme</a>
                <a href="#application" class="transition hover:text-slate-950">Application</a>
                <a href="#contact" class="transition hover:text-slate-950">Contact</a>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                <a href="#contact" class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950">
                    Nous contacter
                </a>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-[0_18px_35px_-24px_rgba(15,23,42,0.8)] transition hover:bg-slate-800">
                        Accéder à la plateforme
                    </a>
                @endif
            </div>

            <button @click="open = ! open" type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:text-slate-950 md:hidden" aria-label="Ouvrir le menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16" />
                    <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6l-12 12" />
                </svg>
            </button>
        </div>

        <div :class="{ 'block': open, 'hidden': !open }" class="hidden border-t border-slate-200 bg-white md:hidden">
            <div class="space-y-1 px-4 py-4 text-sm font-medium text-slate-700">
                <a href="#presentation" class="block rounded-2xl px-4 py-3 transition hover:bg-slate-50">Établissement</a>
                <a href="#atouts" class="block rounded-2xl px-4 py-3 transition hover:bg-slate-50">Atouts</a>
                <a href="#plateforme" class="block rounded-2xl px-4 py-3 transition hover:bg-slate-50">Plateforme</a>
                <a href="#application" class="block rounded-2xl px-4 py-3 transition hover:bg-slate-50">Application mobile</a>
                <a href="#contact" class="block rounded-2xl px-4 py-3 transition hover:bg-slate-50">Contact</a>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="mt-2 inline-flex w-full min-h-11 items-center justify-center rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Accéder à la plateforme
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main>
        <section class="relative">
            <div class="mx-auto grid max-w-7xl gap-14 px-4 pb-20 pt-14 sm:px-6 lg:grid-cols-[1.08fr_0.92fr] lg:px-8 lg:pb-28 lg:pt-20">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-sky-800 shadow-sm backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-sky-600"></span>
                        Éducation, rigueur et clarté
                    </span>

                    <h1 class="mt-8 max-w-xl font-serif text-4xl leading-tight tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                        Une page d’accueil à l’image d’un établissement exigeant.
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                        {{ config('app.name', 'MyEdu') }} réunit la vie scolaire, le suivi pédagogique et la relation avec les familles dans une expérience plus lisible, plus fiable et plus professionnelle.
                    </p>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white shadow-[0_22px_45px_-28px_rgba(15,23,42,0.75)] transition hover:bg-slate-800">
                                Accéder à la plateforme
                            </a>
                        @endif
                        <a href="#presentation" class="inline-flex min-h-12 items-center justify-center rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950">
                            Découvrir l’établissement
                        </a>
                    </div>

                    <div class="mt-12 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white/80 bg-white/80 p-5 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.32)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pilotage</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">Administration structurée</p>
                        </div>
                        <div class="rounded-3xl border border-white/80 bg-white/80 p-5 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.32)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pédagogie</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">Suivi clair des apprentissages</p>
                        </div>
                        <div class="rounded-3xl border border-white/80 bg-white/80 p-5 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.32)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Familles</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">Communication maîtrisée</p>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/80 p-6 shadow-[0_30px_80px_-45px_rgba(15,23,42,0.35)] backdrop-blur sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Vue d’ensemble</p>
                                <h2 class="mt-3 font-serif text-2xl text-slate-950">Un accueil clair pour toute la communauté scolaire</h2>
                            </div>
                            <div class="rounded-2xl bg-slate-950 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white">
                                Premium
                            </div>
                        </div>

                        <div class="mt-8 grid gap-4">
                            <div class="rounded-3xl bg-slate-950 p-6 text-white">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-white/60">Direction</p>
                                        <p class="mt-2 text-xl font-semibold">Vision unifiée de l’activité scolaire</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 p-3">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17l5-5 3 3 8-8" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-white/75">
                                    Présences, communication, organisation et services réunis dans une interface cohérente.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Parents</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-950">Informations utiles au bon moment</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">Suivi, messages et services accessibles dans un espace dédié.</p>
                                </div>
                                <div class="rounded-3xl border border-slate-200 bg-white p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Équipe pédagogique</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-950">Organisation quotidienne simplifiée</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">Cours, évaluations et emploi du temps rassemblés de manière lisible.</p>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-white p-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Expérience</p>
                                        <p class="mt-2 text-lg font-semibold text-slate-950">Une page d’accueil pensée pour inspirer confiance</p>
                                    </div>
                                    <a href="#contact" class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-950">
                                        Demander des informations
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="atouts" class="scroll-mt-24 border-y border-white/10 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl landing-section-copy">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-200">Nos atouts</p>
                    <h2 class="mt-4 font-serif text-3xl tracking-tight text-slate-950 sm:text-4xl">Un cadre de travail rassurant, moderne et bien structuré.</h2>
                    <p class="mt-5 text-base leading-7 text-slate-600">
                        Chaque section de la plateforme répond à un besoin concret de l’établissement: mieux organiser, mieux communiquer et mieux suivre la vie scolaire.
                    </p>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($highlights as $highlight)
                        <article class="group rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-[0_24px_60px_-42px_rgba(15,23,42,0.28)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_28px_80px_-46px_rgba(15,23,42,0.32)]">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 4v5c0 4.2-2.8 8-7 9-4.2-1-7-4.8-7-9V7l7-4z" />
                                </svg>
                            </div>
                            <h3 class="mt-5 text-xl font-semibold tracking-tight text-slate-950">{{ $highlight['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $highlight['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="presentation" class="scroll-mt-24 py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
                <div class="landing-panel-light rounded-[2rem] p-8 text-slate-900 sm:p-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-200">Présentation</p>
                    <h2 class="mt-4 font-serif text-3xl tracking-tight sm:text-4xl">Une identité institutionnelle claire dès la première visite.</h2>
                    <p class="mt-6 text-base leading-7 text-slate-600">
                        Cette page d’accueil donne immédiatement une image sérieuse de l’établissement ou de la plateforme. Elle met en avant l’organisation, la qualité du suivi et la simplicité des échanges.
                    </p>
                    <div class="mt-8 space-y-4">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                            <p class="text-sm font-semibold">Accueil premium</p>
                            <p class="mt-1 text-sm leading-6 text-white/65">Une hiérarchie visuelle nette, des appels à l’action visibles et un ton professionnel.</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                            <p class="text-sm font-semibold">Lecture intuitive</p>
                            <p class="mt-1 text-sm leading-6 text-white/65">Les visiteurs comprennent rapidement l’offre, les services et les prochaines étapes.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div class="landing-panel-light rounded-[2rem] p-8 sm:p-10">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-800">Ce que la page raconte</p>
                        <h3 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950">Un établissement bien organisé, tourné vers la qualité de service.</h3>
                        <p class="mt-5 text-base leading-7 text-slate-600">
                            La présentation met en avant une école attentive à ses élèves, structurée dans sa gestion et exigeante dans sa communication. Le message reste simple, crédible et utile.
                        </p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl bg-slate-50 p-5">
                                <p class="text-sm font-semibold text-slate-950">Vie scolaire mieux suivie</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Des informations plus accessibles pour les équipes comme pour les familles.</p>
                            </div>
                            <div class="rounded-3xl bg-slate-50 p-5">
                                <p class="text-sm font-semibold text-slate-950">Services plus visibles</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Modules, fonctionnalités et parcours de connexion clairement présentés.</p>
                            </div>
                        </div>
                    </div>

                    <div class="landing-panel-light rounded-[2rem] p-8">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Parcours</p>
                                <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">Une progression naturelle d’une section à l’autre</h3>
                            </div>
                            <div class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                                Hero, atouts, services, contact
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="plateforme" class="scroll-mt-24 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl landing-section-copy">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-800">Modules et services</p>
                        <h2 class="mt-4 font-serif text-3xl tracking-tight text-slate-950 sm:text-4xl">Les fonctions clés d’une gestion scolaire professionnelle.</h2>
                        <p class="mt-5 text-base leading-7 text-slate-600">
                            La page valorise les modules les plus utiles au fonctionnement quotidien d’un établissement moderne, sans surcharge visuelle ni complexité inutile.
                        </p>
                    </div>
                    <div class="rounded-full border border-slate-200 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur">
                        Pensé pour l’administration, les enseignants et les familles
                    </div>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($features as $feature)
                        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-[0_24px_60px_-42px_rgba(15,23,42,0.24)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_30px_80px_-46px_rgba(15,23,42,0.3)]">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-800">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold tracking-tight text-slate-950">{{ $feature['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="application" class="scroll-mt-24 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-slate-950 px-6 py-8 text-white shadow-[0_30px_90px_-50px_rgba(15,23,42,0.75)] sm:px-8 sm:py-10 lg:px-12">
                    <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-sky-400/25 blur-3xl" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -bottom-28 left-10 h-72 w-72 rounded-full bg-teal-300/20 blur-3xl" aria-hidden="true"></div>

                    <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-center">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-200">Application mobile</p>
                            <h2 class="mt-4 font-serif text-3xl tracking-tight sm:text-4xl">
                                Téléchargez notre application mobile
                            </h2>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-white/75">
                                Accédez facilement à votre espace scolaire depuis votre téléphone : absences, notes, devoirs, paiements et notifications.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                <a href="{{ url('/download/myedu.apk') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 shadow-[0_22px_45px_-28px_rgba(255,255,255,0.6)] transition hover:bg-slate-100">
                                    Télécharger l'application
                                </a>
                                <p class="text-sm font-medium text-white/65">Disponible pour Android · Installation simple et rapide</p>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                            <div class="mx-auto max-w-56 rounded-[2rem] border border-white/20 bg-slate-900 p-3 shadow-2xl">
                                <div class="rounded-[1.5rem] bg-white p-4 text-slate-950">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-950 text-xs font-semibold tracking-[0.16em] text-white">
                                            ME
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold">MyEdu</p>
                                            <p class="text-xs text-slate-500">Portail scolaire</p>
                                        </div>
                                    </div>
                                    <div class="mt-5 space-y-3">
                                        <div class="rounded-2xl bg-sky-50 px-4 py-3">
                                            <p class="text-xs font-semibold text-sky-800">Notifications</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">Suivi en temps réel</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl bg-slate-50 p-3">
                                                <p class="text-xs text-slate-500">Notes</p>
                                                <p class="text-lg font-semibold">18/20</p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 p-3">
                                                <p class="text-xs text-slate-500">Absences</p>
                                                <p class="text-lg font-semibold">0</p>
                                            </div>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100">
                                            <div class="h-2 w-3/4 rounded-full bg-slate-950"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="landing-panel-light rounded-[2rem] p-8 sm:p-10">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-800">Indicateurs de confiance</p>
                        <h2 class="mt-4 font-serif text-3xl tracking-tight text-slate-950">Des repères sobres pour rassurer dès le premier écran.</h2>
                    </div>
                    <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($stats as $stat)
                            <div class="rounded-3xl bg-slate-50 px-6 py-6">
                                <p class="text-3xl font-semibold tracking-tight text-slate-950">{{ $stat['value'] }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $stat['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="scroll-mt-24 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="landing-panel-light overflow-hidden rounded-[2rem]">
                    <div class="grid gap-0 lg:grid-cols-[0.95fr_1.05fr]">
                        <div class="relative p-8 text-slate-900 sm:p-10 lg:p-12">
                            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(186,230,253,0.42),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(224,231,255,0.32),transparent_24%)]"></div>
                            <div class="relative">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-200">Prendre contact</p>
                                <h2 class="mt-4 font-serif text-3xl tracking-tight sm:text-4xl">Échangeons sur votre établissement et vos besoins.</h2>
                                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                                    Invitez vos visiteurs à se connecter, demander des informations ou préparer une présentation de la plateforme dans un cadre simple et professionnel.
                                </p>

                                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                    @if (Route::has('login'))
                                        <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                                            Se connecter
                                        </a>
                                    @endif
                                    <a href="#presentation" class="inline-flex min-h-12 items-center justify-center rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950">
                                        Découvrir l’école
                                    </a>
                                </div>

                                <div class="mt-10 space-y-4">
                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                                        <p class="text-sm font-semibold">Accueil administratif</p>
                                        <p class="mt-1 text-sm leading-6 text-white/65">Un point de contact clair pour les familles, les équipes et les nouveaux visiteurs.</p>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                                        <p class="text-sm font-semibold">Demande d’informations</p>
                                        <p class="mt-1 text-sm leading-6 text-white/65">Le formulaire ci-contre reste connecté à la route existante de l’application.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-8 sm:p-10 lg:p-12">
                            <div class="max-w-xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-800">Formulaire</p>
                                <h3 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950">Demander des informations</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Renseignez vos coordonnées et votre besoin. Le message est transmis via le flux déjà prévu par l’application.
                                </p>

                                @if (session('success'))
                                    <div class="app-alert app-alert-success mt-6">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if (($errors ?? null) && $errors->has('contact'))
                                    <div class="app-alert app-alert-error mt-6">
                                        {{ $errors->first('contact') }}
                                    </div>
                                @endif

                                <form action="{{ route('contact.send') }}" method="POST" class="mt-8 space-y-5">
                                    @csrf

                                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="app-field">
                                            <label for="name" class="app-label">Nom complet</label>
                                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="app-input" placeholder="Votre nom complet">
                                            @error('name')
                                                <p class="app-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="app-field">
                                            <label for="email" class="app-label">Email</label>
                                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="app-input" placeholder="vous@exemple.com">
                                            @error('email')
                                                <p class="app-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="app-field">
                                            <label for="phone" class="app-label">Téléphone</label>
                                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="app-input" placeholder="+212 ...">
                                            @error('phone')
                                                <p class="app-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="app-field">
                                            <label for="subject" class="app-label">Objet</label>
                                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="app-input" placeholder="Objet de votre demande">
                                            @error('subject')
                                                <p class="app-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="app-field">
                                        <label for="message" class="app-label">Message</label>
                                        <textarea id="message" name="message" rows="5" class="app-input" placeholder="Présentez votre établissement ou votre besoin.">{{ old('message') }}</textarea>
                                        @error('message')
                                            <p class="app-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        Envoyer la demande
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer border-t border-white/10 bg-transparent">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-6 text-sm text-slate-400 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <p>© {{ date('Y') }} {{ config('app.name', 'MyEdu') }}. Tous droits réservés.</p>
            <div class="flex flex-wrap items-center gap-5">
                <a href="#presentation" class="transition hover:text-slate-950">Présentation</a>
                <a href="#plateforme" class="transition hover:text-slate-950">Modules</a>
                <a href="#application" class="transition hover:text-slate-950">Application</a>
                <a href="#contact" class="transition hover:text-slate-950">Contact</a>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="font-semibold text-slate-700 transition hover:text-slate-950">Connexion</a>
                @endif
            </div>
        </div>
    </footer>
    </div>
</div>
</body>
</html>

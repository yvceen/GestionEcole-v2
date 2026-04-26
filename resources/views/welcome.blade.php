<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MyEdu') }} | Plateforme de gestion scolaire</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="fut-landing-body antialiased">
@php
    $trustCards = [
        ['title' => 'Gestion administrative', 'description' => 'Organisation des classes, dossiers, inscriptions et suivi quotidien dans un espace plus lisible.', 'icon' => 'admin'],
        ['title' => 'Suivi des eleves', 'description' => 'Vision claire des parcours, des observations, des resultats et des informations utiles a l accompagnement.', 'icon' => 'students'],
        ['title' => 'Communication parents-ecole', 'description' => 'Messages, informations et rendez-vous partages dans un cadre fluide et professionnel.', 'icon' => 'chat'],
        ['title' => 'Presences et devoirs', 'description' => 'Pointages, retards, absences et travaux scolaires rassembles pour une meilleure coordination.', 'icon' => 'attendance'],
        ['title' => 'Transport scolaire', 'description' => 'Circuits, affectations et informations pratiques accessibles dans un suivi mieux structure.', 'icon' => 'transport'],
        ['title' => 'Application mobile', 'description' => 'Acces dedie pour les familles, les eleves et les equipes depuis un usage mobile clair et rassurant.', 'icon' => 'mobile'],
    ];

    $modules = [
        ['title' => 'Eleves', 'description' => 'Dossiers, affectations, parcours et suivi global.', 'icon' => 'students'],
        ['title' => 'Parents', 'description' => 'Acces aux informations essentielles et aux echanges utiles.', 'icon' => 'parents'],
        ['title' => 'Enseignants', 'description' => 'Cours, devoirs, presences et evaluation dans le meme espace.', 'icon' => 'teachers'],
        ['title' => 'Direction', 'description' => 'Vue d ensemble sur l organisation et les priorites de l etablissement.', 'icon' => 'direction'],
        ['title' => 'Finance', 'description' => 'Paiements, suivis et rappels presentes avec plus de clarte.', 'icon' => 'finance'],
        ['title' => 'Notifications', 'description' => 'Informations importantes diffusees au bon moment.', 'icon' => 'notifications'],
        ['title' => 'Documents', 'description' => 'Partage des pieces utiles selon les profils concernes.', 'icon' => 'documents'],
        ['title' => 'Agenda', 'description' => 'Evenements, rendez-vous et rythme scolaire dans une lecture unifiee.', 'icon' => 'agenda'],
    ];

    $proofs = [
        ['value' => 'Une seule plateforme', 'label' => 'pour relier l administration, les equipes et les familles'],
        ['value' => 'Des espaces dedies', 'label' => 'pour chaque profil de la communaute scolaire'],
        ['value' => 'Une experience mobile', 'label' => 'pour consulter l essentiel en toute fluidite'],
    ];
@endphp
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

<div class="fut-landing">
    <div class="fut-landing-noise" aria-hidden="true"></div>
    <div class="fut-landing-orb fut-landing-orb-a" aria-hidden="true"></div>
    <div class="fut-landing-orb fut-landing-orb-b" aria-hidden="true"></div>
    <div class="fut-landing-orb fut-landing-orb-c" aria-hidden="true"></div>

    <header x-data="{ open: false }" class="fut-nav-wrap">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex min-h-[5.25rem] items-center gap-4">
                <div class="fut-brand-mark">ME</div>
                <div>
                    <p class="text-base font-semibold tracking-[0.12em] text-white">{{ config('app.name', 'MyEdu') }}</p>
                    <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Plateforme scolaire nouvelle generation</p>
                </div>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-medium text-slate-300 lg:flex">
                <a href="#valeurs" class="transition hover:text-white">Valeurs</a>
                <a href="#modules" class="transition hover:text-white">Modules</a>
                <a href="#mobile" class="transition hover:text-white">Application mobile</a>
                <a href="#organisation" class="transition hover:text-white">Organisation</a>
                <a href="#demo" class="transition hover:text-white">Demonstration</a>
            </nav>

            <div class="hidden items-center gap-4 lg:flex">
                <a href="#demo" class="fut-button fut-button-primary">Demander une demonstration</a>
            </div>

            <button @click="open = ! open" type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-100 backdrop-blur lg:hidden" aria-label="Ouvrir le menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
                </svg>
            </button>
        </div>

        <div :class="{ 'block': open, 'hidden': !open }" class="hidden border-t border-white/10 bg-slate-950/95 backdrop-blur lg:hidden">
            <div class="mx-auto max-w-7xl space-y-1 px-4 py-4 text-sm text-slate-200 sm:px-6">
                <a href="#valeurs" class="block rounded-2xl px-4 py-3 transition hover:bg-white/5">Valeurs</a>
                <a href="#modules" class="block rounded-2xl px-4 py-3 transition hover:bg-white/5">Modules</a>
                <a href="#mobile" class="block rounded-2xl px-4 py-3 transition hover:bg-white/5">Application mobile</a>
                <a href="#organisation" class="block rounded-2xl px-4 py-3 transition hover:bg-white/5">Organisation</a>
                <a href="#demo" class="block rounded-2xl px-4 py-3 transition hover:bg-white/5">Demonstration</a>
            </div>
        </div>
    </header>

    <main class="fut-main-flow">
        <section class="relative">
            <div class="mx-auto grid max-w-7xl gap-20 px-4 pb-28 pt-20 sm:px-6 md:gap-24 md:pb-32 md:pt-24 lg:grid-cols-[1.02fr_0.98fr] lg:px-8 lg:gap-28 lg:pb-40 lg:pt-32">
                <div class="max-w-3xl">
                    <span class="fut-eyebrow">Pilotage scolaire nouvelle generation</span>

                    <h1 class="mt-9 text-4xl font-semibold leading-[1.02] tracking-[-0.04em] text-white sm:text-5xl lg:text-7xl">
                        Une experience scolaire premium pour piloter l etablissement avec clarte, rythme et confiance.
                    </h1>

                    <p class="mt-8 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl">
                        {{ config('app.name', 'MyEdu') }} rassemble l organisation, le suivi pedagogique, les echanges et les operations du quotidien dans un environnement plus lisible, plus elegant et mieux coordonne.
                    </p>

                    <div class="mt-12 flex flex-col gap-4 sm:flex-row">
                        <a href="#demo" class="fut-button fut-button-primary">Demander une demonstration</a>
                        <a href="#modules" class="fut-button fut-button-secondary">Explorer les modules</a>
                    </div>

                    <div class="mt-16 grid gap-4 sm:grid-cols-3">
                        @foreach($proofs as $proof)
                            <div class="fut-mini-panel">
                                <p class="text-sm font-semibold text-white">{{ $proof['value'] }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">{{ $proof['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative">
                    <div class="fut-floating-chip fut-floating-chip-a">Pilotage intelligent</div>
                    <div class="fut-floating-chip fut-floating-chip-b">Communication fluide</div>

                    <div class="fut-hero-mockup">
                        <div class="fut-hero-mockup-grid"></div>

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200/80">Tableau de pilotage</p>
                                <h2 class="mt-3 text-2xl font-semibold text-white">Une lecture instantanee des operations de l etablissement</h2>
                            </div>
                            <div class="fut-status-pill">Temps reel</div>
                        </div>

                        <div class="mt-8 grid gap-4 sm:grid-cols-[1.2fr_0.8fr]">
                            <div class="fut-glass-card">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Vue generale</p>
                                        <p class="mt-2 text-xl font-semibold text-white">Structure, presences, finance, coordination</p>
                                    </div>
                                    <div class="fut-icon-wrap">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 17l5-5 3 3 8-8" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                    <div class="fut-metric-panel">
                                        <span class="fut-metric-label">Eleves</span>
                                        <span class="fut-metric-value">Vision unifiee</span>
                                    </div>
                                    <div class="fut-metric-panel">
                                        <span class="fut-metric-label">Presences</span>
                                        <span class="fut-metric-value">Suivi direct</span>
                                    </div>
                                    <div class="fut-metric-panel">
                                        <span class="fut-metric-label">Rendez-vous</span>
                                        <span class="fut-metric-value">Actions claires</span>
                                    </div>
                                </div>

                                <div class="mt-6 rounded-[24px] border border-white/10 bg-slate-950/55 p-4">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-white">Activite du jour</p>
                                        <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">Stable</span>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="fut-bar-row">
                                            <span>Administration</span>
                                            <div class="fut-bar-track"><div class="fut-bar-fill w-[78%]"></div></div>
                                        </div>
                                        <div class="fut-bar-row">
                                            <span>Classes</span>
                                            <div class="fut-bar-track"><div class="fut-bar-fill w-[64%]"></div></div>
                                        </div>
                                        <div class="fut-bar-row">
                                            <span>Familles</span>
                                            <div class="fut-bar-track"><div class="fut-bar-fill w-[86%]"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="fut-glass-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Experience</p>
                                    <p class="mt-3 text-lg font-semibold text-white">Navigation claire pour chaque profil</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-400">Chaque espace met en avant les informations utiles au bon moment.</p>
                                </div>

                                <div class="fut-glass-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Coordination</p>
                                    <div class="mt-4 space-y-3">
                                        <div class="fut-line-item"><span class="fut-line-dot bg-cyan-300"></span><span>Suivi administratif structure</span></div>
                                        <div class="fut-line-item"><span class="fut-line-dot bg-violet-300"></span><span>Vie scolaire mieux synchronisee</span></div>
                                        <div class="fut-line-item"><span class="fut-line-dot bg-amber-300"></span><span>Communication mieux organisee</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="valeurs" class="fut-section-wrap">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="fut-section-label">Valeur apportee</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-white sm:text-5xl">
                        Une plateforme qui structure les operations et renforce la relation avec toute la communaute scolaire.
                    </h2>
                    <p class="mt-5 text-base leading-8 text-slate-300">
                        Chaque module a ete pense pour rendre la gestion plus sereine, fluidifier le suivi et offrir une image plus moderne de l etablissement.
                    </p>
                </div>

                <div class="mt-14 grid gap-5 md:gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($trustCards as $card)
                        <article class="fut-feature-card">
                            <div class="fut-feature-icon">
                                @switch($card['icon'])
                                    @case('admin')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><rect x="4" y="5" width="16" height="14" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 13h5" /></svg>
                                        @break
                                    @case('students')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 19a3 3 0 0 0-3-2.8M17 10.5a2.5 2.5 0 1 0-1.1-4.8M5 19a3 3 0 0 1 3-2.8M7 10.5A2.5 2.5 0 1 1 8.1 5.7" /></svg>
                                        @break
                                    @case('chat')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v9H8l-4 4V6Z" /></svg>
                                        @break
                                    @case('attendance')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><circle cx="12" cy="12" r="8" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5" /></svg>
                                        @break
                                    @case('transport')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16V9a2 2 0 0 1 2-2h9l5 4v5" /><path stroke-linecap="round" stroke-linejoin="round" d="M7 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" /></svg>
                                        @break
                                    @default
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><rect x="7" y="3.5" width="10" height="17" rx="2.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 7h4M10 11h4M10 15h4" /></svg>
                                @endswitch
                            </div>
                            <h3 class="mt-6 text-xl font-semibold text-white">{{ $card['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-300">{{ $card['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="modules" class="fut-section-wrap">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="fut-section-label">Modules de la plateforme</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-white sm:text-5xl">
                            Les espaces essentiels pour une gestion scolaire moderne et sereine.
                        </h2>
                    </div>
                    <div class="fut-side-note">Concu pour accompagner l administration, les enseignants, la direction, les parents et les eleves.</div>
                </div>

                <div class="mt-14 grid gap-5 md:gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($modules as $module)
                        <article class="fut-module-card">
                            <div class="flex items-center justify-between gap-3">
                                <div class="fut-module-icon">
                                    @switch($module['icon'])
                                        @case('parents')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19a7 7 0 0 1 14 0" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" /></svg>
                                            @break
                                        @case('teachers')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 8l8 4 8-4-8-4Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 11.5V15c0 1.66 1.79 3 4 3s4-1.34 4-3v-3.5" /></svg>
                                            @break
                                        @case('direction')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 18h16M7 18V8m5 10V5m5 13v-7" /></svg>
                                            @break
                                        @case('finance')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M7 7.5c0-1.38 2.24-2.5 5-2.5s5 1.12 5 2.5-2.24 2.5-5 2.5-5 1.12-5 2.5 2.24 2.5 5 2.5 5 1.12 5 2.5-2.24 2.5-5 2.5-5-1.12-5-2.5" /></svg>
                                            @break
                                        @case('notifications')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a3 3 0 0 0 6 0" /></svg>
                                            @break
                                        @case('documents')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h6l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7.5 3.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13 3.5V8h4.5M9 12h6M9 16h6" /></svg>
                                            @break
                                        @case('agenda')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v4M16 3v4M4 10h16" /></svg>
                                            @break
                                        @default
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" /></svg>
                                    @endswitch
                                </div>
                                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Module</span>
                            </div>
                            <h3 class="mt-6 text-xl font-semibold text-white">{{ $module['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-300">{{ $module['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="mobile" class="fut-section-wrap">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="fut-mobile-showcase">
                        <div class="fut-mobile-copy">
                        <p class="fut-section-label">Application mobile</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-white sm:text-5xl">
                            Un prolongement mobile sobre, clair et rassurant pour toute la communaute scolaire.
                        </h2>
                        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300">
                            Les familles, les eleves et les equipes retrouvent les reperes essentiels, les alertes utiles et les informations de la journee depuis une interface mobile harmonisee avec l etablissement.
                        </p>

                        <div class="mt-10 grid gap-4 sm:grid-cols-3">
                            <div class="fut-mini-panel"><p class="text-sm font-semibold text-white">Parents</p><p class="mt-2 text-sm leading-6 text-slate-400">Suivi des enfants, informations utiles et echanges essentiels.</p></div>
                            <div class="fut-mini-panel"><p class="text-sm font-semibold text-white">Eleves</p><p class="mt-2 text-sm leading-6 text-slate-400">Agenda, cours, devoirs et repere quotidien toujours accessibles.</p></div>
                            <div class="fut-mini-panel"><p class="text-sm font-semibold text-white">Equipes</p><p class="mt-2 text-sm leading-6 text-slate-400">Consultation rapide pour agir avec plus de fluidite sur le terrain.</p></div>
                        </div>

                        <div class="mt-10 flex flex-wrap items-center gap-3">
                            <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100">Acces parent</span>
                            <span class="rounded-full border border-violet-400/20 bg-violet-400/10 px-4 py-2 text-sm font-semibold text-violet-100">Acces eleve</span>
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-sm font-semibold text-emerald-100">Acces equipe</span>
                        </div>

                        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="#demo" class="fut-button fut-button-primary">Demander une demonstration</a>
                            <span class="text-sm font-medium text-slate-400">Presentation adaptee a votre organisation, vos usages et vos priorites.</span>
                        </div>
                    </div>

                    <div class="relative flex items-center justify-center">
                        <div class="fut-phone-shell">
                            <div class="fut-phone-notch"></div>
                            <div class="fut-phone-screen">
                                <div class="fut-phone-head">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/70">MyEdu mobile</p>
                                        <p class="mt-2 text-lg font-semibold text-white">Votre espace scolaire, partout</p>
                                    </div>
                                    <div class="h-9 w-9 rounded-2xl bg-white/10"></div>
                                </div>

                                <div class="mt-5 rounded-[24px] border border-cyan-400/20 bg-cyan-400/8 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/80">Accueil mobile</p>
                                    <p class="mt-2 text-sm font-semibold text-white">Informations prioritaires et raccourcis utiles</p>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="fut-phone-tile"><span class="fut-phone-kicker">Presences</span><span class="fut-phone-value">Suivi rapide</span></div>
                                    <div class="fut-phone-tile"><span class="fut-phone-kicker">Messages</span><span class="fut-phone-value">Communication claire</span></div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div class="fut-phone-row"><span>Agenda</span><span class="text-cyan-200">Organise</span></div>
                                    <div class="fut-phone-row"><span>Documents</span><span class="text-violet-200">Disponibles</span></div>
                                    <div class="fut-phone-row"><span>Notifications</span><span class="text-emerald-200">Actives</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="organisation" class="fut-section-wrap">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-8 lg:gap-10 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="fut-info-panel">
                        <p class="fut-section-label">Organisation et confiance</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-white sm:text-5xl">
                            Une plateforme rassurante, claire et adaptee au rythme de l etablissement.
                        </h2>
                        <p class="mt-5 text-base leading-8 text-slate-300">
                            MyEdu aide les equipes a mieux s organiser, a partager les bonnes informations et a proposer une experience plus coherente a l ensemble de la communaute scolaire.
                        </p>
                        <div class="mt-8 grid gap-3 sm:grid-cols-2">
                            <div class="fut-mini-panel">
                                <p class="text-sm font-semibold text-white">Lecture immediate</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">Les informations utiles restent visibles, structurees et faciles a transmettre.</p>
                            </div>
                            <div class="fut-mini-panel">
                                <p class="text-sm font-semibold text-white">Coordination apaisee</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">Les services avancent avec des repères communs et une communication mieux synchronisee.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 md:gap-6 md:grid-cols-2">
                        <article class="fut-trust-panel"><h3 class="text-lg font-semibold text-white">Confidentialite respectee</h3><p class="mt-3 text-sm leading-7 text-slate-300">Les informations sont presentees avec sobriete et reservees aux personnes concernees selon leur espace.</p></article>
                        <article class="fut-trust-panel"><h3 class="text-lg font-semibold text-white">Organisation plus fluide</h3><p class="mt-3 text-sm leading-7 text-slate-300">Les services de l etablissement gagnent en lisibilite et en coordination au quotidien.</p></article>
                        <article class="fut-trust-panel"><h3 class="text-lg font-semibold text-white">Communication mieux structuree</h3><p class="mt-3 text-sm leading-7 text-slate-300">Les annonces, echanges et rappels sont mieux organises pour limiter les pertes d information.</p></article>
                        <article class="fut-trust-panel"><h3 class="text-lg font-semibold text-white">Image institutionnelle renforcee</h3><p class="mt-3 text-sm leading-7 text-slate-300">L etablissement affiche une presence numerique contemporaine, elegante et professionnelle.</p></article>
                    </div>
                </div>
            </div>
        </section>

        <section id="demo" class="pb-28 pt-16 sm:pt-18 lg:pb-32 lg:pt-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="fut-demo-panel">
                    <div class="grid gap-0 lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="p-8 sm:p-10 lg:p-12">
                            <p class="fut-section-label">Demonstration</p>
                            <h2 class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-white sm:text-5xl">
                                Demandez une demonstration adaptee a votre etablissement.
                            </h2>
                            <p class="mt-5 max-w-xl text-base leading-8 text-slate-300">
                                Echangeons sur vos besoins, votre organisation et les espaces a mettre en valeur pour vos equipes et vos familles.
                            </p>

                            <div class="mt-10 space-y-4">
                                <div class="fut-mini-panel"><p class="text-sm font-semibold text-white">Presentation professionnelle</p><p class="mt-2 text-sm leading-6 text-slate-400">Un parcours clair pour montrer comment la plateforme accompagne votre fonctionnement quotidien.</p></div>
                                <div class="fut-mini-panel"><p class="text-sm font-semibold text-white">Approche etablissement</p><p class="mt-2 text-sm leading-6 text-slate-400">Un discours adapte aux enjeux de gestion, de suivi et de relation avec les familles.</p></div>
                            </div>
                        </div>

                        <div class="border-t border-white/10 p-8 sm:p-10 lg:border-l lg:border-t-0 lg:p-12">
                            <div class="max-w-xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Formulaire de contact</p>
                                <h3 class="mt-4 text-2xl font-semibold text-white">Demander une demonstration</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-400">
                                    Renseignez vos coordonnees et votre besoin. Nous vous recontacterons dans un cadre professionnel.
                                </p>

                                @if (session('success'))
                                    <div class="mt-6 rounded-3xl border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">{{ session('success') }}</div>
                                @endif

                                @if (($errors ?? null) && $errors->has('contact'))
                                    <div class="mt-6 rounded-3xl border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">{{ $errors->first('contact') }}</div>
                                @endif

                                <form action="{{ route('contact.send') }}" method="POST" class="mt-10 space-y-5">
                                    @csrf
                                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div>
                                            <label for="name" class="fut-label">Nom complet</label>
                                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="fut-input" placeholder="Votre nom complet">
                                            @error('name')<p class="app-error mt-2">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="email" class="fut-label">Email</label>
                                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="fut-input" placeholder="vous@etablissement.com">
                                            @error('email')<p class="app-error mt-2">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div>
                                            <label for="phone" class="fut-label">Telephone</label>
                                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="fut-input" placeholder="+212 ...">
                                            @error('phone')<p class="app-error mt-2">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="subject" class="fut-label">Objet</label>
                                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="fut-input" placeholder="Demande de demonstration">
                                            @error('subject')<p class="app-error mt-2">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label for="message" class="fut-label">Message</label>
                                        <textarea id="message" name="message" rows="5" class="fut-input fut-textarea" placeholder="Presentez votre etablissement et vos attentes.">{{ old('message') }}</textarea>
                                        @error('message')<p class="app-error mt-2">{{ $message }}</p>@enderror
                                    </div>

                                    <button type="submit" class="fut-button fut-button-primary w-full justify-center">Envoyer la demande</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-7 text-sm text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <p>© {{ date('Y') }} {{ config('app.name', 'MyEdu') }}. Tous droits reserves.</p>
            <div class="flex flex-wrap items-center gap-5">
                <a href="#valeurs" class="transition hover:text-white">Valeurs</a>
                <a href="#modules" class="transition hover:text-white">Modules</a>
                <a href="#mobile" class="transition hover:text-white">Application mobile</a>
                <a href="#demo" class="transition hover:text-white">Demonstration</a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>

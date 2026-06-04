<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'My Edu') }} | L'éducation connectée, simplement</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-school-favicons />
    <style>
        .fut-logo-marquee{position:relative;z-index:1;width:100%;overflow:hidden;margin-top:3rem;padding:1.25rem 0 2rem;mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent)}
        .fut-logo-marquee:hover .fut-logo-track{animation-play-state:paused}
        .fut-logo-track{display:flex;width:max-content;gap:1rem;animation:myeduLogoMarquee 34s linear infinite}
        .fut-logo-card{display:grid;min-width:15rem;min-height:6.5rem;grid-template-columns:4rem minmax(0,1fr);align-items:center;gap:1rem;border-radius:1.6rem;border:1px solid rgba(226,232,240,.86);background:rgba(255,255,255,.82);padding:1rem 1.1rem;box-shadow:0 24px 58px -46px rgba(15,23,42,.42);transition:transform .28s ease,box-shadow .28s ease,border-color .28s ease}
        .fut-logo-card:hover{transform:translateY(-4px) scale(1.015);border-color:rgba(147,197,253,.8);box-shadow:0 32px 72px -44px rgba(37,99,235,.34)}
        .fut-school-logo{display:grid;width:4rem;height:4rem;place-items:center;overflow:hidden;background:transparent}
        .fut-school-logo img{display:block;width:100%;height:100%;object-fit:contain;background:transparent}
        .fut-school-logo-fallback{display:grid;width:3.5rem;height:3.5rem;place-items:center;border-radius:1rem;background:#f0f9ff;color:#0369a1;font-size:.75rem;font-weight:900;letter-spacing:.08em}
        .fut-logo-card strong{overflow-wrap:anywhere;font-size:.98rem;color:#0f172a}
        .fut-logo-empty{margin:2rem auto 0;max-width:32rem;border:1px dashed #cbd5e1;border-radius:1.5rem;background:rgba(255,255,255,.72);padding:1.5rem;text-align:center;color:#64748b}
        @keyframes myeduLogoMarquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
        @media(max-width:767px){.fut-logo-track{animation-duration:40s}.fut-logo-card{min-width:13rem;min-height:5.75rem}}
    </style>
</head>
<body class="fut-landing-body antialiased">
@php
    $values = [
        ['title' => 'Communication', 'copy' => "Des Échanges plus clairs entre l'École, les familles et les Équipes éducatives.", 'tone' => 'cyan'],
        ['title' => 'Organisation', 'copy' => 'Un quotidien plus fluide, avec les informations importantes toujours au bon endroit.', 'tone' => 'indigo'],
        ['title' => 'Suivi', 'copy' => 'Une vision rassurante du parcours des Élèves, des activités et des temps forts.', 'tone' => 'emerald'],
        ['title' => 'Innovation', 'copy' => 'Une expérience numérique moderne, élégante et adaptée aux Établissements ambitieux.', 'tone' => 'amber'],
    ];

    $community = ['Direction', 'Enseignants', 'Parents', 'Élèves', 'Vie scolaire', 'Administration'];
    $partnerSchoolCarousel = $partnerSchools->concat($partnerSchools)->values();
    $partnerSchoolCount = $partnerSchools->count();

    $reasons = [
        'Interface élégante',
        'Accès sécurisé',
        'Suivi en temps réel',
        'Notifications instantanees',
        'Application mobile',
    ];

    $testimonials = [
        ['quote' => 'My Edu donne une image moderne a notre Établissement et simplifie la relation avec les familles.', 'name' => 'Direction pédagogique'],
        ['quote' => "Les parents comprennent mieux la vie de l'École, les informations arrivent avec plus de clarté.", 'name' => 'Responsable vie scolaire'],
        ['quote' => 'L expérience est fluide, rassurante et beaucoup plus proche des usages actuels des familles.', 'name' => 'Equipe administrative'],
    ];
@endphp
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

<div class="fut-landing">
    <div class="fut-ambient fut-ambient-a" aria-hidden="true"></div>
    <div class="fut-ambient fut-ambient-b" aria-hidden="true"></div>
    <div class="fut-ambient fut-ambient-c" aria-hidden="true"></div>
    <div class="fut-grain" aria-hidden="true"></div>

    <header x-data="{ open: false }" class="fut-nav">
        <div class="mx-auto max-w-7xl px-5 py-4 sm:px-6 lg:px-8">
            <div class="fut-nav-shell">
            <a href="{{ url('/') }}" class="fut-brand-link">
                <span class="fut-mark fut-mark-nav">
                    <img src="{{ asset('images/myedu-mark-transparent.png') }}" alt="My Edu">
                </span>
                <span class="leading-tight">
                    <span class="block text-base font-semibold text-slate-950">My Edu</span>
                    <span class="block text-[0.66rem] font-bold uppercase tracking-[0.28em] text-teal-700">Education premium</span>
                </span>
            </a>

            <nav class="fut-nav-links">
                <a href="#about">My Edu</a>
                <a href="#partners">Ecoles</a>
                <a href="#values">Valeurs</a>
                <a href="#community">Communaute</a>
                <a href="#mobile">Mobile</a>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                <a href="#demo" class="fut-nav-cta">
                    <span>Contact</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </a>
            </div>

            <button @click="open = ! open" type="button" class="fut-nav-toggle" aria-label="Ouvrir le menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path :class="{ 'hidden': open }" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    <path :class="{ 'hidden': !open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
                </svg>
            </button>
            </div>
        </div>

        <div :class="{ 'block': open, 'hidden': !open }" class="hidden px-5 pb-4 lg:hidden">
            <div class="fut-mobile-menu">
                <a href="#about">My Edu</a>
                <a href="#partners">Ecoles partenaires</a>
                <a href="#values">Valeurs</a>
                <a href="#community">Communaute</a>
                <a href="#mobile">Mobile</a>
                <a href="#demo">Contact</a>
            </div>
        </div>
    </header>

    <main>
        <section class="fut-hero">
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-24 pt-16 sm:px-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8 lg:pb-32 lg:pt-24">
                <div class="fut-hero-copy">
                    <p class="fut-eyebrow">Brand éducative premium</p>
                    <h1 class="mt-8 text-5xl font-semibold leading-[0.95] tracking-[-0.04em] text-slate-950 sm:text-6xl lg:text-7xl">
                        My Edu
                        <span class="mt-3 block text-transparent bg-clip-text bg-gradient-to-r from-sky-600 via-indigo-600 to-slate-950">L'éducation connectée, simplement.</span>
                    </h1>
                    <p class="mt-8 max-w-2xl text-lg leading-8 text-slate-600 sm:text-xl">
                        Une expérience moderne qui rapproche l'École, les parents, les enseignants et les Élèves dans un environnement numérique intuitif.
                    </p>
                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        <a href="#about" class="fut-button fut-button-dark">Decouvrir la plateforme</a>
                    </div>
                </div>

                <div class="fut-hero-visual fut-float-stage">
                    <img src="{{ asset('images/myedu-premium-education-hero.png') }}" alt="Expérience éducative moderne My Edu" class="h-full w-full object-cover">
                    <div class="fut-hero-card fut-hero-card-a">
                        <span>Communication</span>
                        <strong>École & familles</strong>
                    </div>
                    <div class="fut-hero-card fut-hero-card-b">
                        <span>Expérience mobile</span>
                        <strong>Connectee partout</strong>
                    </div>
                </div>
            </div>
        </section>

        <section id="partners" class="fut-section fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-section-heading">
                    <p class="fut-section-label">Ecoles partenaires</p>
                    <h2>Des ecoles qui avancent avec My Edu.</h2>
                    <p>Une communauté éducative connectée, moderne et tournée vers l avenir.</p>
                </div>
            </div>

            <?php if ($partnerSchoolCount > 0): ?>
                <div class="fut-logo-marquee" aria-label="Ecoles partenaires">
                    <div class="fut-logo-track">
                        <?php foreach ($partnerSchoolCarousel as $index => $school): ?>
                            <?php
                                $logoPath = is_string($school->logo_path) ? ltrim($school->logo_path, '/') : '';
                                $logoUrl = $logoPath !== '' ? asset('storage/' . $logoPath) : null;
                                $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(\Illuminate\Support\Str::slug($school->name, ''), 0, 2));
                                $duplicate = $index >= $partnerSchoolCount;
                            ?>
                            <div class="fut-logo-card" <?= $duplicate ? 'aria-hidden="true"' : '' ?>>
                                <?php if ($logoUrl): ?>
                                    <span class="fut-school-logo">
                                        <img src="<?= e($logoUrl) ?>" alt="<?= e($duplicate ? '' : 'Logo ' . $school->name) ?>" loading="lazy">
                                    </span>
                                <?php else: ?>
                                    <span class="fut-school-logo-fallback"><?= e($initials ?: 'EC') ?></span>
                                <?php endif; ?>
                                <strong><?= e($school->name) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="fut-logo-empty">Les ecoles creees et activees par le Super Admin apparaitront automatiquement ici.</div>
            <?php endif; ?>
        </section>

        <section id="about" class="fut-section fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-about-panel">
                    <div>
                        <p class="fut-section-label">About My Edu</p>
                        <h2>Une nouvelle maniére de vivre l'École.</h2>
                    </div>
                    <p>
                        My Edu rassemble tous les acteurs de la communauté éducative autour d'un espace unique, moderne et accèssible, pensé pour renforcer la confiance, la communication et l'expérience scolaire.
                    </p>
                </div>
            </div>
        </section>

        <section id="values" class="fut-section fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-section-heading">
                    <p class="fut-section-label">Valeurs éducatives</p>
                    <h2>Une marque digitale au service de l'apprentissage et du lien.</h2>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($values as $value)
                        <article class="fut-value-card fut-tone-{{ $value['tone'] }}">
                            <span></span>
                            <h3>{{ $value['title'] }}</h3>
                            <p>{{ $value['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="community" class="fut-section fut-reveal">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
                <div class="fut-section-heading">
                    <p class="fut-section-label">Communaute connectée</p>
                    <h2>Toute l'École avance dans une même expérience.</h2>
                    <p>Direction, enseignants, parents, Élèves et administration retrouvent des repéres simples, une communication claire et une présence digitale plus moderne.</p>
                </div>
                <div class="fut-community-grid">
                    @foreach($community as $item)
                        <div class="fut-community-card">
                            <span>{{ substr($item, 0, 1) }}</span>
                            <strong>{{ $item }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="mobile" class="fut-section fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-mobile-panel">
                    <div>
                        <p class="fut-section-label">Mobile expérience</p>
                        <h2>Votre École partout avec vous.</h2>
                        <p>Recevez les informations importantes, suivez les activités et restez connecté a tout moment avec une expérience mobile claire, rassurante et élégante.</p>
                    </div>
                    <div class="fut-phone fut-float-stage">
                        <div class="fut-phone-screen">
                            <div class="fut-phone-top"></div>
                            <div class="fut-phone-message">Nouvelle information de l'École</div>
                            <div class="fut-phone-row"><span>Activités</span><strong>3</strong></div>
                            <div class="fut-phone-row"><span>Messages</span><strong>12</strong></div>
                            <div class="fut-phone-row"><span>Agenda</span><strong>Aujourd'hui</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="fut-section fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-why-panel">
                    <div class="fut-section-heading">
                        <p class="fut-section-label">Why My Edu</p>
                        <h2>Pensee pour les Établissements modernes.</h2>
                    </div>
                    <div class="fut-reason-list">
                        @foreach($reasons as $reason)
                            <div><span></span>{{ $reason }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="fut-section fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-section-heading">
                    <p class="fut-section-label">Temoignages</p>
                    <h2>Une expérience qui inspire confiance.</h2>
                </div>
                <div class="mt-12 grid gap-5 lg:grid-cols-3">
                    @foreach($testimonials as $testimonial)
                        <article class="fut-testimonial">
                            <p>"{{ $testimonial['quote'] }}"</p>
                            <strong>{{ $testimonial['name'] }}</strong>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="demo" class="fut-section fut-section-final fut-reveal">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="fut-cta-panel">
                    <div>
                        <p class="fut-section-label">Demonstration</p>
                        <h2>Pret a decouvrir My Edu ?</h2>
                        <p>Rejoignez une nouvelle expérience éducative.</p>
                    </div>
                    <form action="{{ route('contact.send') }}" method="POST" class="fut-contact">
                        @csrf
                        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                        @if (session('success'))
                            <div class="fut-form-success">{{ session('success') }}</div>
                        @endif
                        @if (($errors ?? null) && $errors->has('contact'))
                            <div class="fut-form-error">{{ $errors->first('contact') }}</div>
                        @endif
                        <input name="name" value="{{ old('name') }}" class="fut-input" placeholder="Nom complet">
                        <input name="email" type="email" value="{{ old('email') }}" class="fut-input" placeholder="Email professionnel">
                        <input name="phone" value="{{ old('phone') }}" class="fut-input" placeholder="Téléphone">
                        <input name="subject" value="{{ old('subject', 'Demande de demonstration My Edu') }}" class="fut-input" placeholder="Objet">
                        <textarea name="message" rows="4" class="fut-input fut-textarea" placeholder="Parlez-nous de votre Établissement.">{{ old('message') }}</textarea>
                        <button type="submit" class="fut-button fut-button-dark w-full">Demander une demonstration</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer class="fut-footer">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 sm:px-6 lg:grid-cols-[1fr_auto] lg:px-8">
            <div>
                <div class="flex items-center gap-3">
                    <span class="fut-mark">
                        <img src="{{ asset('images/myedu-mark-transparent.png') }}" alt="My Edu">
                    </span>
                    <strong>My Edu</strong>
                </div>
                <p class="mt-4 max-w-xl text-sm leading-6 text-slate-500">L'éducation connectée, simplement. Une expérience moderne pour rapprocher toute la communauté éducative.</p>
            </div>
            <div class="fut-footer-links">
                <a href="https://www.instagram.com/myedu.school" target="_blank" rel="noopener"><span>Instagram</span> myedu.school</a>
                <a href="https://www.facebook.com/my-edu" target="_blank" rel="noopener"><span>Facebook</span> my-edu</a>
                <a href="https://wa.me/212641612016" target="_blank" rel="noopener"><span>WhatsApp</span> 0641612016</a>
                <a href="mailto:yassine@myedu.school"><span>Email</span> yassine@myedu.school</a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>

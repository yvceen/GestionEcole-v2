@props([
    'target' => null,
    'label' => 'Générer un mot de passe',
    'copyLabel' => 'Copier',
    'helper' => 'Conservez ce mot de passe avant d\'enregistrer.',
    'confirmationTarget' => null,
])

@php
    $targetId = (string) $target;
    $confirmationTargetId = filled($confirmationTarget) ? (string) $confirmationTarget : '';
@endphp

@if($targetId !== '')
    <div
        class="mt-2 flex flex-wrap items-center gap-2"
        data-password-tools
        data-target="{{ $targetId }}"
        @if($confirmationTargetId !== '')
            data-confirmation-target="{{ $confirmationTargetId }}"
        @endif
    >
        <button type="button" class="app-button-secondary min-h-10 rounded-2xl px-4 py-2" data-password-generate>
            {{ $label }}
        </button>
        <button type="button" class="app-button-ghost min-h-10 rounded-2xl px-4 py-2" data-password-copy>
            {{ $copyLabel }}
        </button>
        <span class="text-xs font-semibold text-emerald-700" data-password-feedback aria-live="polite"></span>
    </div>

    @if(filled($helper))
        <p class="mt-2 text-xs text-slate-500">{{ $helper }}</p>
    @endif

    <script>
        (function () {
            if (window.__passwordToolsInit) {
                return;
            }

            window.__passwordToolsInit = true;

            const charsets = {
                upper: 'ABCDEFGHJKLMNPQRSTUVWXYZ',
                lower: 'abcdefghijkmnopqrstuvwxyz',
                number: '23456789',
                symbol: '@#$%',
            };

            const randomChar = (pool) => pool[Math.floor(Math.random() * pool.length)];
            const shuffle = (value) => value.split('').sort(() => Math.random() - 0.5).join('');

            const generatePassword = () => {
                const required = [
                    randomChar(charsets.upper),
                    randomChar(charsets.lower),
                    randomChar(charsets.number),
                    randomChar(charsets.symbol),
                ];

                const combined = charsets.upper + charsets.lower + charsets.number;
                while (required.length < 12) {
                    required.push(randomChar(combined));
                }

                return shuffle(required.join(''));
            };

            const copyText = async (value) => {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                    return true;
                }

                const helper = document.createElement('textarea');
                helper.value = value;
                helper.setAttribute('readonly', 'readonly');
                helper.style.position = 'fixed';
                helper.style.opacity = '0';
                document.body.appendChild(helper);
                helper.select();
                const copied = document.execCommand('copy');
                document.body.removeChild(helper);
                return copied;
            };

            document.querySelectorAll('[data-password-tools]').forEach((tool) => {
                const target = document.getElementById(tool.dataset.target);
                if (!target) {
                    return;
                }

                const confirmationTarget = tool.dataset.confirmationTarget
                    ? document.getElementById(tool.dataset.confirmationTarget)
                    : null;
                const feedback = tool.querySelector('[data-password-feedback]');
                let feedbackTimer = null;

                const showFeedback = (message, isError = false) => {
                    if (!feedback) {
                        return;
                    }

                    feedback.textContent = message;
                    feedback.classList.toggle('text-emerald-700', !isError);
                    feedback.classList.toggle('text-rose-700', isError);

                    window.clearTimeout(feedbackTimer);
                    feedbackTimer = window.setTimeout(() => {
                        feedback.textContent = '';
                    }, 2200);
                };

                tool.querySelector('[data-password-generate]')?.addEventListener('click', () => {
                    const password = generatePassword();
                    target.value = password;
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    target.dispatchEvent(new Event('change', { bubbles: true }));

                    if (confirmationTarget) {
                        confirmationTarget.value = password;
                        confirmationTarget.dispatchEvent(new Event('input', { bubbles: true }));
                        confirmationTarget.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    showFeedback('Mot de passe généré');
                });

                tool.querySelector('[data-password-copy]')?.addEventListener('click', async () => {
                    if (!target.value) {
                        showFeedback('Aucun mot de passe à copier', true);
                        return;
                    }

                    try {
                        await copyText(target.value);
                        showFeedback('Mot de passe copié');
                    } catch (error) {
                        showFeedback('Copie impossible', true);
                    }
                });
            });
        })();
    </script>
@endif

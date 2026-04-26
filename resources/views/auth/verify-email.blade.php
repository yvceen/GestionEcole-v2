<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Merci pour votre inscription. Avant de commencer, veuillez verifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer. Si vous ne l avez pas recu, vous pouvez demander un nouvel envoi.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Un nouveau lien de verification a ete envoye a l adresse e-mail fournie lors de l inscription.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Renvoyer l e-mail de verification
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Deconnexion
            </button>
        </form>
    </div>
</x-guest-layout>

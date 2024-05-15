<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <head>
        <style>
            .relative {
                position: relative;
            }
    
            .absolute {
                position: absolute;
                top: 50%;
                right: 10px; /* Adjust this value if necessary */
                transform: translateY(-50%);
            }
    
            .cursor-pointer {
                cursor: pointer;
            }
    
            .pr-10 {
                padding-right: 2.5rem; /* Ensure there is enough space for the icon */
            }

            .eye-icon {
                font-size: 12px; /* Adjust the size as needed */
            }
        </style>
    </head>
    

    <div class="text-center">
        <a href="/">
            <x-application-logo class="w-10 h-10 fill-current text-gray-500" />
        </a>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Emel Pengguna')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Kata Laluan')" />

            <div class="relative flex items-center">
                <x-text-input id="password" class="block mt-1 w-full pr-10"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                <span id="togglePassword" class="absolute right-3 cursor-pointer">
                    <i class="fa fa-eye-slash eye-icon"></i>
                </span>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                
        </div>

        <!-- Remember Me -->
        {{-- <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div> --}}

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-500 hover:text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Lupa Kata Laluan ?') }}
                </a>
            @endif
        </div>

        <div class="flex items-center justify-center mt-4">
            <x-primary-button class="ms-3">
                {{ __('Log Masuk') }}
            </x-primary-button>
        </div>

        <div class="flex items-center justify-center mt-2">
            <a class="underline text-sm text-gray-500 hover:text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('register') }}">
                {{ __('Belum mempunyai akaun? Daftar') }}
            </a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
        
            const togglePasswordConfirmation = document.querySelector('#togglePasswordConfirmation');
            const passwordConfirmation = document.querySelector('#password_confirmation');
        
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                if (type === 'text') {
                    this.querySelector('i').classList.remove('fa-eye-slash');
                    this.querySelector('i').classList.add('fa-eye');
                } else {
                    this.querySelector('i').classList.remove('fa-eye');
                    this.querySelector('i').classList.add('fa-eye-slash');
                }
            });
        });
    </script>        
</x-guest-layout>

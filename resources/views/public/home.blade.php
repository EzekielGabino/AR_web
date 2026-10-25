@extends('layouts.public')

@section('title', 'SciLens AR — Teacher Portal')

@section('content')
    @include('public.partials.nav')

    <main>
        @include('public.partials.hero')
        @include('public.partials.overview')
        @include('public.partials.login-form')
    </main>

    <footer class="border-t border-slate-200 bg-slate-100 py-8 text-center text-xs font-bold text-slate-500">
        <p>© {{ date('Y') }} SciLens AR. Manaoag National High School — STE Program Research Project.</p>
    </footer>
@endsection

@push('scripts')
    <script>
        window.__supabaseUrl = @json(rtrim((string) config('supabase.url'), '/'));
        window.__supabaseAnonKey = @json((string) config('supabase.anon_key'));
        window.__supabaseCallbackUrl = @json(route('auth.supabase.callback'));
    </script>
    <script type="module" src="{{ asset('js/public/google-signin.js') }}"></script>
@endpush

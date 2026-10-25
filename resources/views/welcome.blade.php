{{--
    Deprecated entry: routes now use public.home.
    Kept so old links don't 500 if something still references view('welcome').
--}}
@include('public.home')

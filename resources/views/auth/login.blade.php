@extends('layouts.master-without-nav')
@section('title')
    @lang('translation.signin')
@endsection
@section('content')
@php
    $selectedPlanCode = $selectedPlanCode ?? null;
    $selectedPlan = $selectedPlan ?? null;
@endphp
<div class="auth-page-wrapper py-4">
    <!-- auth page bg -->
    <div class="auth-one-bg-position auth-one-bg"  id="auth-particles">
        <div class="bg-overlay"></div>

        <div class="shape">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
            </svg>
        </div>
    </div>

    <!-- auth page content -->
    <div class="auth-page-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mt-4 mb-3 text-white-50">
                        <div>
                            <a href="{{ route('root') }}" class="d-inline-block auth-logo">
                                <img src="{{ URL::asset('images/logo-light.png')}}" alt="" height="65" width="225">
                            </a>
                        </div>
                        <p class="mt-3 fs-15 fw-medium">Destination Manager Apps</p>
                    </div>
                </div>
            </div>
            <!-- end row -->

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mt-3">

                        <div class="card-body p-3">
                            <div class="text-center mt-0">
                                <h5 class="text-primary">Selamat datang kembali</h5>
                                <p class="text-muted">Masuk untuk melanjutkan ke Desma Apps.</p>
                            </div>
                            <div class="p-2 mt-3">
                                <form action="{{ route('login') }}" method="POST">
                                    @csrf
                                    @if (!empty($selectedPlanCode))
                                        <input type="hidden" name="selected_plan_code" value="{{ $selectedPlanCode }}">
                                    @endif
                                    @if (isset($selectedPlan) && $selectedPlan)
                                        <div class="alert alert-info" role="alert">
                                            Kamu memilih paket <strong>{{ $selectedPlan->name }}</strong>.
                                            Lanjut login untuk proses aktivasi paket.
                                        </div>
                                    @endif
                                    <div class="mb-2">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" id="email" name="email" placeholder="nama@perusahaan.com" autocomplete="username" required>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-2">
                                        <div class="float-end">
                                            <a href="{{ route('password.request') }}" class="text-muted">Lupa password?</a>
                                        </div>
                                        <label class="form-label" for="password-input">Password <span class="text-danger">*</span></label>
                                        <div class="position-relative auth-pass-inputgroup mb-2">
                                            <input type="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" name="password" placeholder="Masukkan password" id="password-input" autocomplete="current-password" required>
                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="auth-remember-check" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auth-remember-check">Ingat saya</label>
                                    </div>

                                    <div class="mt-3">
                                        <button class="btn btn-success w-100" type="submit">Masuk</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->

                    <div class="mt-3 text-center">
                        <p class="mb-0">Belum punya akun? <a href="{{ route('register', !empty($selectedPlanCode) ? ['plan' => $selectedPlanCode] : []) }}" class="fw-semibold text-primary text-decoration-underline">Daftar</a></p>
                    </div>

                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end auth page content -->

    @include('layouts.landing-footer')
</div>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/particles.js/particles.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/particles.app.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/password-addon.init.js') }}"></script>

@endsection

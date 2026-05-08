@extends('layouts.master-without-nav')
@section('title')
    @lang('translation.reset-mail')
@endsection
@section('content')
    <div class="auth-page-wrapper py-4">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
                    viewBox="0 0 1440 120">
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
                                    <img src="{{ URL::asset('images/logo-light.png') }}" alt="" height="20">
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
                                    <h5 class="text-primary">Reset password</h5>
                                    <p class="text-muted">Buat password baru untuk akun kamu.</p>

                                    <lord-icon src="https://cdn.lordicon.com/rhvddzym.json" trigger="loop"
                                        colors="primary:#0ab39c" class="avatar-xl">
                                    </lord-icon>

                                </div>

                                <div class="alert border-0 alert-warning text-center mb-2 mx-2" role="alert">
                                    Masukkan password baru, lalu simpan.
                                </div>
                                <div class="p-2">
                                    <form class="form-horizontal" method="POST" action="{{ route('password.update') }}">
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $token }}">
                                        <div class="mb-2">
                                            <label for="useremail" class="form-label">Email</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="useremail" name="email" placeholder="nama@perusahaan.com" value="{{ $email ?? old('email') }}" id="email">
                                            @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                        <div class="mb-2">
                                            <label for="userpassword" class="form-label">Password baru</label>
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="userpassword" placeholder="Minimal 8 karakter" autocomplete="new-password">
                                            @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                        <div class="mb-2">
                                            <label for="password-confirm" class="form-label">Konfirmasi password baru</label>
                                            <input id="password-confirm" type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" autocomplete="new-password">
                                        </div>

                                        <div class="d-grid mt-2">
                                            <button class="btn btn-primary waves-effect waves-light" type="submit">Simpan password baru</button>
                                        </div>

                                    </form><!-- end form -->
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                        <div class="mt-3 text-center">
                            <p class="mb-0">Sudah ingat password? <a href="{{ route('login') }}"
                                    class="fw-semibold text-primary text-decoration-underline">Masuk</a></p>
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
    <!-- end auth-page-wrapper -->
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/particles.js/particles.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/particles.app.js') }}"></script>
@endsection

@extends('layouts.master-without-nav')
@section('title')
    Syarat & Ketentuan
@endsection
@section('body')
    <body>
    @endsection
@section('content')
    <div class="layout-wrapper landing">
        {{-- Navbar ringkas: pakai pola yang sama dengan landing.blade.php, hanya logo + tombol kembali. --}}
        <nav class="navbar navbar-expand-lg navbar-landing fixed-top" id="navbar">
            <div class="container">
                <a class="navbar-brand" href="{{ route('root') }}">
                    <img src="{{ URL::asset('images/logo-dark.png') }}" class="card-logo card-logo-dark" alt="logo dark" height="50" width="185">
                    <img src="{{ URL::asset('images/logo-light.png') }}" class="card-logo card-logo-light" alt="logo light" height="50" width="185">
                </a>
                <div class="ms-auto">
                    <a href="{{ route('root') }}" class="btn btn-primary">
                        <i class="ri-arrow-left-line align-middle me-1"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </nav>

        {{-- Spacer supaya konten tidak ketutup navbar fixed-top. --}}
        <section class="section pt-5 mt-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        {{-- Card Velzon yang sama dengan pages-term-conditions.blade.php, di-Indonesia-kan dan disesuaikan ke konteks platform. --}}
                        <div class="card">
                            <div class="bg-warning-subtle position-relative">
                                <div class="card-body p-5">
                                    <div class="text-center">
                                        <h3>Syarat &amp; Ketentuan</h3>
                                        <p class="mb-0 text-muted">Pembaruan terakhir: {{ \Carbon\Carbon::create(2026, 5, 1)->translatedFormat('d F Y') }}</p>
                                    </div>
                                </div>
                                <div class="shape">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
                                        xmlns:svgjs="http://svgjs.com/svgjs" width="1440" height="60" preserveAspectRatio="none"
                                        viewBox="0 0 1440 60">
                                        <g mask="url(&quot;#SvgjsMask1001&quot;)" fill="none">
                                            <path d="M 0,4 C 144,13 432,48 720,49 C 1008,50 1296,17 1440,9L1440 60L0 60z"
                                                style="fill: var(--vz-secondary-bg);"></path>
                                        </g>
                                        <defs>
                                            <mask id="SvgjsMask1001">
                                                <rect width="1440" height="60" fill="#ffffff"></rect>
                                            </mask>
                                        </defs>
                                    </svg>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div>
                                    <h5>Selamat datang di Desma!</h5>
                                    <p class="text-muted">Syarat dan ketentuan berikut mengatur penggunaan platform Desma oleh Anda dan tim Anda. Dengan mendaftar atau mengakses platform ini, Anda dianggap telah membaca, memahami, dan menyetujui ketentuan di bawah.</p>
                                    <p class="text-muted">Jika Anda tidak menyetujui salah satu ketentuan, mohon hentikan penggunaan platform. Kami dapat memperbarui ketentuan ini dari waktu ke waktu, dan setiap perubahan akan dipublikasikan pada halaman ini.</p>
                                </div>

                                <div>
                                    <h5>Akun &amp; Tenant</h5>
                                    <p class="text-muted">Desma adalah platform multi-tenant. Setiap operator tour dapat memiliki satu atau lebih tenant yang dikelola oleh administrator yang ditunjuk.</p>
                                    <ul class="text-muted vstack gap-2">
                                        <li>Anda bertanggung jawab menjaga kerahasiaan kredensial akun dan aktivitas yang terjadi pada akun Anda.</li>
                                        <li>Setiap tenant wajib menunjuk administrator yang berwenang menambah, menonaktifkan, dan mengatur peran pengguna.</li>
                                        <li>Data yang dimasukkan ke dalam tenant menjadi tanggung jawab tenant tersebut, termasuk akurasi data booking, pelanggan, dan kapasitas tour.</li>
                                    </ul>
                                </div>

                                <div>
                                    <h5>Penggunaan yang Diperbolehkan</h5>
                                    <p class="text-muted">Anda sepakat untuk menggunakan platform sesuai peruntukannya, yaitu mengelola operasional tour, booking, kapasitas, pelanggan, serta integrasi channel resmi (misalnya GetYourGuide).</p>
                                    <p class="text-muted">Anda tidak diperkenankan untuk:</p>
                                    <ul class="text-muted vstack gap-2">
                                        <li>Mereproduksi, menjual, atau mendistribusikan ulang materi platform tanpa izin tertulis.</li>
                                        <li>Mencoba mengakses data tenant lain, sumber daya internal, atau melakukan reverse engineering terhadap platform.</li>
                                        <li>Menggunakan platform untuk aktivitas yang melanggar hukum, menipu, atau merugikan pengguna lain.</li>
                                        <li>Mengirim konten otomatis dalam jumlah berlebihan yang dapat mengganggu performa platform.</li>
                                    </ul>
                                </div>

                                <div>
                                    <h5>Integrasi Channel &amp; Data Pihak Ketiga</h5>
                                    <p class="text-muted">Platform menyediakan integrasi dengan channel pihak ketiga seperti GetYourGuide. Anda bertanggung jawab atas kepatuhan terhadap syarat dan ketentuan masing-masing channel, termasuk kewajiban kontraktual dan operasionalnya.</p>
                                    <p class="text-muted">Desma bertindak sebagai perantara teknis dan tidak bertanggung jawab atas perselisihan komersial antara operator tour dan channel.</p>
                                </div>

                                <div>
                                    <h5>Pembayaran &amp; Langganan</h5>
                                    <p class="text-muted">Penggunaan platform tunduk pada paket berlangganan yang dipilih saat pendaftaran. Tagihan akan dikirim sesuai siklus berlangganan dan wajib dibayar tepat waktu agar layanan tetap aktif.</p>
                                    <ul class="text-muted vstack gap-2">
                                        <li>Keterlambatan pembayaran dapat menyebabkan pembatasan fitur atau penangguhan akun.</li>
                                        <li>Pembatalan langganan dapat dilakukan kapan saja, namun biaya yang sudah dibayarkan untuk periode berjalan tidak dikembalikan kecuali diatur lain.</li>
                                        <li>Perubahan harga akan diberitahukan sekurang-kurangnya 30 hari sebelum diberlakukan.</li>
                                    </ul>
                                </div>

                                <div>
                                    <h5>Privasi &amp; Keamanan Data</h5>
                                    <p class="text-muted">Kami menerapkan langkah-langkah keamanan yang wajar untuk melindungi data Anda. Detail mengenai pengumpulan, penggunaan, dan perlindungan data diatur pada Kebijakan Privasi yang merupakan bagian tidak terpisahkan dari ketentuan ini.</p>
                                </div>

                                <div>
                                    <h5>Batasan Tanggung Jawab</h5>
                                    <p class="text-muted">Desma disediakan "apa adanya". Sejauh diizinkan oleh hukum yang berlaku, kami tidak bertanggung jawab atas kerugian tidak langsung, kehilangan keuntungan, atau kerusakan akibat ketidakakuratan data yang Anda masukkan.</p>
                                </div>

                                <div>
                                    <h5>Penghentian Layanan</h5>
                                    <p class="text-muted">Kami dapat menangguhkan atau menghentikan akses tenant yang melanggar ketentuan ini, tanpa mengurangi hak kami untuk menempuh upaya hukum lain. Anda juga dapat menghentikan langganan kapan saja melalui menu pengaturan tenant.</p>
                                </div>

                                <div>
                                    <h5>Hukum yang Berlaku</h5>
                                    <p class="text-muted">Syarat dan ketentuan ini tunduk pada hukum Republik Indonesia. Setiap perselisihan akan diselesaikan secara musyawarah, atau melalui jalur hukum yang berlaku apabila musyawarah tidak tercapai.</p>
                                </div>

                                <div>
                                    <h5>Kontak</h5>
                                    <p class="text-muted">Untuk pertanyaan terkait syarat dan ketentuan ini, silakan hubungi kami melalui halaman <a href="{{ route('root') }}#contact">Kontak</a>.</p>
                                </div>

                                <div class="text-end mt-4">
                                    <a href="{{ route('root') }}" class="btn btn-outline-primary">
                                        <i class="ri-arrow-left-line align-bottom me-1"></i> Kembali ke Beranda
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('layouts.landing-footer')

        <button onclick="topFunction()" class="btn btn-info btn-icon landing-back-top" id="back-to-top">
            <i class="ri-arrow-up-line"></i>
        </button>
    </div>
@endsection

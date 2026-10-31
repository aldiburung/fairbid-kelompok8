<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'FairBid - Lelang Online')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --fairbid-blue: #1F3864;
            --fairbid-orange: #E8832A;
            --fairbid-bg: #F5F7FA;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--fairbid-bg);
            color: #233047;
            font-family: 'Inter', sans-serif;
        }

        .text-fairbid { color: var(--fairbid-blue) !important; }
        .bg-fairbid { background-color: var(--fairbid-blue) !important; }
        .btn-fairbid {
            border-color: var(--fairbid-blue);
            background-color: var(--fairbid-blue);
            color: #fff;
        }
        .btn-fairbid:hover, .btn-fairbid:focus {
            border-color: #172b4d;
            background-color: #172b4d;
            color: #fff;
        }
        .btn-orange {
            border-color: var(--fairbid-orange);
            background-color: var(--fairbid-orange);
            color: #fff;
        }
        .btn-orange:hover, .btn-orange:focus {
            border-color: #cf7020;
            background-color: #cf7020;
            color: #fff;
        }
        .navbar-fairbid { background-color: var(--fairbid-blue); }
        .fairbid-card {
            overflow: hidden;
            border: 0;
            border-radius: 1rem;
            background-color: #fff;
            box-shadow: 0 .4rem 1.4rem rgba(31, 56, 100, .08);
        }
        .form-control:focus, .form-select:focus, .form-check-input:focus {
            border-color: var(--fairbid-orange);
            box-shadow: 0 0 0 .2rem rgba(232, 131, 42, .18);
        }
        .form-check-input:checked {
            border-color: var(--fairbid-blue);
            background-color: var(--fairbid-blue);
        }
        main { flex: 1 0 auto; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-fairbid py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="{{ url('/') }}">Fair<span class="text-warning">Bid</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2 pt-3 pt-lg-0">
                    <a class="nav-link" href="{{ url('/') }}">Beranda</a>
                    <a class="nav-link" href="{{ url('/#lelang') }}">Katalog</a>
                    <a class="nav-link" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-orange fw-semibold px-4 ms-lg-2" href="{{ route('register') }}">Daftar</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <a class="text-fairbid fw-bold fs-5 text-decoration-none" href="{{ url('/') }}">FairBid</a>
            <p class="text-secondary small mb-0">&copy; {{ date('Y') }} FairBid. Lelang online yang adil dan transparan.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

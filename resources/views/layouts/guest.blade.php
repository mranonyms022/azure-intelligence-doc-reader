<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice DMS — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1B3A5C 0%, #0D6E56 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #1B3A5C, #0D6E56);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #fff;
            margin: 0 auto 1rem;
        }
        .form-control:focus {
            border-color: #0D6E56;
            box-shadow: 0 0 0 0.2rem rgba(13,110,86,0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #1B3A5C, #0D6E56);
            border: none;
            color: #fff;
            padding: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: opacity 0.2s;
        }
        .btn-login:hover { opacity: 0.9; color: #fff; }
        .input-group-text { background: #f8f9fa; border-right: none; }
        .form-control { border-left: none; }
        .form-control:focus + .input-group-text { border-color: #0D6E56; }
    </style>
    @livewireStyles
</head>
<body>
    <div class="login-card card p-4">
        <div class="card-body">
            <div class="text-center mb-4">
                <div class="login-logo"><i class="bi bi-receipt"></i></div>
                <h5 class="fw-bold mb-0" style="color:#1B3A5C;">Invoice DMS</h5>
                <p class="text-muted small mb-0">Azure Document Intelligence</p>
            </div>
            {{ $slot }}
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>

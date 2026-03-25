<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Unauthorized</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="text-center p-4">
        <div style="font-size:4rem;color:#dee2e6;margin-bottom:1rem;">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h4 class="fw-bold" style="color:#1B3A5C;">Access Denied</h4>
        <p class="text-muted mb-4">
            You do not have permission to access this page.<br>
            This area is restricted to administrators only.
        </p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-house me-1"></i>Back to Dashboard
        </a>
    </div>
</body>
</html>

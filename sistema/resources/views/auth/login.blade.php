<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Acceso al Sistema de Venta de Medicamentos San Juan">
  <title>FARM SJB | Iniciar sesión</title>
  <link rel="stylesheet" href="{{ asset('css/bootstrap-login.css') }}">
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>

<body>
<main class="login-wrapper d-flex">
  <section class="login-brand bg-dark text-white d-none d-lg-flex flex-column justify-content-between p-5" aria-hidden="true">
    <div class="d-flex align-items-center">
      <div class="brand-icon rounded-circle bg-primary d-flex align-items-center justify-content-center me-3 flex-shrink-0">
        <span class="text-white fs-5 fw-bold" aria-hidden="true">+</span>
      </div>
      <div class="lh-sm">
        <div class="text-white fw-bold fs-5 lh-1">San Juan</div>
        <div class="sidebar-section-title text-white-50 fw-normal mt-1">Sistema de Venta de Medicamentos</div>
      </div>
    </div>

    <div class="login-brand-copy">
      <h1 class="display-6 fw-bold mb-3">Gestiona tu farmacia de forma simple y segura.</h1>
      <p class="text-white-50 mb-0">Ventas, inventario y clientes en un solo lugar.</p>
    </div>

    <ul class="list-unstyled d-flex flex-column gap-3 mb-0 small">
      <li class="d-flex align-items-center gap-2 text-white-50">Acceso protegido por roles</li>
      <li class="d-flex align-items-center gap-2 text-white-50">Reportes y control de inventario</li>
      <li class="d-flex align-items-center gap-2 text-white-50">Gestión de clientes y ventas</li>
    </ul>
  </section>

  <section class="flex-grow-1 d-flex align-items-center justify-content-center p-4">
    <div class="login-card w-100">
      <div class="d-flex d-lg-none align-items-center justify-content-center mb-4">
        <div class="brand-icon rounded-circle bg-primary d-flex align-items-center justify-content-center me-2">
          <span class="text-white fs-5 fw-bold" aria-hidden="true">+</span>
        </div>
        <span class="fw-bold fs-5 text-dark">San Juan</span>
      </div>

      <div class="mb-4">
        <h2 class="h3 fw-bold text-dark mb-1">Bienvenido</h2>
        <p class="text-secondary small mb-0">Ingresa tus credenciales para continuar.</p>
      </div>

      @if (session('ok'))
        <div class="alert alert-success small py-2" role="status">
          {{ session('ok') }}
        </div>
      @endif

      @if ($errors->has('email') || $errors->has('password'))
        <div class="alert alert-danger small py-2" role="alert">
          @error('email')
            <div>{{ $message }}</div>
          @enderror
          @error('password')
            <div>{{ $message }}</div>
          @enderror
        </div>
      @endif

      <form id="credentialForm" method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="mb-3">
          <label for="loginEmail" class="form-label small fw-semibold text-dark">Correo electrónico</label>
          <div class="input-group login-input-group">
            <input type="email" id="loginEmail" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="usuario@sanjuan.com"
                   autocomplete="username" required autofocus>
          </div>
        </div>

        <div class="mb-3">
          <label for="loginPassword" class="form-label small fw-semibold text-dark">Contraseña</label>
          <div class="input-group login-input-group">
            <input type="password" id="loginPassword" name="password"
                   class="form-control border-end-0 @error('password') is-invalid @enderror"
                   placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
            <button type="button" id="togglePassword" class="input-group-text bg-white"
                    aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
          </div>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="rememberMe" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
          <label class="form-check-label small text-secondary" for="rememberMe">Mantener sesión iniciada</label>
        </div>

        <button type="submit" id="loginSubmit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3">
          <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
          <span class="btn-label">Iniciar sesión</span>
        </button>
      </form>

      <p class="text-center text-secondary small mt-5 mb-0">&copy; 2026 FARM SJB · Todos los derechos reservados</p>
    </div>
  </section>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('loginPassword');
    const toggle = document.getElementById('togglePassword');
    const form = document.getElementById('credentialForm');
    const submit = document.getElementById('loginSubmit');

    toggle.addEventListener('click', function () {
      const visible = password.type === 'text';
      password.type = visible ? 'password' : 'text';
      toggle.textContent = visible ? 'Mostrar' : 'Ocultar';
      toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
      toggle.setAttribute('aria-pressed', String(!visible));
    });

    form.addEventListener('submit', function () {
      submit.disabled = true;
      submit.querySelector('.spinner-border').classList.remove('d-none');
      submit.querySelector('.btn-label').textContent = 'Ingresando...';
    });
  });
</script>
</body>
</html>

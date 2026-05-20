<?php
require_once 'config.php';
startSession();

// Si ya está logueado, redirigir al panel correcto
$u = currentUser();
if ($u) {
    header('Location: ' . ($u['rol'] === 'admin' ? 'admin.php' : 'cliente.php'));
    exit;
}

$error      = '';
$tipoActivo = 'cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';   // NO trim en password
    $tipo     = $_POST['tipo']          ?? 'cliente';
    $tipoActivo = $tipo;

    if (!$email || !$password) {
        $error = 'Por favor completa todos los campos.';
    } else {
        try {
            $stmt = db()->prepare(
                "SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($tipo !== $user['rol']) {
                    $error = 'Tipo de acceso incorrecto para esta cuenta.';
                } else {
                    $_SESSION['user'] = [
                        'id'       => $user['id'],
                        'nombre'   => $user['nombre'],
                        'apellido' => $user['apellido'],
                        'email'    => $user['email'],
                        'rol'      => $user['rol'],
                        'telefono' => $user['telefono'],
                    ];
                    header('Location: ' . ($user['rol'] === 'admin' ? 'admin.php' : 'cliente.php'));
                    exit;
                }
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión con la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beleza — Acceso</title>
<link rel="stylesheet" href="css/login.css">
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>

<div class="split">

  <!-- Panel izquierdo decorativo -->
  <div class="split-left">
    <div class="deco-content">
      <div class="brand-mark">B</div>
      <img src="logo.avif" alt="Beleza"
           style="width:100px;height:100px;object-fit:contain;display:block;margin:0 auto 12px;filter:drop-shadow(0 2px 10px rgba(0,0,0,.5))"
           onerror="this.style.display='none'">
      <div class="brand-name">Beleza</div>
      <div class="brand-line"></div>
      <p class="brand-tagline">Salón de Belleza</p>
      <p class="brand-quote">"El cuidado que mereces,<br>cuando tú lo decides."</p>

      <!-- Encuéntranos -->
      <div style="margin-top:32px;display:flex;flex-direction:column;align-items:center;gap:10px">
        <p style="font-size:10px;letter-spacing:3px;text-transform:uppercase;color:rgba(201,169,110,.5);margin-bottom:2px">Encuéntranos aquí</p>
        <div style="display:flex;gap:14px;justify-content:center">
          <a href="https://maps.google.com" target="_blank" rel="noopener"
             style="display:flex;align-items:center;gap:6px;color:rgba(240,237,232,.45);font-size:12px;text-decoration:none;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:6px 14px;transition:all .2s"
             onmouseover="this.style.color='#C9A96E';this.style.borderColor='rgba(201,169,110,.4)'"
             onmouseout="this.style.color='rgba(240,237,232,.45)';this.style.borderColor='rgba(255,255,255,.1)'">
            <!-- Google Maps icon -->
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Google Maps
          </a>
          <a href="https://twitter.com" target="_blank" rel="noopener"
             style="display:flex;align-items:center;gap:6px;color:rgba(240,237,232,.45);font-size:12px;text-decoration:none;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:6px 14px;transition:all .2s"
             onmouseover="this.style.color='#C9A96E';this.style.borderColor='rgba(201,169,110,.4)'"
             onmouseout="this.style.color='rgba(240,237,232,.45)';this.style.borderColor='rgba(255,255,255,.1)'">
            <!-- X/Twitter icon -->
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.733-8.835L1.254 2.25H8.08l4.259 5.63L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            Twitter / X
          </a>
        </div>
      </div>
    </div>
    <div class="deco-circles">
      <div class="circle c1"></div>
      <div class="circle c2"></div>
      <div class="circle c3"></div>
    </div>
  </div>

  <!-- Panel derecho: formulario -->
  <div class="split-right">
    <div class="form-wrap">

      <div class="form-header">
        <h1>Bienvenido</h1>
        <p>Accede a tu cuenta para continuar</p>
      </div>

      <!-- Selector de tipo -->
      <div class="type-toggle">
        <button
          type="button"
          class="type-btn <?= $tipoActivo === 'cliente' ? 'active' : '' ?>"
          id="btn-cliente"
          onclick="setTipo('cliente')">
          Cliente
        </button>
        <button
          type="button"
          class="type-btn <?= $tipoActivo === 'admin' ? 'active' : '' ?>"
          id="btn-admin"
          onclick="setTipo('admin')">
          Administrador
        </button>
        <div class="type-slider" id="type-slider"></div>
      </div>

      <?php if ($error): ?>
        <div class="alert-error">
          <span class="alert-icon">!</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="login-form" autocomplete="off">
        <input type="hidden" name="tipo" id="input-tipo" value="<?= htmlspecialchars($tipoActivo) ?>">

        <div class="field">
          <label for="email">Correo electrónico</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="tucorreo@ejemplo.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
            autofocus>
        </div>

        <div class="field">
          <label for="password-field">Contraseña</label>
          <div class="pass-wrap">
            <input
              type="password"
              id="password-field"
              name="password"
              placeholder="••••••••"
              required>
            <button type="button" class="eye-btn" onclick="togglePass()" id="eye-btn">
              <svg id="eye-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit" id="btn-submit">
          Ingresar
        </button>
      </form>

      <div class="form-footer">
        ¿No tienes cuenta? <a href="registro.php">Crear cuenta de cliente</a>
      </div>

    </div>
  </div>
</div>

<script src="js/login.js"></script>
</body>
</html>

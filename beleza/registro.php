<?php
require_once 'config.php';
startSession();
if (currentUser()) { header('Location: cliente.php'); exit; }

$error = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $tel      = trim($_POST['telefono'] ?? '');
    $pass     = $_POST['password']      ?? '';
    $pass2    = $_POST['password2']     ?? '';

    if (!$nombre || !$email || !$pass) {
        $error = 'Nombre, correo y contraseña son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $check = db()->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Este correo ya está registrado.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = db()->prepare(
                    "INSERT INTO usuarios (nombre,apellido,email,telefono,password,rol)
                     VALUES (?,?,?,?,?,'cliente')"
                );
                $stmt->execute([$nombre, $apellido, $email, $tel, $hash]);
                $ok = true;
            }
        } catch (Exception $e) {
            $error = 'Error al registrar. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beleza — Crear cuenta</title>
<link rel="stylesheet" href="css/login.css">
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* Extra styles for registro page */
.reg-card {
  background: white;
  border: 1px solid var(--ink-brd);
  border-radius: 14px;
  padding: 36px 40px;
  width: 100%;
  max-width: 480px;
}
.reg-card h1 {
  font-family: 'Libre Baskerville', serif;
  font-size: 26px;
  font-weight: 400;
  color: var(--ink);
  margin-bottom: 4px;
}
.reg-card .sub {
  font-size: 13px;
  color: var(--ink-sub);
  margin-bottom: 28px;
}
.row2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.alert-ok {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 8px;
  padding: 14px 16px;
  font-size: 14px;
  color: #166534;
  margin-bottom: 16px;
  text-align: center;
}
.alert-ok a { color: var(--accent); }
body.reg-body {
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  background: var(--bg);
}
</style>
</head>
<body class="reg-body">
<div class="reg-card">
  <h1>Crear cuenta</h1>
  <p class="sub">Únete a Beleza y agenda tus citas en minutos</p>

  <?php if ($error): ?>
    <div class="alert-error">
      <span class="alert-icon">!</span>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($ok): ?>
    <div class="alert-ok">
      ✓ ¡Cuenta creada exitosamente!<br>
      <a href="login.php">Haz clic aquí para iniciar sesión</a>
    </div>
  <?php else: ?>

  <form method="POST">
    <div class="row2">
      <div class="field">
        <label>Nombre</label>
        <input name="nombre" placeholder="Laura"
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Apellido</label>
        <input name="apellido" placeholder="García"
               value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>">
      </div>
    </div>
    <div class="field">
      <label>Correo electrónico</label>
      <input type="email" name="email" placeholder="tucorreo@ejemplo.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Teléfono (opcional)</label>
      <input type="tel" name="telefono" placeholder="961 000 0000"
             value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Contraseña</label>
      <input type="password" name="password" placeholder="Mínimo 6 caracteres">
    </div>
    <div class="field">
      <label>Confirmar contraseña</label>
      <input type="password" name="password2" placeholder="Repite tu contraseña">
    </div>
    <button type="submit" class="btn-submit" style="margin-top:4px">Crear mi cuenta</button>
  </form>

  <?php endif; ?>

  <div class="form-footer" style="margin-top:18px">
    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
  </div>
</div>
</body>
</html>

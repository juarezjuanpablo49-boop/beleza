<?php
// api.php — Endpoints JSON para llamadas AJAX
require_once 'config.php';
startSession();

$user = currentUser();
if (!$user) { jsonResponse(['error' => 'No autenticado'], 401); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function isAdmin(): bool {
    return (currentUser()['rol'] ?? '') === 'admin';
}

try {
    switch ($action) {

    /* ---- CITAS ---- */
    case 'citas_list':
        $mes = $_GET['mes'] ?? date('Y-m');
        if (isAdmin()) {
            $stmt = db()->prepare("
                SELECT c.*, u.nombre AS cliente_nombre, u.apellido AS cliente_apellido,
                       u.telefono AS cliente_tel,
                       s.nombre AS servicio_nombre, s.categoria, s.duracion_min,
                       e.nombre AS empleada_nombre, e.apellido AS empleada_apellido
                FROM citas c
                JOIN usuarios u  ON c.cliente_id  = u.id
                JOIN servicios s ON c.servicio_id  = s.id
                LEFT JOIN empleadas e ON c.empleada_id = e.id
                WHERE DATE_FORMAT(c.fecha,'%Y-%m') = ?
                ORDER BY c.fecha, c.hora_inicio");
            $stmt->execute([$mes]);
        } else {
            $stmt = db()->prepare("
                SELECT c.*,
                       s.nombre AS servicio_nombre, s.categoria, s.duracion_min,
                       e.nombre AS empleada_nombre, e.apellido AS empleada_apellido
                FROM citas c
                JOIN servicios s ON c.servicio_id = s.id
                LEFT JOIN empleadas e ON c.empleada_id = e.id
                WHERE c.cliente_id = ? AND DATE_FORMAT(c.fecha,'%Y-%m') = ?
                ORDER BY c.fecha, c.hora_inicio");
            $stmt->execute([$user['id'], $mes]);
        }
        jsonResponse(['citas' => $stmt->fetchAll()]);

    case 'cita_crear':
        $data       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $clienteId  = isAdmin() ? (int)($data['cliente_id'] ?? $user['id']) : $user['id'];
        $servicioId = (int)($data['servicio_id'] ?? 0);
        $empleadaId = !empty($data['empleada_id']) ? (int)$data['empleada_id'] : null;
        $fecha      = $data['fecha'] ?? '';
        $horaI      = $data['hora_inicio'] ?? '';
        $notas      = $data['notas'] ?? '';

        if (!$servicioId || !$fecha || !$horaI)
            jsonResponse(['error' => 'Datos incompletos'], 400);

        // Día bloqueado?
        $bl = db()->prepare("SELECT id FROM horarios_bloqueados WHERE fecha=? AND hora_inicio IS NULL LIMIT 1");
        $bl->execute([$fecha]);
        if ($bl->fetch()) jsonResponse(['error' => 'Este día no está disponible.'], 409);

        $srv = db()->prepare("SELECT duracion_min, precio FROM servicios WHERE id=?");
        $srv->execute([$servicioId]);
        $servicio = $srv->fetch();
        if (!$servicio) jsonResponse(['error' => 'Servicio no encontrado'], 404);

        $horaFin = date('H:i', strtotime($horaI) + $servicio['duracion_min'] * 60);

        if ($empleadaId) {
            $conf = db()->prepare("
                SELECT id FROM citas
                WHERE empleada_id=? AND fecha=?
                  AND estado NOT IN ('cancelada','completada')
                  AND hora_inicio < ? AND hora_fin > ?");
            $conf->execute([$empleadaId, $fecha, $horaFin, $horaI]);
            if ($conf->fetch()) jsonResponse(['error' => 'Esa empleada ya tiene cita en ese horario.'], 409);
        }

        $ins = db()->prepare("
            INSERT INTO citas
              (cliente_id,servicio_id,empleada_id,fecha,hora_inicio,hora_fin,estado,notas,monto)
            VALUES (?,?,?,?,?,?,'pendiente',?,?)");
        $ins->execute([$clienteId,$servicioId,$empleadaId,$fecha,$horaI,$horaFin,$notas,$servicio['precio']]);
        jsonResponse(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'cita_completar':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $id     = (int)($_POST['id']     ?? 0);
        $pagado = (int)($_POST['pagado'] ?? 0);
        $metodo = $_POST['metodo_pago']  ?? 'efectivo';
        $stmt = db()->prepare("UPDATE citas SET estado='completada', pagado=?, metodo_pago=?, completada_por=?, completada_en=NOW() WHERE id=?");
        $stmt->execute([$pagado, $metodo, $user['id'], $id]);
        jsonResponse(['ok' => true]);

    case 'cita_cancelar':
        $id = (int)($_POST['id'] ?? 0);
        if (!isAdmin()) {
            $check = db()->prepare("SELECT id FROM citas WHERE id=? AND cliente_id=? AND estado IN ('pendiente','confirmada')");
            $check->execute([$id, $user['id']]);
            if (!$check->fetch()) jsonResponse(['error' => 'No puedes cancelar esta cita'], 403);
        }
        db()->prepare("UPDATE citas SET estado='cancelada' WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true]);

    case 'cita_estado':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $id     = (int)($_POST['id']     ?? 0);
        $estado = $_POST['estado']       ?? '';
        $validos = ['pendiente','confirmada','en_proceso','completada','cancelada'];
        if (!in_array($estado, $validos)) jsonResponse(['error' => 'Estado inválido'], 400);
        db()->prepare("UPDATE citas SET estado=? WHERE id=?")->execute([$estado, $id]);
        jsonResponse(['ok' => true]);

    case 'cita_mover':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $data  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id    = (int)($data['id'] ?? 0);
        $fecha = $data['fecha'] ?? '';
        $hora  = $data['hora_inicio'] ?? '';
        $srv   = db()->prepare("SELECT s.duracion_min FROM citas c JOIN servicios s ON c.servicio_id=s.id WHERE c.id=?");
        $srv->execute([$id]);
        $s = $srv->fetch();
        $horaFin = date('H:i', strtotime($hora) + ($s['duracion_min'] ?? 60) * 60);
        db()->prepare("UPDATE citas SET fecha=?,hora_inicio=?,hora_fin=? WHERE id=?")->execute([$fecha,$hora,$horaFin,$id]);
        jsonResponse(['ok' => true]);

    /* ---- CALENDARIO ---- */
    case 'calendario_dias':
        $mes = $_GET['mes'] ?? date('Y-m');
        if (isAdmin()) {
            $stmt = db()->prepare("SELECT fecha, COUNT(*) AS total, SUM(estado='completada') AS completadas FROM citas WHERE DATE_FORMAT(fecha,'%Y-%m')=? AND estado!='cancelada' GROUP BY fecha");
            $stmt->execute([$mes]);
        } else {
            $stmt = db()->prepare("SELECT fecha, COUNT(*) AS total, SUM(estado='completada') AS completadas FROM citas WHERE cliente_id=? AND DATE_FORMAT(fecha,'%Y-%m')=? AND estado!='cancelada' GROUP BY fecha");
            $stmt->execute([$user['id'], $mes]);
        }
        $diasCitas = $stmt->fetchAll();
        $bl = db()->prepare("SELECT fecha FROM horarios_bloqueados WHERE DATE_FORMAT(fecha,'%Y-%m')=?");
        $bl->execute([$mes]);
        $diasBloq = array_column($bl->fetchAll(), 'fecha');
        jsonResponse(['dias' => $diasCitas, 'bloqueados' => $diasBloq]);

    /* ---- CATÁLOGOS ---- */
    case 'servicios_list':
        $stmt = db()->query("SELECT * FROM servicios WHERE activo=1 ORDER BY categoria, nombre");
        jsonResponse(['servicios' => $stmt->fetchAll()]);

    case 'empleadas_list':
        $stmt = db()->query("SELECT * FROM empleadas WHERE activo=1 ORDER BY nombre");
        jsonResponse(['empleadas' => $stmt->fetchAll()]);

    /* ---- DISPONIBILIDAD ---- */
    case 'disponibilidad':
        $fecha      = $_GET['fecha']      ?? '';
        $servicioId = (int)($_GET['servicio_id'] ?? 0);
        $empleadaId = !empty($_GET['empleada_id']) ? (int)$_GET['empleada_id'] : null;
        if (!$fecha || !$servicioId) jsonResponse(['error' => 'Faltan datos'], 400);

        $srv = db()->prepare("SELECT duracion_min FROM servicios WHERE id=?");
        $srv->execute([$servicioId]);
        $duracion = $srv->fetch()['duracion_min'] ?? 60;

        if ($empleadaId) {
            $q = db()->prepare("SELECT hora_inicio,hora_fin FROM citas WHERE fecha=? AND empleada_id=? AND estado NOT IN ('cancelada','completada')");
            $q->execute([$fecha, $empleadaId]);
        } else {
            $q = db()->prepare("SELECT hora_inicio,hora_fin FROM citas WHERE fecha=? AND estado NOT IN ('cancelada','completada')");
            $q->execute([$fecha]);
        }
        $ocupadas = $q->fetchAll();
        $slots = [];
        for ($h = 9; $h < 19; $h++) {
            foreach ([0, 30] as $m) {
                $inicio = sprintf('%02d:%02d', $h, $m);
                $fin    = date('H:i', strtotime($inicio) + $duracion * 60);
                if (strtotime($fin) > strtotime('19:00')) break;
                $libre = true;
                foreach ($ocupadas as $o) {
                    if ($inicio < $o['hora_fin'] && $fin > $o['hora_inicio']) { $libre = false; break; }
                }
                $slots[] = ['hora' => $inicio, 'disponible' => $libre];
            }
        }
        jsonResponse(['slots' => $slots]);

    /* ---- HORARIOS BLOQUEADOS ---- */
    case 'bloquear_dia':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        db()->prepare("INSERT INTO horarios_bloqueados (fecha,motivo,creado_por) VALUES (?,?,?)")
            ->execute([$data['fecha'], $data['motivo'] ?? '', $user['id']]);
        jsonResponse(['ok' => true]);

    case 'desbloquear_dia':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        db()->prepare("DELETE FROM horarios_bloqueados WHERE fecha=?")->execute([$data['fecha']]);
        jsonResponse(['ok' => true]);

    /* ---- CLIENTES ---- */
    case 'clientes_list':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $q = db()->query("SELECT u.*, COUNT(c.id) AS total_citas FROM usuarios u LEFT JOIN citas c ON c.cliente_id=u.id WHERE u.rol='cliente' AND u.activo=1 GROUP BY u.id ORDER BY u.nombre");
        jsonResponse(['clientes' => $q->fetchAll()]);

    /* ---- STATS ---- */
    case 'stats':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $hoy = date('Y-m-d');
        $mes = date('Y-m');
        $citasHoy        = db()->query("SELECT COUNT(*) FROM citas WHERE fecha='$hoy' AND estado!='cancelada'")->fetchColumn();
        $ingresosMes     = db()->query("SELECT COALESCE(SUM(monto),0) FROM citas WHERE DATE_FORMAT(fecha,'%Y-%m')='$mes' AND pagado=1")->fetchColumn();
        $clientesActivos = db()->query("SELECT COUNT(DISTINCT cliente_id) FROM citas WHERE DATE_FORMAT(fecha,'%Y-%m')='$mes'")->fetchColumn();
        $serviciosMes    = db()->query("SELECT COUNT(*) FROM citas WHERE DATE_FORMAT(fecha,'%Y-%m')='$mes' AND estado='completada'")->fetchColumn();
        $ing12           = db()->query("SELECT DATE_FORMAT(fecha,'%Y-%m') AS mes, SUM(monto) AS total FROM citas WHERE pagado=1 AND fecha>=DATE_SUB(NOW(),INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(fecha,'%Y-%m') ORDER BY mes")->fetchAll();
        jsonResponse(compact('citasHoy','ingresosMes','clientesActivos','serviciosMes','ing12'));

    /* ---- INVENTARIO ---- */
    case 'inventario_list':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        jsonResponse(['inventario' => db()->query("SELECT * FROM inventario ORDER BY categoria,nombre")->fetchAll()]);

    case 'inventario_update':
        if (!isAdmin()) jsonResponse(['error' => 'Sin permiso'], 403);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        db()->prepare("UPDATE inventario SET stock=? WHERE id=?")->execute([(int)$data['stock'], (int)$data['id']]);
        jsonResponse(['ok' => true]);

    /* ---- PERFIL ---- */
    case 'perfil_update':
        $data    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $nombre  = trim($data['nombre']   ?? '');
        $apell   = trim($data['apellido'] ?? '');
        $tel     = trim($data['telefono'] ?? '');
        db()->prepare("UPDATE usuarios SET nombre=?,apellido=?,telefono=? WHERE id=?")
            ->execute([$nombre, $apell, $tel, $user['id']]);
        $_SESSION['user']['nombre']   = $nombre;
        $_SESSION['user']['apellido'] = $apell;
        jsonResponse(['ok' => true]);

    /* ---- LOGOUT ---- */
    case 'logout':
        session_unset();
        session_destroy();
        jsonResponse(['ok' => true, 'redirect' => 'login.php']);

    default:
        jsonResponse(['error' => 'Acción desconocida'], 400);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Error interno: ' . $e->getMessage()], 500);
}

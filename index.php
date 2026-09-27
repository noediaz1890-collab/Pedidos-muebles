<?php
// Configuración y conexión a SQLite mediante PDO
$dbFile = __DIR__ . '/pedidos.db';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Creación automática de la tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente TEXT NOT NULL,
        mueble TEXT NOT NULL,
        medida TEXT NOT NULL,
        precio REAL NOT NULL,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// Procesar formulario (Crear pedido)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $cliente = trim($_POST['cliente'] ?? '');
    $mueble  = trim($_POST['mueble'] ?? '');
    $medida  = trim($_POST['medida'] ?? '');
    $precio  = filter_var($_POST['precio'] ?? 0, FILTER_VALIDATE_FLOAT);

    if (!empty($cliente) && !empty($mueble) && !empty($medida) && $precio !== false && $precio >= 0) {
        $stmt = $pdo->prepare("INSERT INTO pedidos (cliente, mueble, medida, precio) VALUES (:cliente, :mueble, :medida, :precio)");
        $stmt->execute([
            ':cliente' => $cliente,
            ':mueble'  => $mueble,
            ':medida'  => $medida,
            ':precio'  => $precio
        ]);
    }
    header("Location: index.php");
    exit;
}

// Procesar eliminación (Eliminar pedido)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
    header("Location: index.php");
    exit;
}

// Obtener todos los pedidos
$stmt = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC");
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PedidosMuebles - Gestión de Pedidos</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-box-seam fs-1 text-primary me-3"></i>
            <div>
                <h1 class="h3 mb-0">Gestión de Pedidos de Muebles</h1>
            </div>
        </div>

        <!-- Formulario para agregar pedidos -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Nuevo Pedido</h5>
            </div>
            <div class="card-body">
                <form action="index.php" method="POST" class="row g-3">
                    <input type="hidden" name="action" value="crear">
                    
                    <div class="col-md-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="cliente" name="cliente" placeholder="Ej: María López" required>
                    </div>

                    <div class="col-md-3">
                        <label for="mueble" class="form-label">Mueble</label>
                        <input type="text" class="form-control" id="mueble" name="mueble" placeholder="Ej: Comedor Roble" required>
                    </div>

                    <div class="col-md-3">
                        <label for="medida" class="form-label">Medida</label>
                        <input type="text" class="form-control" id="medida" name="medida" placeholder="Ej: 2.00m x 1.20m" required>
                    </div>

                    <div class="col-md-3">
                        <label for="precio" class="form-label">Precio ($)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" placeholder="0.00" required>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save me-1"></i> Guardar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de listado de pedidos -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-list-check me-2"></i>Listado de Pedidos</h5>
                <span class="badge bg-secondary"><?= count($pedidos) ?> Registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># ID</th>
                                <th>Cliente</th>
                                <th>Mueble</th>
                                <th>Medida</th>
                                <th>Precio</th>
                                <th>Fecha</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No hay pedidos registrados en la base de datos.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($pedido['id']) ?></strong></td>
                                        <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                                        <td><?= htmlspecialchars($pedido['mueble']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($pedido['medida']) ?></span></td>
                                        <td>$<?= number_format($pedido['precio'], 2) ?></td>
                                        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></td>
                                        <td class="text-center">
                                            <form action="index.php" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este pedido?');">
                                                <input type="hidden" name="action" value="eliminar">
                                                <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
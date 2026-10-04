<?php
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/semilla.php';

/**
 * Conexión PDO única. En modo demo (sin DB_DSN) crea una base SQLite con 24 meses de ventas.
 */
function db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $opciones = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    );

    if (DB_DSN !== '') {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $opciones);
        return $pdo;
    }

    $archivo = dirname(dirname(__FILE__)) . '/data/demo.sqlite';
    $nueva = !file_exists($archivo);
    $pdo = new PDO('sqlite:' . $archivo, null, null, $opciones);
    if ($nueva) {
        crear_base_demo($pdo);
    }
    return $pdo;
}

/** true si la conexión es SQLite (modo demo). Algunas funciones de fecha cambian entre motores. */
function es_sqlite()
{
    return db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
}

function crear_base_demo(PDO $pdo)
{
    $pdo->exec('CREATE TABLE vendedores (id INTEGER PRIMARY KEY, nombre VARCHAR(80) NOT NULL, zona VARCHAR(40) NOT NULL)');
    $pdo->exec('CREATE TABLE categorias (id INTEGER PRIMARY KEY, nombre VARCHAR(60) NOT NULL)');
    $pdo->exec('CREATE TABLE productos (id INTEGER PRIMARY KEY, nombre VARCHAR(100) NOT NULL, categoria_id INTEGER NOT NULL, precio DECIMAL(12,2) NOT NULL)');
    $pdo->exec('CREATE TABLE ventas (id INTEGER PRIMARY KEY, fecha DATE NOT NULL, vendedor_id INTEGER NOT NULL, cliente VARCHAR(100) NOT NULL)');
    $pdo->exec('CREATE INDEX idx_fecha ON ventas (fecha)');
    $pdo->exec('CREATE TABLE venta_items (id INTEGER PRIMARY KEY, venta_id INTEGER NOT NULL, producto_id INTEGER NOT NULL,
                cantidad INTEGER NOT NULL, precio_unitario DECIMAL(12,2) NOT NULL)');
    $pdo->exec('CREATE INDEX idx_venta ON venta_items (venta_id)');

    $pdo->beginTransaction();
    foreach (generar_datos() as $tabla => $filas) {
        $columnas = columnas_tabla($tabla);
        $marcadores = implode(', ', array_fill(0, count($columnas), '?'));
        $stmt = $pdo->prepare("INSERT INTO $tabla (" . implode(', ', $columnas) . ") VALUES ($marcadores)");
        foreach ($filas as $fila) {
            $stmt->execute($fila);
        }
    }
    $pdo->commit();
}

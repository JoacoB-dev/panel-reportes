-- Panel de reportes · esquema MySQL 5.x / MariaDB
-- Los datos de ejemplo se generan con: php sql/generar_datos.php > sql/datos.sql

CREATE TABLE IF NOT EXISTS vendedores (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80)  NOT NULL,
    zona   VARCHAR(40)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS categorias (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS productos (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(100)  NOT NULL,
    categoria_id INT UNSIGNED  NOT NULL,
    precio       DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_categoria (categoria_id),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ventas (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha       DATE         NOT NULL,
    vendedor_id INT UNSIGNED NOT NULL,
    cliente     VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_fecha (fecha),
    KEY idx_vendedor (vendedor_id),
    CONSTRAINT fk_ventas_vendedor FOREIGN KEY (vendedor_id) REFERENCES vendedores (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS venta_items (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    venta_id        INT UNSIGNED  NOT NULL,
    producto_id     INT UNSIGNED  NOT NULL,
    cantidad        INT UNSIGNED  NOT NULL,
    precio_unitario DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_venta (venta_id),
    KEY idx_producto (producto_id),
    CONSTRAINT fk_items_venta FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

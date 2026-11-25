-- =====================================================================
--  Refaccionaria · Base de datos FASE 1 (23 tablas)
--  Plan del equipo + lo que pide el Figma (Page 2) · 20/11/2026 · MySQL 8
--
--  Versión reducida (de 30 a 23 tablas):
--   - Una sola tienda: el stock, el mínimo y la ubicación viven en productos
--     (se quitaron almacenes e inventario).
--   - Los datos de la tienda viven en configuraciones (se quitó datosNegocio).
--   - Un ajuste = un producto (se quitó detalleAjusteInventario).
--   - Una solicitud = un producto (se quitó detalleSolicitudes).
--   - El tipo de movimiento se escribe directo (se quitó tiposMovimientoInventario).
--   - Todo se cuenta por pieza (se quitó unidadesMedida).
--   - Se quedan pagosVenta (pago a crédito, abonos, dólares) y bitacora.
--
--  CÓMO USARLO (sin tocar tu base actual):
--   1. En Workbench crea una base NUEVA y vacía:
--        CREATE DATABASE refaccionaria_nueva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Selecciónala (doble clic) y corre este archivo completo.
--   Tu base "refacconarial" se queda igual, como respaldo.
--
--  Convenciones del plan: PK idTabla, llaves foráneas fk_idTabla,
--  importes DECIMAL, fechas DATETIME, contraseñas sólo como hash,
--  nada de listas separadas por comas.
--  No se crea ningún usuario: el primer administrador se crea después
--  con un comando de Laravel, para que ninguna contraseña quede escrita aquí.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1) ACCESO: roles, permisos, trabajadores                (4 tablas)
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    idRol        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(40)  NOT NULL UNIQUE,
    descripcion  VARCHAR(255) NULL,
    activo       TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE permisos (
    idPermiso    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(60)  NOT NULL UNIQUE,      -- ej. ventas.cancelar
    descripcion  VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Qué puede hacer cada rol (un renglón por permiso)
CREATE TABLE rolPermisos (
    idRolPermiso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idRol     INT UNSIGNED NOT NULL,
    fk_idPermiso INT UNSIGNED NOT NULL,
    UNIQUE KEY rolPermisos_unico (fk_idRol, fk_idPermiso),
    FOREIGN KEY (fk_idRol)     REFERENCES roles (idRol),
    FOREIGN KEY (fk_idPermiso) REFERENCES permisos (idPermiso)
) ENGINE=InnoDB;

CREATE TABLE trabajadores (
    idTrabajador      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres           VARCHAR(80)  NOT NULL,
    apellidoPaterno   VARCHAR(60)  NOT NULL,
    apellidoMaterno   VARCHAR(60)  NULL,
    RFC               VARCHAR(13)  NOT NULL UNIQUE,
    telefono          VARCHAR(20)  NULL,
    correoElectronico VARCHAR(120) NOT NULL UNIQUE,
    hashContrasena    VARCHAR(255) NOT NULL,
    fk_idRol          INT UNSIGNED NOT NULL,
    fechaCreacion     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo            TINYINT(1)   NOT NULL DEFAULT 1,   -- desactivar, nunca borrar
    fechaUltimoAcceso DATETIME     NULL,
    rememberToken     VARCHAR(100) NULL,                 -- "Recordarme" de Laravel
    FOREIGN KEY (fk_idRol) REFERENCES roles (idRol)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2) CONFIGURACIÓN Y FOLIOS                                (2 tablas)
-- ---------------------------------------------------------------------
-- Una fila por ajuste, incluidos los datos de la tienda (nombre, RFC, logo...)
CREATE TABLE configuraciones (
    idConfiguracion              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave                        VARCHAR(60)  NOT NULL UNIQUE,
    valor                        VARCHAR(500) NULL,
    descripcion                  VARCHAR(255) NULL,
    fechaActualizacion           DATETIME     NULL,
    fk_idTrabajadorActualizacion INT UNSIGNED NULL,
    FOREIGN KEY (fk_idTrabajadorActualizacion) REFERENCES trabajadores (idTrabajador)
) ENGINE=InnoDB;

-- Un renglón por tipo de documento. El admin elige el formato de la venta.
CREATE TABLE folios (
    idFolio        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipoDocumento  VARCHAR(30)  NOT NULL UNIQUE,     -- VENTA, PEDIDO, COTIZACION, AJUSTE, SOLICITUD, INCIDENCIA
    prefijo        VARCHAR(20)  NOT NULL,
    formato        VARCHAR(40)  NOT NULL,            -- {AAAA} = año, {0000} = número con ceros
    anio           SMALLINT UNSIGNED NULL,           -- al cambiar de año el número vuelve a 1
    ultimoNumero   INT UNSIGNED NOT NULL DEFAULT 0,
    longitudNumero TINYINT UNSIGNED NOT NULL DEFAULT 4,
    activo         TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3) CATÁLOGOS Y PRODUCTOS                                 (6 tablas)
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    idCategoria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE marcas (
    idMarca     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Cubre los 3 formatos del Figma: "A-01-03", "Anaquel A-02 · Rack R-01", "Estante F · Nivel 1"
CREATE TABLE ubicaciones (
    idUbicacion     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pasillo         VARCHAR(20)  NULL,     -- también "Zona"
    rack            VARCHAR(20)  NULL,
    anaquel         VARCHAR(20)  NULL,
    nivel           VARCHAR(20)  NULL,
    posicion        VARCHAR(20)  NULL,
    codigoUbicacion VARCHAR(40)  NOT NULL UNIQUE,   -- ej. A-01-03
    descripcion     VARCHAR(255) NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE productos (
    idProducto            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigoInterno         VARCHAR(30)  NOT NULL UNIQUE,   -- SKU, ej. FR-BOS-001
    codigoBarras          VARCHAR(50)  NULL UNIQUE,
    nombre                VARCHAR(150) NOT NULL,
    descripcion           TEXT         NULL,
    fk_idCategoria        INT UNSIGNED NOT NULL,
    fk_idMarca            INT UNSIGNED NOT NULL,
    fk_idUbicacion        INT UNSIGNED NOT NULL,          -- "Sin asignar" si todavía no tiene lugar
    numeroParteFabricante VARCHAR(60)  NULL,
    requiereNumeroSerie   TINYINT(1)   NOT NULL DEFAULT 0,
    precioCompraActual    DECIMAL(10,2) NOT NULL,
    precioVentaActual     DECIMAL(10,2) NOT NULL,
    tasaIVA               DECIMAL(5,2) NULL,              -- NULL = usa el IVA de configuraciones
    existencia            INT UNSIGNED NOT NULL DEFAULT 0, -- sólo cambia con ventas y ajustes, nunca negativa
    stockMinimo           INT UNSIGNED NULL,              -- NULL = usa stockMinimoDefecto de configuraciones
    stockMaximo           INT UNSIGNED NULL,
    imagen                VARCHAR(255) NULL,              -- ruta del archivo PNG/JPG
    activo                TINYINT(1)   NOT NULL DEFAULT 1, -- desactivar, nunca borrar
    fechaCreacion         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaActualizacion    DATETIME     NULL,
    FOREIGN KEY (fk_idCategoria) REFERENCES categorias (idCategoria),
    FOREIGN KEY (fk_idMarca)     REFERENCES marcas (idMarca),
    FOREIGN KEY (fk_idUbicacion) REFERENCES ubicaciones (idUbicacion),
    CHECK (precioCompraActual >= 0 AND precioVentaActual >= 0)
) ENGINE=InnoDB;

-- "Nissan Versa · 2012–2019"
CREATE TABLE productosCompatibilidades (
    idCompatibilidad INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idProducto    INT UNSIGNED NOT NULL,
    marcaVehiculo    VARCHAR(60)  NOT NULL,
    modeloVehiculo   VARCHAR(60)  NOT NULL,
    anioDesde        SMALLINT UNSIGNED NOT NULL,
    anioHasta        SMALLINT UNSIGNED NULL,
    motor            VARCHAR(60)  NULL,
    notas            VARCHAR(255) NULL,
    FOREIGN KEY (fk_idProducto) REFERENCES productos (idProducto)
) ENGINE=InnoDB;

-- Motivos de catálogo: ajustes, cancelaciones, incidencias, desactivaciones...
CREATE TABLE motivos (
    idMotivo        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(80)  NOT NULL,
    categoriaMotivo VARCHAR(30)  NOT NULL,
    descripcion     VARCHAR(255) NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY motivos_unico (categoriaMotivo, nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4) MOVIMIENTOS Y AJUSTES DE INVENTARIO                   (2 tablas)
-- ---------------------------------------------------------------------
-- Historial: cada vez que la existencia sube o baja queda un renglón.
CREATE TABLE movimientosInventario (
    idMovimientoInventario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idProducto          INT UNSIGNED NOT NULL,
    tipoMovimiento         VARCHAR(20)  NOT NULL,  -- ENTRADA, SALIDA, VENTA, CANCELACION_VENTA, DEVOLUCION, CONTEO, INCIDENCIA
    cantidad               INT UNSIGNED NOT NULL,
    existenciaAnterior     INT UNSIGNED NOT NULL,
    existenciaNueva        INT UNSIGNED NOT NULL,
    costoUnitario          DECIMAL(10,2) NULL,
    fk_idTrabajador        INT UNSIGNED NOT NULL,
    fechaMovimiento        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    referenciaTipo         VARCHAR(20)  NULL,      -- VENTA, AJUSTE, SOLICITUD
    referenciaId           INT UNSIGNED NULL,      -- id de esa venta, ajuste o solicitud
    fk_idMotivo            INT UNSIGNED NULL,
    observaciones          VARCHAR(500) NULL,
    FOREIGN KEY (fk_idProducto)   REFERENCES productos (idProducto),
    FOREIGN KEY (fk_idTrabajador) REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idMotivo)     REFERENCES motivos (idMotivo),
    KEY movimientos_referencia (referenciaTipo, referenciaId)
) ENGINE=InnoDB;

-- Un ajuste = un producto. Entrada (con número de factura), salida o conteo físico.
CREATE TABLE ajustesInventario (
    idAjuste                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio                   VARCHAR(30)  NOT NULL UNIQUE,
    tipoAjuste              VARCHAR(10)  NOT NULL,     -- ENTRADA, SALIDA, CONTEO
    fk_idProducto           INT UNSIGNED NOT NULL,
    cantidad                INT UNSIGNED NOT NULL,     -- piezas que entran o salen
    existenciaSistema       INT UNSIGNED NOT NULL,     -- lo que había al pedir el ajuste
    existenciaContada       INT UNSIGNED NULL,         -- sólo en CONTEO
    diferencia              INT          NULL,         -- sólo en CONTEO
    costoUnitario           DECIMAL(10,2) NULL,
    fk_idTrabajador         INT UNSIGNED NOT NULL,
    fecha                   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado                  VARCHAR(12)  NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE, APLICADO, RECHAZADO, CANCELADO
    fk_idMotivo             INT UNSIGNED NULL,
    referencia              VARCHAR(60)  NULL,         -- ej. FAC-2026-0184
    observaciones           VARCHAR(500) NULL,
    fk_idTrabajadorAutoriza INT UNSIGNED NULL,
    fechaAutorizacion       DATETIME     NULL,
    FOREIGN KEY (fk_idProducto)           REFERENCES productos (idProducto),
    FOREIGN KEY (fk_idTrabajador)         REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idMotivo)             REFERENCES motivos (idMotivo),
    FOREIGN KEY (fk_idTrabajadorAutoriza) REFERENCES trabajadores (idTrabajador),
    CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5) CLIENTES, VENTAS, PEDIDOS Y PAGOS                     (7 tablas)
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    idCliente       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres         VARCHAR(80)  NULL,               -- NULL si es empresa
    apellidoPaterno VARCHAR(60)  NULL,
    apellidoMaterno VARCHAR(60)  NULL,
    nombreComercial VARCHAR(120) NULL,
    razonSocial     VARCHAR(150) NULL,
    RFC             VARCHAR(13)  NULL,
    telefono        VARCHAR(20)  NOT NULL,
    correo          VARCHAR(120) NULL,
    direccion       VARCHAR(255) NULL,
    tipoCliente     VARCHAR(20)  NOT NULL DEFAULT 'NORMAL', -- NORMAL, TALLER, MAYORISTA (por confirmar)
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    fechaRegistro   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notas           VARCHAR(500) NULL
) ENGINE=InnoDB;

CREATE TABLE metodosPago (
    idMetodoPago INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(40) NOT NULL UNIQUE,
    activo       TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE ventas (
    idVenta                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio                      VARCHAR(30)  NOT NULL UNIQUE,
    fk_idCliente               INT UNSIGNED NULL,     -- NULL = venta de mostrador
    fk_idTrabajador            INT UNSIGNED NOT NULL,
    fechaVenta                 DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estadoVenta                VARCHAR(12)  NOT NULL DEFAULT 'COMPLETADA', -- COMPLETADA, CANCELADA
    condicionPago              VARCHAR(10)  NOT NULL DEFAULT 'CONTADO',    -- CONTADO, CREDITO
    subtotal                   DECIMAL(12,2) NOT NULL,
    descuentoTotal             DECIMAL(12,2) NOT NULL DEFAULT 0,
    impuestosTotal             DECIMAL(12,2) NOT NULL,
    total                      DECIMAL(12,2) NOT NULL,
    observaciones              VARCHAR(500) NULL,
    fechaCancelacion           DATETIME     NULL,
    fk_idTrabajadorCancelacion INT UNSIGNED NULL,
    fk_idMotivoCancelacion     INT UNSIGNED NULL,
    motivoCancelacion          VARCHAR(500) NULL,     -- texto adicional
    FOREIGN KEY (fk_idCliente)               REFERENCES clientes (idCliente),
    FOREIGN KEY (fk_idTrabajador)            REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idTrabajadorCancelacion) REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idMotivoCancelacion)     REFERENCES motivos (idMotivo),
    CHECK (subtotal >= 0 AND descuentoTotal >= 0 AND impuestosTotal >= 0 AND total >= 0)
) ENGINE=InnoDB;

CREATE TABLE detalleVentas (
    idDetalleVenta      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idVenta          INT UNSIGNED NOT NULL,
    fk_idProducto       INT UNSIGNED NOT NULL,
    cantidad            INT UNSIGNED NOT NULL,
    precioUnitarioVenta DECIMAL(10,2) NOT NULL,       -- precio del momento de la venta
    descuento           DECIMAL(10,2) NOT NULL DEFAULT 0,
    tasaIVA             DECIMAL(5,2)  NOT NULL,
    subtotal            DECIMAL(12,2) NOT NULL,
    totalLinea          DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (fk_idVenta)    REFERENCES ventas (idVenta),
    FOREIGN KEY (fk_idProducto) REFERENCES productos (idProducto),
    CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- Pedidos y cotizaciones (tipoPedido). Pendiente: decisión 4 (¿pedido = venta?)
CREATE TABLE pedidos (
    idPedido        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio           VARCHAR(30)  NOT NULL UNIQUE,
    tipoPedido      VARCHAR(12)  NOT NULL DEFAULT 'PEDIDO',    -- PEDIDO, COTIZACION
    fk_idCliente    INT UNSIGNED NULL,
    fk_idTrabajador INT UNSIGNED NOT NULL,
    fechaPedido     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaEntrega    DATE         NULL,
    vigencia        DATE         NULL,                         -- sólo cotizaciones
    estadoPedido    VARCHAR(12)  NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE, COMPLETADO, CANCELADO
    subtotal        DECIMAL(12,2) NOT NULL,
    descuentoTotal  DECIMAL(12,2) NOT NULL DEFAULT 0,
    impuestosTotal  DECIMAL(12,2) NOT NULL,
    total           DECIMAL(12,2) NOT NULL,
    observaciones   VARCHAR(500) NULL,
    fk_idVenta      INT UNSIGNED NULL,                         -- la venta en que se convirtió
    FOREIGN KEY (fk_idCliente)    REFERENCES clientes (idCliente),
    FOREIGN KEY (fk_idTrabajador) REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idVenta)      REFERENCES ventas (idVenta)
) ENGINE=InnoDB;

CREATE TABLE detallePedidos (
    idDetallePedido INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idPedido     INT UNSIGNED NOT NULL,
    fk_idProducto   INT UNSIGNED NOT NULL,
    cantidad        INT UNSIGNED NOT NULL,
    precioUnitario  DECIMAL(10,2) NOT NULL,
    descuento       DECIMAL(10,2) NOT NULL DEFAULT 0,
    tasaIVA         DECIMAL(5,2)  NOT NULL,
    subtotal        DECIMAL(12,2) NOT NULL,
    totalLinea      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (fk_idPedido)   REFERENCES pedidos (idPedido),
    FOREIGN KEY (fk_idProducto) REFERENCES productos (idProducto),
    CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- Pagos: varios por venta, abonos de ventas a crédito, anticipos de pedidos, dólares.
CREATE TABLE pagosVenta (
    idPagoVenta     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idVenta      INT UNSIGNED NULL,
    fk_idPedido     INT UNSIGNED NULL,               -- anticipo (por confirmar, decisión 4)
    fk_idMetodoPago INT UNSIGNED NOT NULL,
    monto           DECIMAL(12,2) NOT NULL,          -- en la moneda del pago
    moneda          CHAR(3)       NOT NULL DEFAULT 'MXN',
    tipoCambio      DECIMAL(10,4) NOT NULL DEFAULT 1, -- pesos por 1 unidad de la moneda
    fechaPago       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fk_idTrabajador INT UNSIGNED NOT NULL,           -- quien recibió el pago
    referencia      VARCHAR(60)   NULL,
    observaciones   VARCHAR(255)  NULL,
    FOREIGN KEY (fk_idVenta)      REFERENCES ventas (idVenta),
    FOREIGN KEY (fk_idPedido)     REFERENCES pedidos (idPedido),
    FOREIGN KEY (fk_idMetodoPago) REFERENCES metodosPago (idMetodoPago),
    FOREIGN KEY (fk_idTrabajador) REFERENCES trabajadores (idTrabajador),
    CHECK (monto > 0 AND tipoCambio > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6) SOLICITUDES Y BITÁCORA                                (2 tablas)
-- ---------------------------------------------------------------------
-- Editar venta, cambios del almacenista, desactivar producto, devoluciones, incidencias.
-- Una solicitud = un producto (o una venta).
CREATE TABLE solicitudes (
    idSolicitud                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio                      VARCHAR(30)  NOT NULL UNIQUE,
    tipoSolicitud              VARCHAR(30)  NOT NULL, -- EDITAR_VENTA, EDITAR_PRODUCTO, DESACTIVAR_PRODUCTO, DEVOLUCION, INCIDENCIA
    fk_idCliente               INT UNSIGNED NULL,
    fk_idVenta                 INT UNSIGNED NULL,
    fk_idProducto              INT UNSIGNED NULL,
    cantidad                   INT UNSIGNED NULL,     -- piezas afectadas (incidencias, devoluciones)
    fk_idTrabajadorSolicitante INT UNSIGNED NULL,
    fk_idTrabajadorResponsable INT UNSIGNED NULL,     -- quien aceptó o rechazó
    fechaSolicitud             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado                     VARCHAR(12)  NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE, EN_REVISION, ACEPTADA, RECHAZADA
    prioridad                  VARCHAR(6)   NULL,     -- ALTA, MEDIA, BAJA (incidencias)
    fk_idMotivo                INT UNSIGNED NULL,
    motivo                     VARCHAR(500) NULL,
    comentario                 VARCHAR(500) NULL,     -- respuesta del gerente/admin
    datosPropuestos            JSON         NULL,     -- los cambios que se aplican al aceptar
    fechaResolucion            DATETIME     NULL,
    FOREIGN KEY (fk_idCliente)               REFERENCES clientes (idCliente),
    FOREIGN KEY (fk_idVenta)                 REFERENCES ventas (idVenta),
    FOREIGN KEY (fk_idProducto)              REFERENCES productos (idProducto),
    FOREIGN KEY (fk_idTrabajadorSolicitante) REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idTrabajadorResponsable) REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idMotivo)                REFERENCES motivos (idMotivo)
) ENGINE=InnoDB;

-- Quién hizo qué y quién lo autorizó (desactivar, editar, cancelar...).
CREATE TABLE bitacora (
    idBitacora              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_idTrabajador         INT UNSIGNED NOT NULL,   -- quien hizo la acción
    fk_idTrabajadorAutoriza INT UNSIGNED NULL,       -- quien puso su contraseña para autorizar
    accion                  VARCHAR(30)  NOT NULL,   -- CREAR, EDITAR, DESACTIVAR, REACTIVAR, CANCELAR, AUTORIZAR...
    tabla                   VARCHAR(40)  NOT NULL,
    idRegistro              INT UNSIGNED NULL,
    detalle                 JSON         NULL,       -- antes / después
    fecha                   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_idTrabajador)         REFERENCES trabajadores (idTrabajador),
    FOREIGN KEY (fk_idTrabajadorAutoriza) REFERENCES trabajadores (idTrabajador),
    KEY bitacora_registro (tabla, idRegistro)
) ENGINE=InnoDB;

-- =====================================================================
-- DATOS INICIALES (catálogos; ningún producto, cliente ni usuario)
-- =====================================================================
INSERT INTO roles (nombre, descripcion) VALUES
    ('ADMINISTRADOR', 'Acceso total'),
    ('GERENTE',       'Autoriza solicitudes, todo menos Configuración'),
    ('CAJERO',        'Cobra ventas'),
    ('VENDEDOR',      'Pedidos y cotizaciones'),
    ('ALMACENISTA',   'Inventario e incidencias');

INSERT INTO permisos (nombre, descripcion) VALUES
    ('ventas.registrar',        'Registrar ventas'),
    ('ventas.consultar',        'Consultar ventas'),
    ('ventas.cancelar',         'Cancelar ventas'),
    ('ventas.solicitarEdicion', 'Pedir que se edite una venta'),
    ('pagos.registrar',         'Registrar pagos y abonos'),
    ('pedidos.gestionar',       'Registrar y editar pedidos y cotizaciones'),
    ('clientes.gestionar',      'Registrar y editar clientes'),
    ('clientes.desactivar',     'Desactivar clientes'),
    ('productos.consultar',     'Consultar productos'),
    ('productos.gestionar',     'Registrar y editar productos'),
    ('productos.desactivar',    'Desactivar productos'),
    ('inventario.ajustar',      'Registrar ajustes de stock'),
    ('incidencias.reportar',    'Reportar incidencias'),
    ('solicitudes.autorizar',   'Aceptar o rechazar solicitudes'),
    ('usuarios.gestionar',      'Usuarios y roles'),
    ('configuracion.editar',    'Configuración de la tienda'),
    ('reportes.ver',            'Inicio y reportes');

-- El administrador tiene todos los permisos.
-- Los de los otros roles se asignan cuando el equipo los defina (decisión 2).
INSERT INTO rolPermisos (fk_idRol, fk_idPermiso)
    SELECT r.idRol, p.idPermiso FROM roles r CROSS JOIN permisos p
    WHERE r.nombre = 'ADMINISTRADOR';

-- Lugar por defecto para productos nuevos
INSERT INTO ubicaciones (codigoUbicacion, descripcion) VALUES ('SIN-ASIGNAR', 'Sin asignar');

INSERT INTO metodosPago (nombre) VALUES ('Efectivo'), ('Tarjeta'), ('Transferencia');

INSERT INTO motivos (categoriaMotivo, nombre) VALUES
    ('AJUSTE',       'Llegada de mercancía'),
    ('AJUSTE',       'Conteo físico'),
    ('AJUSTE',       'Merma'),
    ('AJUSTE',       'Uso interno'),
    ('CANCELACION',  'Error de captura'),
    ('CANCELACION',  'El cliente ya no la quiere'),
    ('DESACTIVACION','Producto descontinuado'),
    ('DESACTIVACION','Registro duplicado'),
    ('INCIDENCIA',   'Producto dañado'),
    ('INCIDENCIA',   'Producto defectuoso'),
    ('INCIDENCIA',   'Envase con fuga'),
    ('INCIDENCIA',   'Producto incorrecto'),
    ('INCIDENCIA',   'Accesorio faltante'),
    ('INCIDENCIA',   'Etiqueta ilegible'),
    ('INCIDENCIA',   'Diferencia de inventario'),
    ('DEVOLUCION',   'No es compatible con el vehículo'),
    ('DEVOLUCION',   'Llegó dañado');

-- Formato de la venta: el admin lo cambia en Configuración (decisión 8):
--   'VENTA-{AAAA}-{0000}'  ·  'V-{AAAA}-{0000}'  ·  'Venta #{0000}'
INSERT INTO folios (tipoDocumento, prefijo, formato) VALUES
    ('VENTA',      'V',   'V-{AAAA}-{0000}'),
    ('PEDIDO',     'P',   'P-{AAAA}-{0000}'),
    ('COTIZACION', 'COT', 'COT-{AAAA}-{0000}'),
    ('AJUSTE',     'AJ',  'AJ-{AAAA}-{0000}'),
    ('SOLICITUD',  'SOL', 'SOL-{AAAA}-{0000}'),
    ('INCIDENCIA', 'INC', 'INC-{0000}');

INSERT INTO configuraciones (clave, valor, descripcion) VALUES
    -- Datos de la tienda (antes tabla datosNegocio)
    ('tienda.nombreComercial',  NULL,  'Nombre de la tienda'),
    ('tienda.razonSocial',      NULL,  'Razón social'),
    ('tienda.rfc',              NULL,  'RFC de la tienda'),
    ('tienda.regimenFiscal',    NULL,  'Régimen fiscal'),
    ('tienda.telefono',         NULL,  'Teléfono'),
    ('tienda.correo',           NULL,  'Correo'),
    ('tienda.direccion',        NULL,  'Dirección'),
    ('tienda.logo',             NULL,  'Ruta del logo (PNG/JPG hasta 2 MB)'),
    ('tienda.textoComprobante', NULL,  'Texto al pie del comprobante'),
    -- Inventario y ventas
    ('iva',                     '16',  'IVA general en %'),
    ('stockMinimoDefecto',      '10',  'Para productos sin stock mínimo propio'),
    ('monedaBase',              'MXN', 'Moneda de precios y totales'),
    -- Notificaciones y seguridad
    ('alertasStockBajo',        '1',   '1 = activadas'),
    ('notificacionesCorreo',    '0',   '1 = activadas (requiere servidor de correo)'),
    ('correoNotificaciones',    NULL,  'A dónde llegan los avisos'),
    ('minutosCierreSesion',     '15',  'Cierre de sesión automático');

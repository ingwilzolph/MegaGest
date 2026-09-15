-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql-alianzapro.alwaysdata.net
-- Generation Time: Sep 15, 2026 at 02:47 AM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 8.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alianzapro_alianzapro`
--

-- --------------------------------------------------------

--
-- Table structure for table `ajustes_remuneraciones`
--

DROP TABLE IF EXISTS `ajustes_remuneraciones`;
CREATE TABLE `ajustes_remuneraciones` (
  `id_ajuste` bigint(20) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_comision` bigint(20) DEFAULT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_reembolso` int(11) DEFAULT NULL,
  `id_liquidacion` bigint(20) DEFAULT NULL,
  `tipo_ajuste` enum('bono','descuento') NOT NULL,
  `origen` enum('reembolso','administrativo') NOT NULL,
  `monto` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('pendiente','aplicado','anulado') NOT NULL DEFAULT 'pendiente',
  `clave_operacion` varchar(150) NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `visible_tienda` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL,
  `numeroReserva` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(30) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `patente` varchar(20) NOT NULL,
  `vehiculo` varchar(100) DEFAULT NULL,
  `servicio` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `comentario` text DEFAULT NULL,
  `estado` enum('Pendiente','Confirmada','En proceso','Finalizada','Cancelada') DEFAULT 'Pendiente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `rut` varchar(12) DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellido` varchar(60) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
  `correo_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `codigos_verificacion`
--

DROP TABLE IF EXISTS `codigos_verificacion`;
CREATE TABLE `codigos_verificacion` (
  `id_codigo` int(11) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `codigo_hash` varchar(255) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `expiracion` datetime NOT NULL,
  `intentos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `usado` tinyint(1) DEFAULT 0,
  `token_recuperacion_hash` char(64) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `codigos_verificacion_cliente`
--

DROP TABLE IF EXISTS `codigos_verificacion_cliente`;
CREATE TABLE `codigos_verificacion_cliente` (
  `id_codigo` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `codigo_hash` varchar(255) NOT NULL,
  `expiracion` datetime NOT NULL,
  `intentos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `token_recuperacion_hash` char(64) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comisiones_usuarios`
--

DROP TABLE IF EXISTS `comisiones_usuarios`;
CREATE TABLE `comisiones_usuarios` (
  `id_comision` bigint(20) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_comision` enum('venta','servicio','mano_obra') NOT NULL,
  `origen` enum('venta_presencial','venta_online','orden_trabajo') NOT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_ot` int(11) DEFAULT NULL,
  `id_servicio_ot` int(11) DEFAULT NULL,
  `id_mano_obra` int(11) DEFAULT NULL,
  `base_calculo` bigint(20) UNSIGNED NOT NULL,
  `porcentaje_aplicado` decimal(5,2) UNSIGNED NOT NULL,
  `monto_comision` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('pendiente','liquidada','pagada','anulada') NOT NULL DEFAULT 'pendiente',
  `clave_operacion` varchar(150) NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `configuracion_remuneraciones`
--

DROP TABLE IF EXISTS `configuracion_remuneraciones`;
CREATE TABLE `configuracion_remuneraciones` (
  `id_configuracion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `sueldo_base_mensual` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `porcentaje_ventas` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `porcentaje_servicios` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `porcentaje_mano_obra` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `id_usuario_actualizacion` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_entrada_inventario`
--

DROP TABLE IF EXISTS `detalle_entrada_inventario`;
CREATE TABLE `detalle_entrada_inventario` (
  `id_detalle_entrada` int(11) NOT NULL,
  `id_entrada` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` bigint(20) NOT NULL DEFAULT 0,
  `total_linea` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_liquidacion_comisiones`
--

DROP TABLE IF EXISTS `detalle_liquidacion_comisiones`;
CREATE TABLE `detalle_liquidacion_comisiones` (
  `id_detalle` bigint(20) NOT NULL,
  `id_liquidacion` bigint(20) NOT NULL,
  `id_comision` bigint(20) NOT NULL,
  `monto_comision` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `nombre_producto` varchar(100) NOT NULL,
  `marca_producto` varchar(60) DEFAULT NULL,
  `precio_unitario` bigint(20) NOT NULL DEFAULT 0,
  `costo_unitario` bigint(20) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `total_linea` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `direcciones_cliente`
--

DROP TABLE IF EXISTS `direcciones_cliente`;
CREATE TABLE `direcciones_cliente` (
  `id_direccion` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `nombre_direccion` varchar(50) NOT NULL DEFAULT 'Dirección principal',
  `region` varchar(100) NOT NULL,
  `comuna` varchar(100) NOT NULL,
  `calle` varchar(150) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `departamento` varchar(30) DEFAULT NULL,
  `referencia` varchar(250) DEFAULT NULL,
  `codigo_postal` varchar(15) DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documentos_impresion`
--

DROP TABLE IF EXISTS `documentos_impresion`;
CREATE TABLE `documentos_impresion` (
  `id_documento` bigint(20) UNSIGNED NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `tipo` enum('cotizacion','venta') NOT NULL,
  `numero_documento` varchar(30) NOT NULL,
  `contenido` longtext NOT NULL,
  `fecha_emision` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `entradas_inventario`
--

DROP TABLE IF EXISTS `entradas_inventario`;
CREATE TABLE `entradas_inventario` (
  `id_entrada` int(11) NOT NULL,
  `numero_entrada` varchar(30) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_documento` enum('factura','boleta','guia_despacho','sin_documento') NOT NULL DEFAULT 'sin_documento',
  `numero_documento` varchar(50) DEFAULT NULL,
  `fecha_documento` date DEFAULT NULL,
  `neto` bigint(20) NOT NULL DEFAULT 0,
  `iva` bigint(20) NOT NULL DEFAULT 0,
  `total` bigint(20) NOT NULL DEFAULT 0,
  `estado` enum('borrador','confirmada','anulada') NOT NULL DEFAULT 'borrador',
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_confirmacion` datetime DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fotos_ot`
--

DROP TABLE IF EXISTS `fotos_ot`;
CREATE TABLE `fotos_ot` (
  `id_foto` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `nombreArchivo` varchar(255) NOT NULL,
  `fechaSubida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial_liquidaciones`
--

DROP TABLE IF EXISTS `historial_liquidaciones`;
CREATE TABLE `historial_liquidaciones` (
  `id_historial` bigint(20) NOT NULL,
  `id_liquidacion` bigint(20) NOT NULL,
  `id_usuario_responsable` int(11) NOT NULL,
  `accion` enum('creada','cerrada','pagada','anulada') NOT NULL,
  `estado_anterior` enum('borrador','cerrada','pagada','anulada') DEFAULT NULL,
  `estado_nuevo` enum('borrador','cerrada','pagada','anulada') NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_evento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `liquidaciones_remuneraciones`
--

DROP TABLE IF EXISTS `liquidaciones_remuneraciones`;
CREATE TABLE `liquidaciones_remuneraciones` (
  `id_liquidacion` bigint(20) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `periodo_desde` date NOT NULL,
  `periodo_hasta` date NOT NULL,
  `sueldo_base` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_comisiones` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `bonos` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `descuentos` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_liquidacion` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `estado` enum('borrador','cerrada','pagada','anulada') NOT NULL DEFAULT 'borrador',
  `id_usuario_responsable` int(11) NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_admin`
--

DROP TABLE IF EXISTS `login_admin`;
CREATE TABLE `login_admin` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` varchar(50) NOT NULL,
  `fechaRegistro` datetime NOT NULL,
  `estado` varchar(20) NOT NULL,
  `version_sesion` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mano_obra_ot`
--

DROP TABLE IF EXISTS `mano_obra_ot`;
CREATE TABLE `mano_obra_ot` (
  `id_mano_obra` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `descripcion` varchar(200) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio` int(11) NOT NULL,
  `costo_unitario` bigint(20) DEFAULT NULL,
  `costo_total` bigint(20) DEFAULT NULL,
  `total` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` bigint(20) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_entrada` int(11) DEFAULT NULL,
  `id_ot` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('saldo_inicial','entrada_compra','salida_venta','devolucion_cliente','ajuste_entrada','ajuste_salida','merma','salida_ot','devolucion_ot') NOT NULL,
  `sentido` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_resultante` int(11) NOT NULL,
  `costo_unitario` bigint(20) DEFAULT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `clave_operacion` varchar(150) DEFAULT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orden_trabajo`
--

DROP TABLE IF EXISTS `orden_trabajo`;
CREATE TABLE `orden_trabajo` (
  `id_ot` int(11) NOT NULL,
  `numeroOT` varchar(30) DEFAULT NULL,
  `id_cita` int(11) NOT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `fechaFin` datetime DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `kilometraje` int(11) DEFAULT NULL,
  `combustible` enum('Vacío','1/4','1/2','3/4','Lleno') DEFAULT NULL,
  `observacionesRecepcion` text DEFAULT NULL,
  `observacionesEntrega` text DEFAULT NULL,
  `estado` enum('Abierta','En proceso','Terminada','Facturada') DEFAULT 'Abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orden_trabajo_productos`
--

DROP TABLE IF EXISTS `orden_trabajo_productos`;
CREATE TABLE `orden_trabajo_productos` (
  `id_producto_ot` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `descripcion` varchar(200) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio` int(11) NOT NULL,
  `costo_unitario` bigint(20) DEFAULT NULL,
  `total` int(11) NOT NULL,
  `costo_total` bigint(20) DEFAULT NULL,
  `stock_descontado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_descuento_stock` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orden_trabajo_servicios`
--

DROP TABLE IF EXISTS `orden_trabajo_servicios`;
CREATE TABLE `orden_trabajo_servicios` (
  `id_servicio_ot` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `descripcion` varchar(200) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio` int(11) NOT NULL,
  `costo_unitario` bigint(20) DEFAULT NULL,
  `costo_total` bigint(20) DEFAULT NULL,
  `total` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `proveedor` enum('webpay','efectivo','transferencia','debito','credito') NOT NULL,
  `buy_order` varchar(50) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `token_ws` varchar(150) DEFAULT NULL,
  `monto` bigint(20) NOT NULL DEFAULT 0,
  `estado` enum('iniciado','aprobado','rechazado','anulado','reembolso_pendiente','reembolsado') NOT NULL DEFAULT 'iniciado',
  `response_code` int(11) DEFAULT NULL,
  `authorization_code` varchar(50) DEFAULT NULL,
  `payment_type_code` varchar(10) DEFAULT NULL,
  `installments_number` int(11) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `respuesta_proveedor` longtext DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos_orden_trabajo`
--

DROP TABLE IF EXISTS `pagos_orden_trabajo`;
CREATE TABLE `pagos_orden_trabajo` (
  `id_pago_ot` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `metodo_pago` enum('efectivo','debito','credito','transferencia') NOT NULL,
  `subtotal_servicios` bigint(20) NOT NULL DEFAULT 0,
  `subtotal_productos` bigint(20) NOT NULL DEFAULT 0,
  `subtotal_mano_obra` bigint(20) NOT NULL DEFAULT 0,
  `total` bigint(20) NOT NULL DEFAULT 0,
  `estado` enum('aprobado','anulado') NOT NULL DEFAULT 'aprobado',
  `referencia` varchar(50) NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `numero_pedido` varchar(30) NOT NULL,
  `numero_cotizacion` varchar(30) DEFAULT NULL,
  `fecha_cotizacion` datetime DEFAULT NULL,
  `fecha_expiracion_cotizacion` datetime DEFAULT NULL,
  `ajustes_aceptados` tinyint(1) NOT NULL DEFAULT 0,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_usuario_confirmacion` int(11) DEFAULT NULL,
  `canal` enum('online','presencial') NOT NULL DEFAULT 'online',
  `tipo_entrega` enum('retiro','despacho') NOT NULL DEFAULT 'retiro',
  `region` varchar(100) DEFAULT NULL,
  `comuna` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `referencia_direccion` varchar(200) DEFAULT NULL,
  `neto` bigint(20) NOT NULL DEFAULT 0,
  `iva` bigint(20) NOT NULL DEFAULT 0,
  `subtotal_productos` bigint(20) NOT NULL DEFAULT 0,
  `costo_despacho` bigint(20) NOT NULL DEFAULT 0,
  `id_tarifa_despacho` int(11) DEFAULT NULL,
  `total` bigint(20) NOT NULL DEFAULT 0,
  `estado` enum('pendiente_pago','pagado','preparando','listo_retiro','enviado','entregado','cancelacion_solicitada','cancelado','cotizacion') NOT NULL DEFAULT 'pendiente_pago',
  `estado_pago` enum('pendiente','aprobado','rechazado','anulado','reembolso_pendiente','reembolsado') NOT NULL DEFAULT 'pendiente',
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `control_stock` enum('desconocido','no_descontado','descontado','reintegrado') NOT NULL DEFAULT 'desconocido',
  `estado_antes_cancelacion` varchar(50) DEFAULT NULL,
  `fecha_reintegro_stock` datetime DEFAULT NULL,
  `id_usuario_reintegro_stock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `categoria` varchar(50) NOT NULL,
  `nombre` varchar(60) DEFAULT NULL,
  `descripcion` varchar(200) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `cantidad` int(5) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `visible_tienda` tinyint(1) NOT NULL DEFAULT 1,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `compra` int(10) DEFAULT 0,
  `precio` int(10) NOT NULL DEFAULT 0,
  `precio_oferta` int(11) DEFAULT NULL,
  `inicio_oferta` datetime DEFAULT NULL,
  `fin_oferta` datetime DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `garantia` varchar(100) DEFAULT NULL,
  `compatibilidad` text DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `rut` varchar(12) DEFAULT NULL,
  `razon_social` varchar(120) NOT NULL,
  `nombre_contacto` varchar(100) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reembolsos`
--

DROP TABLE IF EXISTS `reembolsos`;
CREATE TABLE `reembolsos` (
  `id_reembolso` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `token_ws` varchar(150) DEFAULT NULL,
  `monto` int(10) UNSIGNED NOT NULL,
  `motivo` varchar(500) NOT NULL,
  `estado_pedido_anterior` varchar(50) DEFAULT NULL,
  `estado_pago_anterior` varchar(50) DEFAULT NULL,
  `estado` enum('pendiente','procesando','reembolsado','error') NOT NULL DEFAULT 'pendiente',
  `respuesta_transbank` longtext DEFAULT NULL,
  `mensaje_error` varchar(500) DEFAULT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_procesamiento` datetime DEFAULT NULL,
  `id_usuario_reembolso` int(11) DEFAULT NULL,
  `medio_devolucion` varchar(30) DEFAULT NULL,
  `referencia_devolucion` varchar(150) DEFAULT NULL,
  `clave_operacion_manual` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
CREATE TABLE `servicios` (
  `id_servicio` int(6) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(600) NOT NULL,
  `precio_min` int(11) NOT NULL,
  `duracion_minutos` int(11) NOT NULL DEFAULT 60,
  `visible_citas` tinyint(1) NOT NULL DEFAULT 1,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tarifas_despacho`
--

DROP TABLE IF EXISTS `tarifas_despacho`;
CREATE TABLE `tarifas_despacho` (
  `id_tarifa` int(11) NOT NULL,
  `region` varchar(100) NOT NULL,
  `comuna` varchar(100) NOT NULL DEFAULT '',
  `costo` int(10) UNSIGNED NOT NULL,
  `plazo_minimo_dias` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `plazo_maximo_dias` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ajustes_remuneraciones`
--
ALTER TABLE `ajustes_remuneraciones`
  ADD PRIMARY KEY (`id_ajuste`),
  ADD UNIQUE KEY `uk_ajuste_clave_operacion` (`clave_operacion`),
  ADD KEY `idx_ajuste_usuario_estado` (`id_usuario`,`estado`),
  ADD KEY `idx_ajuste_comision` (`id_comision`),
  ADD KEY `idx_ajuste_pedido` (`id_pedido`),
  ADD KEY `idx_ajuste_reembolso` (`id_reembolso`),
  ADD KEY `idx_ajuste_liquidacion` (`id_liquidacion`);

--
-- Indexes for table `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`categoria`),
  ADD UNIQUE KEY `id_categoria` (`id_categoria`),
  ADD UNIQUE KEY `uk_categoria_slug` (`slug`);

--
-- Indexes for table `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `uk_clientes_correo` (`correo`),
  ADD UNIQUE KEY `uk_clientes_rut` (`rut`),
  ADD KEY `idx_clientes_nombre` (`nombre`,`apellido`),
  ADD KEY `idx_clientes_telefono` (`telefono`);

--
-- Indexes for table `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  ADD PRIMARY KEY (`id_codigo`),
  ADD KEY `idx_codigo_admin_correo` (`correo`,`usado`,`expiracion`);

--
-- Indexes for table `codigos_verificacion_cliente`
--
ALTER TABLE `codigos_verificacion_cliente`
  ADD PRIMARY KEY (`id_codigo`),
  ADD KEY `fk_codigo_cliente` (`id_cliente`),
  ADD KEY `idx_codigo_cliente_correo` (`correo`,`usado`,`expiracion`);

--
-- Indexes for table `comisiones_usuarios`
--
ALTER TABLE `comisiones_usuarios`
  ADD PRIMARY KEY (`id_comision`),
  ADD UNIQUE KEY `uk_comision_clave_operacion` (`clave_operacion`),
  ADD KEY `idx_comision_usuario_fecha` (`id_usuario`,`fecha_generacion`),
  ADD KEY `idx_comision_estado` (`estado`),
  ADD KEY `idx_comision_pedido` (`id_pedido`),
  ADD KEY `idx_comision_ot` (`id_ot`),
  ADD KEY `idx_comision_servicio_ot` (`id_servicio_ot`),
  ADD KEY `idx_comision_mano_obra` (`id_mano_obra`);

--
-- Indexes for table `configuracion_remuneraciones`
--
ALTER TABLE `configuracion_remuneraciones`
  ADD PRIMARY KEY (`id_configuracion`),
  ADD UNIQUE KEY `uk_configuracion_remuneracion_usuario` (`id_usuario`),
  ADD KEY `idx_configuracion_remuneracion_estado` (`estado`),
  ADD KEY `idx_configuracion_actualizacion` (`id_usuario_actualizacion`);

--
-- Indexes for table `detalle_entrada_inventario`
--
ALTER TABLE `detalle_entrada_inventario`
  ADD PRIMARY KEY (`id_detalle_entrada`),
  ADD UNIQUE KEY `uk_entrada_producto` (`id_entrada`,`id_producto`),
  ADD KEY `idx_detalle_entrada_producto` (`id_producto`);

--
-- Indexes for table `detalle_liquidacion_comisiones`
--
ALTER TABLE `detalle_liquidacion_comisiones`
  ADD PRIMARY KEY (`id_detalle`),
  ADD UNIQUE KEY `uk_detalle_comision` (`id_comision`),
  ADD KEY `idx_detalle_liquidacion` (`id_liquidacion`);

--
-- Indexes for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD UNIQUE KEY `uk_detalle_pedido_producto` (`id_pedido`,`id_producto`),
  ADD KEY `idx_detalle_pedido` (`id_pedido`),
  ADD KEY `idx_detalle_producto` (`id_producto`);

--
-- Indexes for table `direcciones_cliente`
--
ALTER TABLE `direcciones_cliente`
  ADD PRIMARY KEY (`id_direccion`),
  ADD KEY `idx_direccion_cliente` (`id_cliente`),
  ADD KEY `idx_direccion_region_comuna` (`region`,`comuna`);

--
-- Indexes for table `documentos_impresion`
--
ALTER TABLE `documentos_impresion`
  ADD PRIMARY KEY (`id_documento`),
  ADD UNIQUE KEY `documento_por_pedido` (`id_pedido`,`tipo`),
  ADD KEY `buscar_numero_documento` (`numero_documento`);

--
-- Indexes for table `entradas_inventario`
--
ALTER TABLE `entradas_inventario`
  ADD PRIMARY KEY (`id_entrada`),
  ADD UNIQUE KEY `uk_entrada_numero` (`numero_entrada`),
  ADD UNIQUE KEY `uk_entrada_documento` (`id_proveedor`,`tipo_documento`,`numero_documento`),
  ADD KEY `idx_entrada_proveedor` (`id_proveedor`),
  ADD KEY `idx_entrada_usuario` (`id_usuario`),
  ADD KEY `idx_entrada_estado` (`estado`),
  ADD KEY `idx_entrada_fecha` (`fecha_registro`);

--
-- Indexes for table `fotos_ot`
--
ALTER TABLE `fotos_ot`
  ADD PRIMARY KEY (`id_foto`),
  ADD KEY `id_ot` (`id_ot`);

--
-- Indexes for table `historial_liquidaciones`
--
ALTER TABLE `historial_liquidaciones`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `idx_historial_liquidacion` (`id_liquidacion`,`fecha_evento`),
  ADD KEY `idx_historial_responsable` (`id_usuario_responsable`);

--
-- Indexes for table `liquidaciones_remuneraciones`
--
ALTER TABLE `liquidaciones_remuneraciones`
  ADD PRIMARY KEY (`id_liquidacion`),
  ADD KEY `idx_liquidacion_estado` (`estado`),
  ADD KEY `idx_liquidacion_periodo` (`periodo_desde`,`periodo_hasta`),
  ADD KEY `idx_liquidacion_responsable` (`id_usuario_responsable`),
  ADD KEY `idx_liquidacion_usuario_periodo` (`id_usuario`,`periodo_desde`,`periodo_hasta`);

--
-- Indexes for table `login_admin`
--
ALTER TABLE `login_admin`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indexes for table `mano_obra_ot`
--
ALTER TABLE `mano_obra_ot`
  ADD PRIMARY KEY (`id_mano_obra`),
  ADD KEY `id_ot` (`id_ot`),
  ADD KEY `fk_mano_obra_usuario` (`id_usuario`);

--
-- Indexes for table `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD UNIQUE KEY `uk_movimiento_operacion` (`clave_operacion`),
  ADD KEY `idx_movimiento_producto` (`id_producto`),
  ADD KEY `idx_movimiento_usuario` (`id_usuario`),
  ADD KEY `idx_movimiento_pedido` (`id_pedido`),
  ADD KEY `idx_movimiento_entrada` (`id_entrada`),
  ADD KEY `idx_movimiento_tipo` (`tipo_movimiento`),
  ADD KEY `idx_movimiento_fecha` (`fecha_movimiento`),
  ADD KEY `idx_movimiento_ot` (`id_ot`);

--
-- Indexes for table `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  ADD PRIMARY KEY (`id_ot`),
  ADD UNIQUE KEY `numeroOT` (`numeroOT`),
  ADD KEY `id_cita` (`id_cita`),
  ADD KEY `fk_ot_usuario` (`id_usuario`);

--
-- Indexes for table `orden_trabajo_productos`
--
ALTER TABLE `orden_trabajo_productos`
  ADD PRIMARY KEY (`id_producto_ot`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `fk_producto_ot_usuario` (`id_usuario`),
  ADD KEY `idx_ot_producto_stock` (`id_ot`,`stock_descontado`);

--
-- Indexes for table `orden_trabajo_servicios`
--
ALTER TABLE `orden_trabajo_servicios`
  ADD PRIMARY KEY (`id_servicio_ot`),
  ADD KEY `id_ot` (`id_ot`),
  ADD KEY `fk_servicio_ot_usuario` (`id_usuario`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `uk_pagos_buy_order` (`buy_order`),
  ADD UNIQUE KEY `uk_pagos_token` (`token_ws`),
  ADD KEY `idx_pagos_pedido` (`id_pedido`),
  ADD KEY `idx_pagos_estado` (`estado`);

--
-- Indexes for table `pagos_orden_trabajo`
--
ALTER TABLE `pagos_orden_trabajo`
  ADD PRIMARY KEY (`id_pago_ot`),
  ADD UNIQUE KEY `uk_pago_ot` (`id_ot`),
  ADD UNIQUE KEY `uk_pago_ot_referencia` (`referencia`),
  ADD KEY `idx_pago_ot_usuario` (`id_usuario`),
  ADD KEY `idx_pago_ot_fecha` (`fecha_pago`);

--
-- Indexes for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD UNIQUE KEY `uk_pedidos_numero` (`numero_pedido`),
  ADD UNIQUE KEY `uk_pedidos_numero_cotizacion` (`numero_cotizacion`),
  ADD KEY `idx_pedidos_cliente` (`id_cliente`),
  ADD KEY `idx_pedidos_usuario` (`id_usuario`),
  ADD KEY `idx_pedidos_estado` (`estado`),
  ADD KEY `idx_pedidos_estado_pago` (`estado_pago`),
  ADD KEY `idx_pedidos_fecha` (`fecha_pedido`),
  ADD KEY `fk_pedido_usuario_confirmacion` (`id_usuario_confirmacion`),
  ADD KEY `fk_pedido_tarifa_despacho` (`id_tarifa_despacho`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `uk_productos_sku` (`sku`),
  ADD KEY `categoria` (`categoria`);

--
-- Indexes for table `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `uk_proveedor_rut` (`rut`),
  ADD KEY `idx_proveedor_razon_social` (`razon_social`),
  ADD KEY `idx_proveedor_estado` (`estado`);

--
-- Indexes for table `reembolsos`
--
ALTER TABLE `reembolsos`
  ADD PRIMARY KEY (`id_reembolso`),
  ADD UNIQUE KEY `uq_reembolso_operacion_manual` (`clave_operacion_manual`),
  ADD KEY `idx_reembolso_pedido` (`id_pedido`),
  ADD KEY `idx_reembolso_pago` (`id_pago`),
  ADD KEY `idx_reembolso_estado` (`estado`);

--
-- Indexes for table `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id_servicio`);

--
-- Indexes for table `tarifas_despacho`
--
ALTER TABLE `tarifas_despacho`
  ADD PRIMARY KEY (`id_tarifa`),
  ADD UNIQUE KEY `uk_tarifa_region_comuna` (`region`,`comuna`),
  ADD KEY `idx_tarifa_busqueda` (`region`,`comuna`,`activa`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ajustes_remuneraciones`
--
ALTER TABLE `ajustes_remuneraciones`
  MODIFY `id_ajuste` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  MODIFY `id_codigo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `codigos_verificacion_cliente`
--
ALTER TABLE `codigos_verificacion_cliente`
  MODIFY `id_codigo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comisiones_usuarios`
--
ALTER TABLE `comisiones_usuarios`
  MODIFY `id_comision` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `configuracion_remuneraciones`
--
ALTER TABLE `configuracion_remuneraciones`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_entrada_inventario`
--
ALTER TABLE `detalle_entrada_inventario`
  MODIFY `id_detalle_entrada` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_liquidacion_comisiones`
--
ALTER TABLE `detalle_liquidacion_comisiones`
  MODIFY `id_detalle` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `direcciones_cliente`
--
ALTER TABLE `direcciones_cliente`
  MODIFY `id_direccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documentos_impresion`
--
ALTER TABLE `documentos_impresion`
  MODIFY `id_documento` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `entradas_inventario`
--
ALTER TABLE `entradas_inventario`
  MODIFY `id_entrada` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fotos_ot`
--
ALTER TABLE `fotos_ot`
  MODIFY `id_foto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historial_liquidaciones`
--
ALTER TABLE `historial_liquidaciones`
  MODIFY `id_historial` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `liquidaciones_remuneraciones`
--
ALTER TABLE `liquidaciones_remuneraciones`
  MODIFY `id_liquidacion` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_admin`
--
ALTER TABLE `login_admin`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mano_obra_ot`
--
ALTER TABLE `mano_obra_ot`
  MODIFY `id_mano_obra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id_movimiento` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  MODIFY `id_ot` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orden_trabajo_productos`
--
ALTER TABLE `orden_trabajo_productos`
  MODIFY `id_producto_ot` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orden_trabajo_servicios`
--
ALTER TABLE `orden_trabajo_servicios`
  MODIFY `id_servicio_ot` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pagos_orden_trabajo`
--
ALTER TABLE `pagos_orden_trabajo`
  MODIFY `id_pago_ot` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reembolsos`
--
ALTER TABLE `reembolsos`
  MODIFY `id_reembolso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id_servicio` int(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tarifas_despacho`
--
ALTER TABLE `tarifas_despacho`
  MODIFY `id_tarifa` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ajustes_remuneraciones`
--
ALTER TABLE `ajustes_remuneraciones`
  ADD CONSTRAINT `fk_ajuste_comision` FOREIGN KEY (`id_comision`) REFERENCES `comisiones_usuarios` (`id_comision`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ajuste_liquidacion` FOREIGN KEY (`id_liquidacion`) REFERENCES `liquidaciones_remuneraciones` (`id_liquidacion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ajuste_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ajuste_reembolso` FOREIGN KEY (`id_reembolso`) REFERENCES `reembolsos` (`id_reembolso`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ajuste_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE;

--
-- Constraints for table `codigos_verificacion_cliente`
--
ALTER TABLE `codigos_verificacion_cliente`
  ADD CONSTRAINT `fk_codigo_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comisiones_usuarios`
--
ALTER TABLE `comisiones_usuarios`
  ADD CONSTRAINT `fk_comision_mano_obra` FOREIGN KEY (`id_mano_obra`) REFERENCES `mano_obra_ot` (`id_mano_obra`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comision_ot` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comision_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comision_servicio_ot` FOREIGN KEY (`id_servicio_ot`) REFERENCES `orden_trabajo_servicios` (`id_servicio_ot`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comision_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE;

--
-- Constraints for table `configuracion_remuneraciones`
--
ALTER TABLE `configuracion_remuneraciones`
  ADD CONSTRAINT `fk_configuracion_remuneracion_responsable` FOREIGN KEY (`id_usuario_actualizacion`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_configuracion_remuneracion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE;

--
-- Constraints for table `detalle_entrada_inventario`
--
ALTER TABLE `detalle_entrada_inventario`
  ADD CONSTRAINT `fk_detalle_entrada` FOREIGN KEY (`id_entrada`) REFERENCES `entradas_inventario` (`id_entrada`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_entrada_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

--
-- Constraints for table `detalle_liquidacion_comisiones`
--
ALTER TABLE `detalle_liquidacion_comisiones`
  ADD CONSTRAINT `fk_detalle_comision` FOREIGN KEY (`id_comision`) REFERENCES `comisiones_usuarios` (`id_comision`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_liquidacion` FOREIGN KEY (`id_liquidacion`) REFERENCES `liquidaciones_remuneraciones` (`id_liquidacion`) ON UPDATE CASCADE;

--
-- Constraints for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `direcciones_cliente`
--
ALTER TABLE `direcciones_cliente`
  ADD CONSTRAINT `fk_direccion_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `entradas_inventario`
--
ALTER TABLE `entradas_inventario`
  ADD CONSTRAINT `fk_entrada_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entrada_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE;

--
-- Constraints for table `fotos_ot`
--
ALTER TABLE `fotos_ot`
  ADD CONSTRAINT `fotos_ot_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`) ON DELETE CASCADE;

--
-- Constraints for table `historial_liquidaciones`
--
ALTER TABLE `historial_liquidaciones`
  ADD CONSTRAINT `fk_historial_liquidacion` FOREIGN KEY (`id_liquidacion`) REFERENCES `liquidaciones_remuneraciones` (`id_liquidacion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_liquidacion_responsable` FOREIGN KEY (`id_usuario_responsable`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE;

--
-- Constraints for table `liquidaciones_remuneraciones`
--
ALTER TABLE `liquidaciones_remuneraciones`
  ADD CONSTRAINT `fk_liquidacion_responsable` FOREIGN KEY (`id_usuario_responsable`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_liquidacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON UPDATE CASCADE;

--
-- Constraints for table `mano_obra_ot`
--
ALTER TABLE `mano_obra_ot`
  ADD CONSTRAINT `fk_mano_obra_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`),
  ADD CONSTRAINT `mano_obra_ot_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`);

--
-- Constraints for table `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `fk_movimiento_entrada` FOREIGN KEY (`id_entrada`) REFERENCES `entradas_inventario` (`id_entrada`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_inventario_ot` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  ADD CONSTRAINT `fk_ot_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`),
  ADD CONSTRAINT `orden_trabajo_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`);

--
-- Constraints for table `orden_trabajo_productos`
--
ALTER TABLE `orden_trabajo_productos`
  ADD CONSTRAINT `fk_producto_ot_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`),
  ADD CONSTRAINT `orden_trabajo_productos_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`),
  ADD CONSTRAINT `orden_trabajo_productos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Constraints for table `orden_trabajo_servicios`
--
ALTER TABLE `orden_trabajo_servicios`
  ADD CONSTRAINT `fk_servicio_ot_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`),
  ADD CONSTRAINT `orden_trabajo_servicios_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`) ON DELETE CASCADE;

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pagos_orden_trabajo`
--
ALTER TABLE `pagos_orden_trabajo`
  ADD CONSTRAINT `fk_pago_ot_orden` FOREIGN KEY (`id_ot`) REFERENCES `orden_trabajo` (`id_ot`),
  ADD CONSTRAINT `fk_pago_ot_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`);

--
-- Constraints for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_tarifa_despacho` FOREIGN KEY (`id_tarifa_despacho`) REFERENCES `tarifas_despacho` (`id_tarifa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_usuario_confirmacion` FOREIGN KEY (`id_usuario_confirmacion`) REFERENCES `login_admin` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `login_admin` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria`) REFERENCES `categorias` (`categoria`);

--
-- Constraints for table `reembolsos`
--
ALTER TABLE `reembolsos`
  ADD CONSTRAINT `fk_reembolso_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reembolso_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

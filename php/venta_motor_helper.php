<?php
// Protocolo de revisión y confirmación. No es un endpoint independiente.
function ventaFallo(string $mensaje, int $estado = 400): void {
    http_response_code($estado);
    throw new RuntimeException($mensaje);
}

function ventaValidarSolicitud(array $datos): void {
    if (!in_array($datos['accion'] ?? '', ['revisar', 'confirmar'], true)) {
        ventaFallo('Actualice la pantalla de Ventas: falta la revisión previa.');
    }
    if (!is_array($datos['cliente'] ?? null) || !is_array($datos['productos'] ?? null)) {
        ventaFallo('Cliente o productos no válidos.');
    }
    if (!is_int($datos['cliente']['id_cliente'] ?? 0) || ($datos['cliente']['id_cliente'] ?? 0) < 0) ventaFallo('Identificador de cliente no válido.');
    foreach (['rut','nombre','apellido','correo','telefono'] as $campo) {
        if (!is_string($datos['cliente'][$campo] ?? null)) ventaFallo('Datos del cliente no válidos.');
    }
    if (!is_string($datos['metodo_pago'] ?? null) || !is_string($datos['observaciones'] ?? null)) {
        ventaFallo('Método de pago u observaciones no válidos.');
    }
    if (count($datos['productos']) < 1 || count($datos['productos']) > 100) ventaFallo('Incluya entre 1 y 100 líneas.');
    foreach ($datos['productos'] as $p) {
        if (!is_array($p) || !is_int($p['id_producto'] ?? null) || $p['id_producto'] <= 0 ||
            !is_int($p['cantidad'] ?? null) || $p['cantidad'] < 1 || $p['cantidad'] > 1000) {
            ventaFallo('Producto o cantidad no válidos.');
        }
    }
    if (!is_string($datos['operacion'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D', $datos['operacion'])) {
        ventaFallo('Identificador de operación no válido.');
    }
    if ($datos['accion'] === 'confirmar' &&
        (($datos['cobro_confirmado'] ?? false) !== true || !is_string($datos['token_revision'] ?? null))) {
        ventaFallo('Revise el importe y confirme que recibió y verificó el pago.');
    }
    if (!isset($_SESSION['clave_revision_venta'])) $_SESSION['clave_revision_venta'] = bin2hex(random_bytes(32));
}

function ventaValidarMotor(mysqli $db): void {
    $r=$db->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('pedidos','detalle_pedido','productos','pagos','clientes','login_admin','documentos_impresion')");
    $cantidad=0;
    while ($tabla=$r->fetch_assoc()) {
        if (strtolower((string)$tabla['ENGINE']) !== 'innodb') ventaFallo('No se puede registrar: las tablas de venta deben usar InnoDB.',409);
        $cantidad++;
    }
    if ($cantidad !== 7) ventaFallo('Faltan tablas de venta o documentos_impresion. Complete la instalación.',409);
    $r=$db->query("SELECT INDEX_NAME, COUNT(*) AS columnas, MAX(COLUMN_NAME) AS columna FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pagos' AND NON_UNIQUE=0 GROUP BY INDEX_NAME");
    $unico=false;
    while ($indice=$r->fetch_assoc()) if ((int)$indice['columnas']===1 && $indice['columna']==='buy_order') $unico=true;
    if (!$unico) ventaFallo('Falta el índice único de pagos.buy_order. No se habilitará el cobro.',409);
}

function ventaHash(array $datos): string {
    unset($datos['accion'], $datos['token_revision'], $datos['cobro_confirmado']);
    return hash('sha256', json_encode($datos, JSON_THROW_ON_ERROR));
}

function ventaReintento(mysqli $db, array $datos, int $usuario): ?array {
    if ($datos['accion'] !== 'confirmar') return null;
    $orden = 'VP-' . $datos['operacion'];
    $s = $db->prepare('SELECT p.id_pedido, p.numero_pedido, pa.respuesta_proveedor FROM pagos pa JOIN pedidos p ON p.id_pedido=pa.id_pedido WHERE pa.buy_order=? LIMIT 1 FOR UPDATE');
    $s->bind_param('s', $orden); $s->execute(); $fila = $s->get_result()->fetch_assoc(); $s->close();
    if (!$fila) return null;
    $original = json_decode($fila['respuesta_proveedor'], true, 512, JSON_THROW_ON_ERROR);
    if ((int)($original['id_usuario'] ?? 0) !== $usuario ||
        !hash_equals((string)($original['hash_solicitud'] ?? ''), ventaHash($datos))) {
        ventaFallo('El identificador ya corresponde a otra solicitud. No se registró otro cobro.', 409);
    }
    return ['ok'=>true, 'ya_confirmada'=>true, 'mensaje'=>'La venta ya estaba registrada. No se duplicó el pago ni el stock.',
        'datos'=>['id_pedido'=>(int)$fila['id_pedido'], 'numero_pedido'=>$fila['numero_pedido']]];
}

function ventaOrigen(mysqli $db, array $datos, bool $rutaCotizacion): ?array {
    $origen = $datos['origen'] ?? null;
    if ($origen === null) {
        if ($rutaCotizacion) ventaFallo('Debe indicar la cotización de origen.');
        return null;
    }
    if (!is_array($origen) || !in_array($origen['tipo'] ?? '', ['cotizacion','venta_similar'], true) ||
        !is_int($origen['id_pedido'] ?? null) || $origen['id_pedido'] <= 0) ventaFallo('Origen no válido.');
    if (($origen['tipo'] === 'cotizacion') !== $rutaCotizacion) ventaFallo('Ruta incorrecta para este documento.');
    $s = $db->prepare('SELECT *, (fecha_expiracion_cotizacion > NOW()) AS vigente FROM pedidos WHERE id_pedido=? LIMIT 1 FOR UPDATE');
    $s->bind_param('i', $origen['id_pedido']); $s->execute(); $p = $s->get_result()->fetch_assoc(); $s->close();
    if (!$p) ventaFallo('No se encontró el documento de origen.', 404);
    if (!$rutaCotizacion) {
        if ($p['estado_pago'] !== 'aprobado' || in_array($p['estado'], ['cotizacion','cancelado','cancelacion_solicitada'], true)) {
            ventaFallo('La venta de origen no está disponible para esta operación.', 409);
        }
        return $p;
    }
    if ($p['estado'] !== 'cotizacion' || $p['estado_pago'] !== 'pendiente') ventaFallo('La cotización ya no está pendiente. Actualice el listado.',409);
    if (!(int)$p['vigente']) ventaFallo('Cotización vencida o sin vencimiento válido. No puede cobrarse.',409);
    if ($p['canal'] !== 'presencial' || $p['tipo_entrega'] !== 'retiro' || (int)$p['costo_despacho'] !== 0) {
        ventaFallo('Este flujo solo convierte cotizaciones presenciales con retiro.',409);
    }
    if ((int)($datos['cliente']['id_cliente'] ?? 0) !== (int)$p['id_cliente']) ventaFallo('No puede cambiar el cliente de la cotización.',409);
    $s = $db->prepare("SELECT id_documento FROM documentos_impresion WHERE id_pedido=? AND tipo='cotizacion' LIMIT 1");
    $s->bind_param('i', $p['id_pedido']); $s->execute(); $copia = $s->get_result()->fetch_assoc(); $s->close();
    if (!$copia) ventaFallo('La cotización no tiene copia histórica. No se modificará: revise su emisión antes de convertirla.',409);
    $s = $db->prepare('SELECT id_pago FROM pagos WHERE id_pedido=? LIMIT 1 FOR UPDATE');
    $s->bind_param('i', $p['id_pedido']); $s->execute(); $pago = $s->get_result()->fetch_assoc(); $s->close();
    if ($pago) ventaFallo('La cotización ya tiene registros de pago. Revísela antes de continuar.',409);
    return $p;
}

function ventaRevision(array $datos, array $productos, int $total, ?array $origen): array {
    $precios = array_map(static fn($p) => [$p['id_producto'],$p['cantidad'],$p['precio_unitario']], $productos);
    $firma = json_encode([ventaHash($datos), $precios, $origen['fecha_actualizacion'] ?? null], JSON_THROW_ON_ERROR);
    $neto = (int)round($total / 1.19);
    return ['productos'=>$productos, 'neto'=>$neto, 'iva'=>$total-$neto, 'total'=>$total,
        'token_revision'=>hash_hmac('sha256', $firma, $_SESSION['clave_revision_venta'])];
}

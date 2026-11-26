// Ventas · Consultar ventas: ventana de detalle, ventana de cancelar y botón "Sólo hoy".
(function () {
  const $ = function (id) { return document.getElementById(id); };

  function elemento(etiqueta, clase, texto) {
    const e = document.createElement(etiqueta);
    if (clase) { e.className = clase; }
    if (texto !== undefined && texto !== null) { e.textContent = texto; }
    return e;
  }

  // ---------- Detalle ----------
  const detalle = $('modalDetalleVenta');
  if (detalle) {
    detalle.addEventListener('show.bs.modal', function (event) {
      const boton = event.relatedTarget;
      if (!boton) { return; }
      const v = JSON.parse(boton.dataset.venta);

      $('detalleFolio').textContent = v.folio;
      $('detalleSubtitulo').textContent = v.fecha;
      $('detalleCliente').textContent = v.cliente;
      $('detalleTrabajador').textContent = v.trabajador || '—';
      $('detallePago').textContent = v.metodoPago;

      $('detalleCancelacion').classList.toggle('d-none', !v.cancelacion);
      if (v.cancelacion) {
        $('detalleCancelacionTexto').textContent = v.cancelacion.fecha + ' · por ' + (v.cancelacion.quien || '—')
          + ' · Motivo: ' + (v.cancelacion.motivo || '—');
      }

      const cuerpo = $('detalleRenglones');
      cuerpo.replaceChildren();
      v.renglones.forEach(function (r) {
        const tr = elemento('tr');
        tr.appendChild(elemento('td', 'text-muted text-nowrap', r.codigo));
        tr.appendChild(elemento('td', 'cell-main', r.nombre));
        tr.appendChild(elemento('td', 'text-end', r.cantidad));
        tr.appendChild(elemento('td', 'text-end text-nowrap', r.precio));
        tr.appendChild(elemento('td', 'text-end text-nowrap fw-semibold text-dark', r.importe));
        cuerpo.appendChild(tr);
      });

      // Pagos (se muestran si es a crédito o si hubo más de uno)
      const pagos = v.pagos || [];
      $('detallePagos').classList.toggle('d-none', !(v.credito || pagos.length > 1));
      const listaPagos = $('detallePagosLista');
      listaPagos.replaceChildren();
      if (pagos.length === 0) { listaPagos.appendChild(elemento('div', 'text-muted', 'Todavía no ha pagado nada.')); }
      pagos.forEach(function (p) {
        const fila = elemento('div', 'd-flex justify-content-between gap-3');
        fila.appendChild(elemento('span', 'text-muted', p.fecha + ' · ' + p.metodo));
        fila.appendChild(elemento('span', 'text-dark', p.monto));
        listaPagos.appendChild(fila);
      });
      $('detalleSaldo').textContent = v.saldo ? 'Pagado ' + v.pagado + ' · Debe ' + v.saldo : 'Pagado ' + v.pagado + ' · Liquidada';
      $('detalleSaldo').className = 'mt-2 fw-semibold ' + (v.saldo ? 'saldo-pendiente' : 'text-success');

      const devoluciones = v.devoluciones || [];
      $('detalleDevoluciones').classList.toggle('d-none', devoluciones.length === 0);
      const lista = $('detalleDevolucionesLista');
      lista.replaceChildren();
      devoluciones.forEach(function (d) {
        const caja = elemento('div', 'tarjeta-gris small');
        caja.appendChild(elemento('div', 'fw-semibold text-dark', d.folio + ' · ' + d.cantidad + ' × ' + d.producto + ' · Reembolso ' + d.reembolso));
        caja.appendChild(elemento('div', 'text-muted', d.fecha + ' · ' + (d.motivo || '—') + ' · ' + (d.inventario ? 'regresó al inventario' : 'no regresó al inventario (dañado)')));
        lista.appendChild(caja);
      });

      $('detalleSubtotal').textContent = v.subtotal;
      $('detalleDescuentoFila').classList.toggle('d-none', !v.descuento);
      $('detalleDescuento').textContent = v.descuento || '';
      $('detalleIva').textContent = v.iva;
      $('detalleTotal').textContent = v.total;
      $('detalleImprimir').href = boton.dataset.ticket;
    });
  }

  // ---------- Cancelar ----------
  const cancelar = $('modalCancelarVenta');
  if (cancelar) {
    const form = $('formCancelarVenta');

    function preparar(datos, limpiar) {
      form.action = datos.url;
      $('cancelarFolio').textContent = datos.folio;
      $('cancelarDetalle').textContent = datos.detalle;
      if (limpiar) {
        // A mano: form.reset() regresaría lo que venía de un error anterior
        ['cancelarMotivo', 'cancelarComentario', 'cancelarCorreo', 'cancelarContrasena'].forEach(function (id) { $(id).value = ''; });
        $('cancelarMotivo').classList.remove('is-invalid');
        const errores = form.querySelector('.alert-danger');
        if (errores) { errores.remove(); }
      }
      $('cancelarBoton').disabled = false;
    }

    cancelar.addEventListener('show.bs.modal', function (event) {
      if (event.relatedTarget) { preparar(event.relatedTarget.dataset, true); }
    });
    cancelar.addEventListener('shown.bs.modal', function () {
      ($('cancelarMotivo').value ? $('cancelarContrasena') : $('cancelarMotivo')).focus();
    });

    form.addEventListener('submit', function (e) {
      if (!$('cancelarMotivo').value) {
        e.preventDefault();
        $('cancelarMotivo').classList.add('is-invalid');
        $('cancelarMotivo').focus();
        return;
      }
      $('cancelarBoton').disabled = true; // evita doble clic
      $('cancelarBoton').textContent = 'Cancelando…';
    });
    $('cancelarMotivo').addEventListener('change', function () { this.classList.remove('is-invalid'); });

    // Regresó con un error (contraseña mala, etc.): se vuelve a abrir con lo elegido
    if (cancelar.dataset.reabrir) {
      preparar(cancelar.dataset, false);
      bootstrap.Modal.getOrCreateInstance(cancelar).show();
    }
  }

  // ---------- "Sólo hoy" ----------
  const hoy = $('filtroHoy');
  if (hoy) {
    hoy.addEventListener('click', function () {
      const d = new Date();
      const texto = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
      $('filtroDesde').value = texto;
      $('filtroHasta').value = texto;
      $('filtrosVentas').submit();
    });
  }
})();

// ---------- Registrar devolución ----------
(function () {
  const modal = document.getElementById('modalDevolucion');
  if (!modal) {
    return;
  }

  const $ = function (id) { return document.getElementById(id); };
  const form = $('formDevolucion');
  let renglones = [];

  function dinero(n) {
    return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function elegido() {
    const id = parseInt($('devolucionProducto').value, 10);
    return renglones.find(function (r) { return r.id === id; });
  }

  function actualizar() {
    const r = elegido();
    if (!r) { return; }
    const cantidad = $('devolucionCantidad');
    cantidad.max = r.disponibles;
    let n = parseInt(cantidad.value, 10);
    if (!Number.isFinite(n) || n < 1) { n = 1; }
    if (n > r.disponibles) { n = r.disponibles; cantidad.value = n; }
    $('devolucionMaximo').textContent = 'Máximo ' + r.disponibles + ' (se vendieron ' + r.vendidas + ')';
    // Estimado: el servidor calcula el exacto (en la última devolución ajusta los centavos)
    $('devolucionReembolso').textContent = dinero(Math.round(r.porPieza * n * 100) / 100);
  }

  function cargar(url, datos, elegidos) {
    form.action = url;
    $('devolucionSubtitulo').textContent = 'Venta ' + datos.folio + ' · ' + datos.cliente;
    renglones = datos.renglones;
    const select = $('devolucionProducto');
    select.replaceChildren();
    renglones.forEach(function (r) {
      const o = document.createElement('option');
      o.value = r.id;
      o.textContent = r.codigo + ' · ' + r.nombre;
      select.appendChild(o);
    });
    select.value = elegidos.producto || (renglones[0] ? renglones[0].id : '');
    $('devolucionCantidad').value = elegidos.cantidad || 1;
    $('devolucionMotivo').value = elegidos.motivo || '';
    $('devolucionInventario').checked = elegidos.regresaInventario !== false;
    actualizar();
  }

  modal.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    if (!boton || !boton.dataset.devolucion) { return; }
    cargar(boton.dataset.url, JSON.parse(boton.dataset.devolucion), {});
    ['devolucionComentario', 'devolucionCorreo', 'devolucionContrasena'].forEach(function (id) { $(id).value = ''; });
    $('devolucionMotivo').classList.remove('is-invalid');
    const errores = $('devolucionErrores');
    if (errores) { errores.remove(); }
    $('devolucionBoton').disabled = false;
  });

  $('devolucionProducto').addEventListener('change', function () { $('devolucionCantidad').value = 1; actualizar(); });
  $('devolucionCantidad').addEventListener('input', actualizar);

  // "Llegó dañado" → no regresa al inventario (se puede cambiar a mano)
  $('devolucionMotivo').addEventListener('change', function () {
    this.classList.remove('is-invalid');
    const opcion = this.options[this.selectedIndex];
    if (opcion && opcion.value) { $('devolucionInventario').checked = !opcion.dataset.danado; }
  });

  form.addEventListener('submit', function (e) {
    if (!$('devolucionMotivo').value) {
      e.preventDefault();
      $('devolucionMotivo').classList.add('is-invalid');
      $('devolucionMotivo').focus();
      return;
    }
    $('devolucionBoton').disabled = true;
    $('devolucionBoton').textContent = 'Guardando…';
  });

  if (modal.dataset.reabrir) {
    const r = JSON.parse(modal.dataset.reabrir);
    cargar(r.url, r.datos, r);
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
})();

// ---------- Registrar abono (ventas a crédito) ----------
(function () {
  const modal = document.getElementById('modalAbono');
  if (!modal) {
    return;
  }
  const $ = function (id) { return document.getElementById(id); };
  let saldo = 0;   // en centavos

  function dinero(c) {
    return '$' + (c / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function revisar() {
    const texto = $('abonoMonto').value.trim();
    const valor = Number(texto);
    const centavos = Math.round(valor * 100);
    const valido = texto !== '' && Number.isFinite(valor) && centavos >= 1 && centavos <= saldo
      && Math.abs(valor * 100 - centavos) < 1e-6;
    $('abonoMonto').classList.toggle('is-invalid', texto !== '' && !valido);
    $('abonoRestante').textContent = !valido
      ? (texto === '' ? '' : 'El abono debe ser de $0.01 a ' + dinero(saldo) + '.')
      : (centavos === saldo ? 'Con este abono la venta queda liquidada.' : 'Después de este abono debe ' + dinero(saldo - centavos) + '.');
    return valido;
  }

  function cargar(d, limpiar) {
    $('formAbono').action = d.url;
    $('abonoFolio').textContent = d.folio;
    $('abonoDetalle').textContent = d.detalle;
    saldo = Math.round(Number(d.saldo) * 100);
    $('abonoSaldo').textContent = dinero(saldo);
    if (limpiar) {
      $('abonoMonto').value = '';
      const errores = $('abonoErrores');
      if (errores) { errores.remove(); }
    }
    $('abonoBoton').disabled = false;
    revisar();
  }

  modal.addEventListener('show.bs.modal', function (e) {
    if (e.relatedTarget && e.relatedTarget.dataset.saldo) { cargar(e.relatedTarget.dataset, true); }
  });
  modal.addEventListener('shown.bs.modal', function () { $('abonoMonto').focus(); });
  $('abonoMonto').addEventListener('input', revisar);
  $('abonoTodo').addEventListener('click', function () { $('abonoMonto').value = (saldo / 100).toFixed(2); revisar(); });

  $('formAbono').addEventListener('submit', function (e) {
    if (!revisar()) {
      e.preventDefault();
      $('abonoMonto').classList.add('is-invalid');
      $('abonoMonto').focus();
      return;
    }
    $('abonoBoton').disabled = true;
  });

  if (modal.dataset.reabrir) {
    cargar(modal.dataset, false);
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
})();

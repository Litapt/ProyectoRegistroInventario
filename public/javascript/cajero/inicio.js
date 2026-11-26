// Cajero · Pantalla principal (pedidos).
// 1) "Abrir turno": suma billetes y monedas en pantalla y, si no es el fondo autorizado,
//    muestra la autorización. (El servidor vuelve a sumar: aquí sólo es ayuda.)
// 2) "Ver": llena la ventana de detalles del pedido.
// 3) "Cancelar": pone el folio y la dirección en la ventana de cancelar.

(function () {
  const $ = (id) => document.getElementById(id);
  const dinero = (centavos) => '$' + (centavos / 100).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const aCentavos = (texto) => {
    const n = parseFloat(String(texto).replace(',', '.'));
    return Number.isFinite(n) && n > 0 ? Math.round(n * 100) : 0;
  };

  // ---------- 1) Abrir turno ----------
  const formTurno = $('formAbrirTurno');
  if (formTurno) {
    const fondo = aCentavos(formTurno.dataset.fondo);

    function sumar() {
      let total = 0;
      formTurno.querySelectorAll('.conteo-piezas').forEach(function (campo) {
        const piezas = Math.max(0, parseInt(campo.value, 10) || 0);
        const importe = piezas * parseInt(campo.dataset.valor, 10) * 100;
        formTurno.querySelector('[data-importe-de="' + campo.dataset.valor + '"]').textContent = dinero(importe);
        total += importe;
      });
      const monedas = aCentavos($('monedas').value);
      formTurno.querySelector('[data-importe-de="monedas"]').textContent = dinero(monedas);
      total += monedas;

      $('fondoTotal').textContent = dinero(total);
      const cuadra = total === fondo;
      $('fondoTotal').classList.toggle('text-danger', !cuadra && total > 0);
      $('bloqueAutorizacionTurno').classList.toggle('d-none', cuadra || total === 0);
      if (!cuadra) {
        $('avisoFondo').textContent = 'Contaste ' + dinero(total) + ' y el fondo autorizado es ' + dinero(fondo) + ' ('
          + (total > fondo ? 'sobran ' + dinero(total - fondo) : 'faltan ' + dinero(fondo - total))
          + '). Un gerente o administrador tiene que autorizarlo.';
      }
    }

    formTurno.addEventListener('input', sumar);
    formTurno.addEventListener('submit', function () {
      const boton = $('botonAbrirTurno');
      boton.disabled = true;
      boton.textContent = 'Abriendo…';
    });
    sumar();

    // La ventana se abre sola y no se puede cerrar
    bootstrap.Modal.getOrCreateInstance($('modalAbrirTurno')).show();
  }

  // ---------- 2) Detalles del pedido ----------
  const modalDetalle = $('modalDetallePedido');
  if (modalDetalle) {
    modalDetalle.addEventListener('show.bs.modal', function (e) {
      const boton = e.relatedTarget;
      if (!boton) { return; }
      const p = JSON.parse(boton.dataset.pedido);

      $('pedidoFolio').textContent = p.folio;
      $('pedidoSubtitulo').textContent = 'Registrado el ' + p.fecha;
      $('pedidoCliente').textContent = p.cliente;
      $('pedidoClienteDatos').textContent = p.codigoCliente ? p.codigoCliente + ' · ' + (p.telefono || '') : '';
      $('pedidoTrabajador').textContent = p.trabajador || '';

      const cuerpo = $('pedidoRenglones');
      cuerpo.innerHTML = '';
      p.productos.forEach(function (r) {
        const fila = document.createElement('tr');
        [r.codigo, r.nombre, r.cantidad, r.precio, r.subtotal].forEach(function (valor, i) {
          const celda = document.createElement('td');
          celda.textContent = valor === null ? '' : valor;
          if (i >= 2) { celda.className = 'text-end text-nowrap'; }
          fila.appendChild(celda);
        });
        cuerpo.appendChild(fila);
      });

      $('pedidoSubtotal').textContent = p.subtotal;
      $('pedidoDescuentoFila').classList.toggle('d-none', !p.descuento);
      $('pedidoDescuento').textContent = p.descuento || '';
      $('pedidoIva').textContent = p.iva;
      $('pedidoTotal').textContent = p.total;

      [['pedidoCobrar', boton.dataset.cobrar], ['pedidoEditar', boton.dataset.editar]].forEach(function (par) {
        $(par[0]).classList.toggle('d-none', !par[1]);
        $(par[0]).href = par[1] || '#';
      });
    });
  }

  // ---------- 3) Cancelar pedido ----------
  const modalCancelar = $('modalCancelarPedido');
  if (modalCancelar) {
    function preparar(datos) {
      $('formCancelarPedido').action = datos.url;
      $('cancelarPedidoFolio').textContent = datos.folio;
      $('cancelarPedidoDetalle').textContent = datos.detalle;
    }
    modalCancelar.addEventListener('show.bs.modal', function (e) {
      if (e.relatedTarget) { preparar(e.relatedTarget.dataset); }
    });
    // Si hubo un error, se vuelve a abrir sola
    if (modalCancelar.dataset.reabrir) {
      preparar(modalCancelar.dataset);
      bootstrap.Modal.getOrCreateInstance(modalCancelar).show();
    }
    $('formCancelarPedido').addEventListener('submit', function (e) {
      const boton = e.submitter;
      if (boton) { boton.disabled = true; boton.textContent = 'Cancelando…'; }
    });
  }
})();

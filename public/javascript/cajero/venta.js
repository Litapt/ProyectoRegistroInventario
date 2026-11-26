// Cajero · Nueva venta: el cambio y la autorización.
// Las cuentas (total, descuento, cuánto paga ahora) las avisa javascript/administrador/ventas.js
// con el evento "venta:cuentas". Este archivo se carga ANTES que ventas.js para revisar
// el cambio y la autorización antes de que se envíe la venta. El servidor revisa todo otra vez.

(function () {
  const $ = (id) => document.getElementById(id);
  const form = $('formVenta');
  if (!form) { return; }

  const dinero = (centavos) => '$' + (centavos / 100).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const aCentavos = (texto) => {
    const n = Number(String(texto).trim());
    return String(texto).trim() !== '' && Number.isFinite(n) && n >= 0 ? Math.round(n * 100) : null;
  };

  let cuentas = { total: 0, descuento: 0, credito: false, pagaAhora: 0 };
  // Si el servidor regresó un error de autorización, el bloque se queda a la vista
  const pedidaPorServidor = !$('bloqueAutorizacion').classList.contains('d-none');

  const esEfectivo = () => !!form.dataset.efectivo && $('metodoPago').value === form.dataset.efectivo;
  const necesitaAutorizacion = () => cuentas.descuento > 0 || (cuentas.credito && cuentas.pagaAhora < cuentas.total);

  function pintar() {
    // Efectivo y cambio
    $('bloqueEfectivo').classList.toggle('d-none', !esEfectivo());
    const recibido = aCentavos($('recibido').value);
    const aPagar = cuentas.pagaAhora;
    $('cambio').classList.remove('text-danger');
    if (recibido === null) {
      $('cambio').textContent = '$0.00';
      $('ayudaCambio').textContent = 'A pagar: ' + dinero(aPagar);
    } else if (recibido < aPagar) {
      $('cambio').textContent = '—';
      $('cambio').classList.add('text-danger');
      $('ayudaCambio').textContent = 'Faltan ' + dinero(aPagar - recibido);
    } else {
      $('cambio').textContent = dinero(recibido - aPagar);
      $('ayudaCambio').textContent = 'A pagar: ' + dinero(aPagar);
    }

    // Autorización (descuento o saldo pendiente)
    const pide = necesitaAutorizacion();
    $('bloqueAutorizacion').classList.toggle('d-none', !pide && !pedidaPorServidor);
    if (pide) {
      const motivos = [];
      if (cuentas.descuento > 0) { motivos.push('tiene ' + cuentas.descuento + ' % de descuento'); }
      if (cuentas.credito && cuentas.pagaAhora < cuentas.total) { motivos.push('deja ' + dinero(cuentas.total - cuentas.pagaAhora) + ' pendiente'); }
      $('ayudaAutorizacion').textContent = 'Esta venta ' + motivos.join(' y ') + ': no es un pago directo.';
    }
  }

  form.addEventListener('venta:cuentas', function (e) { cuentas = e.detail; pintar(); });
  $('metodoPago').addEventListener('change', pintar);
  $('recibido').addEventListener('input', function () { $('recibido').classList.remove('is-invalid'); pintar(); });
  $('recibido').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
  $('botonExacto').addEventListener('click', function () {
    $('recibido').value = (cuentas.pagaAhora / 100).toFixed(2);
    $('recibido').classList.remove('is-invalid');
    pintar();
  });

  // Antes que ventas.js: si falta algo, no se envía (y ventas.js ni se entera)
  form.addEventListener('submit', function (e) {
    function detener(campo) {
      e.preventDefault();
      e.stopImmediatePropagation();
      campo.classList.add('is-invalid');
      campo.focus();
    }

    if (esEfectivo()) {
      const recibido = aCentavos($('recibido').value);
      if (recibido === null || recibido < cuentas.pagaAhora) { detener($('recibido')); return; }
    } else {
      $('recibido').value = '';
    }

    if (necesitaAutorizacion()) {
      if ($('ventaCorreo').value.trim() === '') { detener($('ventaCorreo')); return; }
      if ($('ventaContrasena').value === '') { detener($('ventaContrasena')); return; }
    }
  });

  ['ventaCorreo', 'ventaContrasena'].forEach(function (id) {
    $(id).addEventListener('input', function () { $(id).classList.remove('is-invalid'); });
  });

  pintar();
})();

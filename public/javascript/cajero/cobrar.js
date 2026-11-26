// Cajero · Cobrar un pedido.
// - Si el pago es en efectivo: con cuánto paga, botón "Exacto" y el cambio.
// - Si hay cliente y "Paga una parte": cuánto paga ahora y cuánto queda debiendo.
// - Descuento o saldo pendiente: pide la autorización de un gerente o administrador.
// El servidor revisa todo otra vez; aquí sólo es ayuda para el cajero.

(function () {
  const $ = (id) => document.getElementById(id);
  const form = $('formCobrar');
  if (!form) { return; }

  const dinero = (centavos) => '$' + (centavos / 100).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const aCentavos = (texto) => {
    const n = parseFloat(String(texto).replace(',', '.'));
    return Number.isFinite(n) && n >= 0 ? Math.round(n * 100) : null;
  };
  const total = aCentavos(form.dataset.total) || 0;

  const esCredito = () => {
    const opcion = form.querySelector('input[name="condicion"]:checked');
    return !!opcion && opcion.value === 'CREDITO';
  };
  const esEfectivo = () => form.dataset.efectivo !== '' && $('metodoPago').value === form.dataset.efectivo;

  // Lo que se paga en este momento (todo, o la parte que escribió)
  function pagaAhora() {
    if (!esCredito()) { return total; }
    const parte = aCentavos($('pagoInicial').value);
    return parte === null ? 0 : Math.min(parte, total);
  }

  const necesitaAutorizacion = () => !!form.dataset.conDescuento || (esCredito() && pagaAhora() < total);

  function pintar() {
    $('bloqueAutorizacion').classList.toggle('d-none', !necesitaAutorizacion());

    // Paga una parte
    if ($('bloquePagoInicial')) {
      $('bloquePagoInicial').classList.toggle('d-none', !esCredito());
      if (esCredito()) {
        const parte = aCentavos($('pagoInicial').value);
        $('ayudaPagoInicial').textContent = parte !== null && parte >= total
          ? 'Paga todo: se registra como pagada.'
          : 'Queda debiendo ' + dinero(total - (parte || 0)) + '.';
      }
    }

    // Efectivo y cambio
    $('bloqueEfectivo').classList.toggle('d-none', !esEfectivo());
    const recibido = aCentavos($('recibido').value);
    const aPagar = pagaAhora();
    if (recibido === null || $('recibido').value.trim() === '') {
      $('cambio').textContent = '$0.00';
      $('ayudaCambio').textContent = 'A pagar: ' + dinero(aPagar);
      $('cambio').classList.remove('text-danger');
    } else if (recibido < aPagar) {
      $('cambio').textContent = '—';
      $('ayudaCambio').textContent = 'Faltan ' + dinero(aPagar - recibido);
      $('cambio').classList.add('text-danger');
    } else {
      $('cambio').textContent = dinero(recibido - aPagar);
      $('ayudaCambio').textContent = 'A pagar: ' + dinero(aPagar);
      $('cambio').classList.remove('text-danger');
    }
  }

  $('botonExacto').addEventListener('click', function () {
    $('recibido').value = (pagaAhora() / 100).toFixed(2);
    pintar();
  });

  form.addEventListener('input', pintar);
  form.addEventListener('change', pintar);
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); }
  });

  form.addEventListener('submit', function (e) {
    // En efectivo, que alcance lo recibido
    if (esEfectivo()) {
      const recibido = aCentavos($('recibido').value);
      if (recibido === null || $('recibido').value.trim() === '' || recibido < pagaAhora()) {
        e.preventDefault();
        $('recibido').classList.add('is-invalid');
        $('recibido').focus();
        return;
      }
    } else {
      $('recibido').value = '';
    }
    if (necesitaAutorizacion()) {
      for (const id of ['cobroCorreo', 'cobroContrasena']) {
        if ($(id).value.trim() === '') {
          e.preventDefault();
          $(id).classList.add('is-invalid');
          $(id).focus();
          return;
        }
      }
    }
    const boton = $('botonCobrar');
    boton.disabled = true;
    boton.textContent = 'Cobrando…';
  });

  pintar();
})();

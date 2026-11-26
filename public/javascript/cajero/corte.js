// Cajero · Corte de caja: suma el conteo, compara con lo esperado y muestra la diferencia.
// Si falta o sobra dinero, abre la autorización de un superior. El servidor vuelve a calcular todo.

(function () {
  const form = document.getElementById('formCorte');
  if (!form) { return; }
  const $ = (id) => document.getElementById(id);
  const dinero = (c) => (c < 0 ? '−' : '') + '$' + (Math.abs(c) / 100).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const aCentavos = (texto) => {
    const n = Number(String(texto).trim());
    return String(texto).trim() !== '' && Number.isFinite(n) && n > 0 ? Math.round(n * 100) : 0;
  };
  const esperadoEfectivo = parseInt(form.dataset.esperadoEfectivo, 10) || 0;
  let diferencia = 0;

  function pintarDiferencia(celda, contado, esperado) {
    const d = contado - esperado;
    celda.textContent = d === 0 ? 'Cuadra' : (d < 0 ? 'Faltan ' : 'Sobran ') + dinero(Math.abs(d));
    celda.className = 'text-end fw-semibold ' + (d === 0 ? 'text-success' : 'text-danger');
  }

  function calcular() {
    // Efectivo
    let efectivo = 0;
    form.querySelectorAll('.conteo-piezas').forEach(function (campo) {
      const piezas = Math.max(0, parseInt(campo.value, 10) || 0);
      const importe = piezas * parseInt(campo.dataset.valor, 10) * 100;
      form.querySelector('[data-importe-de="' + campo.dataset.valor + '"]').textContent = dinero(importe);
      efectivo += importe;
    });
    efectivo += aCentavos($('corteMonedas').value);
    $('corteEfectivo').textContent = dinero(efectivo);

    const filaEfectivo = form.querySelector('[data-metodo="efectivo"]');
    filaEfectivo.querySelector('[data-contado]').textContent = dinero(efectivo);
    pintarDiferencia(filaEfectivo.querySelector('[data-diferencia]'), efectivo, esperadoEfectivo);

    // Otros métodos
    let total = efectivo;
    let esperadoTotal = esperadoEfectivo;
    form.querySelectorAll('.corte-otro').forEach(function (campo) {
      const contado = aCentavos(campo.value);
      const esperado = parseInt(campo.dataset.esperado, 10) || 0;
      const fila = form.querySelector('[data-metodo="' + campo.name.match(/\d+/)[0] + '"]');
      fila.querySelector('[data-contado]').textContent = dinero(contado);
      pintarDiferencia(fila.querySelector('[data-diferencia]'), contado, esperado);
      total += contado;
      esperadoTotal += esperado;
    });

    diferencia = total - esperadoTotal;
    $('corteTotal').textContent = dinero(total);
    $('corteDiferencia').textContent = diferencia === 0 ? 'Cuadra' : dinero(Math.abs(diferencia));
    $('corteDiferenciaTexto').textContent = diferencia === 0 ? 'Diferencia' : (diferencia < 0 ? 'Faltan' : 'Sobran');
    $('corteDiferenciaCaja').classList.toggle('resumen-corte-mal', diferencia !== 0);
    $('corteDiferenciaCaja').classList.toggle('resumen-corte-bien', diferencia === 0);

    if (diferencia !== 0) {
      $('bloqueAutorizacionCorte').classList.remove('d-none');
      $('ayudaAutorizacionCorte').textContent = (diferencia < 0 ? 'Faltan ' : 'Sobran ') + dinero(Math.abs(diferencia))
        + ': un gerente o administrador autoriza el corte y se escribe qué pasó.';
    }
  }

  $('botonAutorizacionCorte').addEventListener('click', function () {
    $('bloqueAutorizacionCorte').classList.remove('d-none');
    $('corteCorreo').focus();
  });

  form.addEventListener('input', function (e) { e.target.classList.remove('is-invalid'); calcular(); });
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); }
  });

  form.addEventListener('submit', function (e) {
    calcular();
    if (diferencia !== 0) {
      for (const id of ['corteCorreo', 'corteContrasena', 'corteObservaciones']) {
        if ($(id).value.trim() === '') {
          e.preventDefault();
          $(id).classList.add('is-invalid');
          $(id).focus();
          return;
        }
      }
    }
    const textoTotal = $('corteTotal').textContent;
    if (!window.confirmarCorte) {
      // Una sola vez: confirmar que el turno se va a cerrar
      e.preventDefault();
      $('botonGuardarCorte').textContent = 'Confirmar: cerrar turno con ' + textoTotal;
      $('botonGuardarCorte').classList.add('btn-app-danger');
      window.confirmarCorte = true;
      return;
    }
    $('botonGuardarCorte').disabled = true;
    $('botonGuardarCorte').textContent = 'Guardando…';
  });

  calcular();
})();

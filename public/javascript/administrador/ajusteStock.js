// Ajuste de stock: vista previa mientras escribes y ventana "¿Confirmas el ajuste de stock?".
// El servidor vuelve a revisar todo antes de guardar (por si alguien cambia el stock al mismo tiempo).
(function () {
  const form = document.getElementById('formAjuste');
  if (!form) {
    return;
  }

  const stock = Number(form.dataset.stock);
  const minimo = Number(form.dataset.minimo);
  const opciones = document.querySelectorAll('.move-option');
  const cantidad = document.getElementById('cantidad');
  const ayudaCantidad = document.getElementById('ayudaCantidad');
  const especificacion = document.getElementById('especificacion');
  const cajaReferencia = document.getElementById('cajaReferencia');
  const operacion = document.getElementById('operacion');
  const resultante = document.getElementById('stockResultante');
  const estadoMinimo = document.getElementById('estadoMinimo');
  const resumenTitulo = document.getElementById('resumenTitulo');
  const resumenTexto = document.getElementById('resumenTexto');
  const boton = document.getElementById('botonConfirmar');
  const textoConfirmar = document.getElementById('textoConfirmar');
  const modal = document.getElementById('modalConfirmarAjuste');
  let confirmado = false;

  // 24 → "24 unidades", 1 → "1 unidad"
  function unidades(n) {
    return n + (n === 1 ? ' unidad' : ' unidades');
  }

  function esEgreso() {
    return form.elements.tipo.value === 'SALIDA';
  }

  // La cantidad si es un número entero mayor que 0; si no, 0
  function laCantidad() {
    const n = Number(cantidad.value);
    return Number.isInteger(n) && n > 0 ? n : 0;
  }

  function textoDelMinimo(n) {
    if (n < minimo) {
      return 'Por debajo del mínimo';
    }
    return n === minimo ? 'En el mínimo' : 'Por encima del mínimo';
  }

  function actualizar() {
    const egreso = esEgreso();
    const n = laCantidad();
    const demasiado = egreso && n > stock;
    const resultado = egreso ? stock - n : stock + n;

    opciones.forEach(function (opcion) {
      opcion.classList.toggle('is-selected', opcion.dataset.tipo === (egreso ? 'SALIDA' : 'ENTRADA'));
    });

    // El egreso no puede retirar más de lo que hay y debe explicar a dónde van las piezas
    cantidad.max = egreso ? Math.min(stock, 99999) : 99999;
    ayudaCantidad.textContent = 'Introduce un número entero mayor que 0.' + (egreso ? ' Puedes retirar hasta ' + unidades(stock) + '.' : '');
    especificacion.required = egreso;
    especificacion.setCustomValidity(egreso && especificacion.value.trim() === '' ? 'En un egreso escribe a dónde van las piezas o por qué salen.' : '');
    document.getElementById('tituloEspecificacion').textContent = egreso ? 'Especificación del egreso' : 'Especificación de la entrada';
    cajaReferencia.classList.toggle('d-none', egreso); // la factura sólo aplica a entradas

    operacion.textContent = (egreso ? '− ' : '+ ') + n + '  =';
    resultante.textContent = demasiado ? 'No disponible' : unidades(resultado);
    estadoMinimo.textContent = demasiado ? 'Cantidad mayor al stock' : textoDelMinimo(resultado);

    const nombre = egreso ? 'egreso' : 'entrada';
    document.getElementById('iconoEntrada').classList.toggle('d-none', egreso);
    document.getElementById('iconoEgreso').classList.toggle('d-none', !egreso);
    textoConfirmar.textContent = 'Confirmar ' + nombre + (n > 0 ? ' de ' + unidades(n) : '');

    if (n === 0) {
      resumenTitulo.textContent = 'Ingresa la cantidad a ajustar.';
      resumenTexto.textContent = 'Aquí verás cómo quedará el stock antes de confirmar.';
    } else if (demasiado) {
      resumenTitulo.textContent = 'La cantidad supera el stock disponible.';
      resumenTexto.textContent = 'Solo puedes retirar hasta ' + unidades(stock) + '.';
    } else {
      resumenTitulo.textContent = (egreso ? 'Se retirará' : 'Se agregará') + (n === 1 ? ' ' : 'n ') + unidades(n) + (egreso ? ' del almacén.' : ' al almacén.');
      resumenTexto.textContent = 'El stock pasará de ' + stock + ' a ' + unidades(resultado) + '. El movimiento se guardará como ' + nombre + '.';
    }
  }

  opciones.forEach(function (opcion) {
    opcion.querySelector('input').addEventListener('change', actualizar);
  });
  cantidad.addEventListener('input', actualizar);
  especificacion.addEventListener('input', actualizar);

  // Primero se revisa el formulario y se pide confirmación; después se envía una sola vez
  form.addEventListener('submit', function (event) {
    if (confirmado) {
      boton.disabled = true; // evita dos clics = dos movimientos
      return;
    }
    event.preventDefault();
    if (!form.reportValidity()) {
      return;
    }
    const n = laCantidad();
    document.getElementById('confirmarAjusteTexto').textContent = esEgreso()
      ? 'Se registrará un egreso de ' + unidades(n) + ': el stock pasará de ' + stock + ' a ' + (stock - n) + '.'
      : 'Se registrará una entrada de ' + unidades(n) + ' al inventario: el stock pasará de ' + stock + ' a ' + (stock + n) + '.';
    bootstrap.Modal.getOrCreateInstance(modal).show();
  });

  document.getElementById('confirmarAjusteSi').addEventListener('click', function () {
    confirmado = true;
    this.disabled = true;
    form.requestSubmit();
  });

  document.getElementById('stockActual').textContent = unidades(stock);
  document.getElementById('textoMinimo').textContent = 'Stock mínimo: ' + unidades(minimo);
  actualizar();
})();

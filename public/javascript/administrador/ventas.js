// Ventas · Registrar venta.
// Maneja el cliente elegido, la lista de productos y las cuentas que se ven en pantalla.
// IMPORTANTE: las cuentas son las mismas que App\Support\CalculoVenta (PHP). El servidor
// vuelve a calcular todo al guardar; si no coincide con lo que se vio aquí, avisa.
(function () {
  const form = document.getElementById('formVenta');
  if (!form) {
    return;
  }

  const $ = function (id) { return document.getElementById(id); };
  const token = form.querySelector('input[name=_token]').value;

  const estado = {
    cliente: JSON.parse(form.dataset.cliente || 'null'),
    carrito: JSON.parse(form.dataset.carrito || '[]'),
    descuento: 0 // % aplicado (con "Aplicar")
  };

  // ---------- Dinero y cuentas (en centavos, igual que CalculoVenta.php) ----------

  function dinero(centavos) {
    return '$' + (centavos / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // centavos × (puntos / 10 000), redondeado al centavo. 1600 puntos = 16 %
  function porcentaje(centavos, puntos) {
    return Math.floor((centavos * puntos * 2 + 10000) / 20000);
  }

  function calcular() {
    const puntosDescuento = Math.round(estado.descuento * 100);
    const renglones = estado.carrito.map(function (p) {
      const precio = Math.round(p.precio * 100);
      return { subtotal: precio * p.cantidad, tasa: Math.round(p.tasa * 100) };
    });

    const subtotal = renglones.reduce(function (s, r) { return s + r.subtotal; }, 0);
    const descuento = porcentaje(subtotal, puntosDescuento);

    let repartido = 0;
    let iva = 0;
    renglones.forEach(function (r, i) {
      const desc = i === renglones.length - 1 ? descuento - repartido : porcentaje(r.subtotal, puntosDescuento);
      repartido += desc;
      iva += porcentaje(r.subtotal - desc, r.tasa);
    });

    return { subtotal: subtotal, descuento: descuento, iva: iva, total: subtotal - descuento + iva };
  }

  // ---------- Pintar ----------

  function elemento(etiqueta, clase, texto) {
    const e = document.createElement(etiqueta);
    if (clase) { e.className = clase; }
    if (texto !== undefined) { e.textContent = texto; }
    return e;
  }

  function pintarCarrito() {
    const cuerpo = $('carrito');
    cuerpo.querySelectorAll('tr[data-id]').forEach(function (tr) { tr.remove(); });
    $('carritoVacio').classList.toggle('d-none', estado.carrito.length > 0);

    estado.carrito.forEach(function (p) {
      const tr = elemento('tr');
      tr.dataset.id = p.id;

      tr.appendChild(elemento('td', 'celda-codigo', p.codigo));

      const tdNombre = elemento('td');
      tdNombre.appendChild(elemento('div', 'cell-main', p.nombre));
      if (p.detalle) { tdNombre.appendChild(elemento('div', 'cell-sub', p.detalle)); }
      if (p.cantidad >= p.existencia) {
        tdNombre.appendChild(elemento('div', 'aviso-stock', 'Es todo lo que hay (' + p.existencia + ')'));
      }
      tr.appendChild(tdNombre);

      const tdCantidad = elemento('td');
      const control = elemento('div', 'qty-control d-inline-flex align-items-center');
      const menos = elemento('button', '', '−');
      menos.type = 'button';
      menos.setAttribute('aria-label', 'Una pieza menos de ' + p.nombre);
      menos.disabled = p.cantidad <= 1;
      menos.addEventListener('click', function () { cambiarCantidad(p.id, p.cantidad - 1); });
      const entrada = elemento('input');
      entrada.type = 'number';
      entrada.min = 1;
      entrada.max = p.existencia;
      entrada.value = p.cantidad;
      entrada.setAttribute('aria-label', 'Cantidad de ' + p.nombre);
      entrada.addEventListener('change', function () { cambiarCantidad(p.id, parseInt(entrada.value, 10)); });
      entrada.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); entrada.blur(); } });
      const mas = elemento('button', '', '+');
      mas.type = 'button';
      mas.setAttribute('aria-label', 'Una pieza más de ' + p.nombre);
      mas.disabled = p.cantidad >= p.existencia;
      mas.addEventListener('click', function () { cambiarCantidad(p.id, p.cantidad + 1); });
      control.append(menos, entrada, mas);
      tdCantidad.appendChild(control);
      tr.appendChild(tdCantidad);

      tr.appendChild(elemento('td', 'text-end text-nowrap', dinero(Math.round(p.precio * 100))));
      tr.appendChild(elemento('td', 'text-end text-nowrap fw-semibold text-dark', dinero(Math.round(p.precio * 100) * p.cantidad)));

      const tdQuitar = elemento('td', 'text-end');
      const quitar = elemento('button', 'btn-icon btn-icon-danger');
      quitar.type = 'button';
      quitar.title = 'Quitar';
      quitar.setAttribute('aria-label', 'Quitar ' + p.nombre);
      const icono = elemento('span', 'icono');
      icono.style.setProperty('--icono', "url('" + form.dataset.iconoQuitar + "')");
      quitar.appendChild(icono);
      quitar.addEventListener('click', function () { quitarProducto(p.id); });
      tdQuitar.appendChild(quitar);
      tr.appendChild(tdQuitar);

      cuerpo.appendChild(tr);
    });

    pintarResumen();
  }

  function pintarResumen() {
    const c = calcular();
    const productos = estado.carrito.length;
    const piezas = estado.carrito.reduce(function (s, p) { return s + p.cantidad; }, 0);
    const texto = productos === 0 ? 'Sin productos'
      : productos + (productos === 1 ? ' producto · ' : ' productos · ') + piezas + (piezas === 1 ? ' pieza' : ' piezas');

    $('conteoProductos').textContent = texto;
    $('resumenConteo').textContent = productos === 0 ? '0 productos · 0 piezas' : texto;
    $('resumenSubtotal').textContent = dinero(c.subtotal);
    $('resumenDescuento').textContent = '−' + dinero(c.descuento);
    $('resumenPorcentaje').textContent = estado.descuento > 0 ? '(' + estado.descuento + ' %)' : '';
    $('resumenIva').textContent = dinero(c.iva);
    $('resumenTotal').textContent = dinero(c.total);

    pintarPago(c.total);

    const vacio = productos === 0;
    $('botonRegistrar').disabled = vacio;
    form.querySelectorAll('.botones-cotizacion').forEach(function (b) { b.disabled = vacio; });
  }

  // ---------- Paga todo o paga una parte (sólo con cliente registrado) ----------

  function esCredito() {
    const elegido = form.querySelector('input[name=condicionPago]:checked');
    return !!elegido && elegido.value === 'CREDITO';
  }

  // Revisa "¿Cuánto paga ahora?": regresa el monto en centavos o null si no vale
  function pagoInicialValido(totalCentavos) {
    const texto = $('pagoInicial').value.trim();
    const valor = texto === '' ? 0 : Number(texto);
    if (!Number.isFinite(valor) || valor < 0 || Math.abs(valor * 100 - Math.round(valor * 100)) > 1e-6) { return null; }
    const centavos = Math.round(valor * 100);
    return centavos < totalCentavos ? centavos : null;
  }

  function pintarPago(totalCentavos) {
    if (totalCentavos === undefined) { totalCentavos = calcular().total; }
    const hayCliente = !!estado.cliente;
    const credito = $('opcionCredito').querySelector('input');
    credito.disabled = !hayCliente;
    $('opcionCredito').classList.toggle('is-disabled', !hayCliente);
    $('ayudaCredito').textContent = hayCliente
      ? 'El resto queda pendiente a cuenta de ' + estado.cliente.nombre + '.'
      : 'Elige un cliente registrado para poder dejar saldo pendiente.';
    if (!hayCliente && credito.checked) {
      form.querySelector('input[name=condicionPago][value=CONTADO]').checked = true;
    }

    const mostrar = esCredito();
    $('bloquePagoInicial').classList.toggle('d-none', !mostrar);
    if (!mostrar) { return; }

    const pago = pagoInicialValido(totalCentavos);
    const ayuda = $('ayudaPagoInicial');
    $('pagoInicial').classList.toggle('is-invalid', pago === null);
    if (pago === null) {
      ayuda.textContent = 'Debe ser menos que el total (' + dinero(totalCentavos) + '). Si paga todo, elige «Paga todo ahora».';
      ayuda.className = 'field-help m-0 mt-1 text-danger';
    } else {
      ayuda.textContent = 'Queda pendiente: ' + dinero(totalCentavos - pago) + (pago === 0 ? ' (no deja anticipo).' : '.');
      ayuda.className = 'field-help m-0 mt-1 saldo-pendiente';
    }
  }

  form.querySelectorAll('input[name=condicionPago]').forEach(function (r) {
    r.addEventListener('change', function () { pintarPago(); if (esCredito()) { $('pagoInicial').focus(); } });
  });
  $('pagoInicial').addEventListener('input', function () { pintarPago(); });
  $('pagoInicial').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });

  function pintarCliente() {
    const hay = !!estado.cliente;
    $('clienteElegido').classList.toggle('d-none', !hay);
    $('clienteBuscador').classList.toggle('d-none', hay);
    $('clienteEstado').textContent = hay ? 'Cliente seleccionado' : 'Opcional';
    $('clienteEstado').classList.toggle('text-primary-app', hay);
    if (hay) {
      $('clienteElegidoNombre').textContent = estado.cliente.nombre;
      $('clienteElegidoDetalle').textContent = estado.cliente.codigo + ' · ' + estado.cliente.telefono;
    }
    pintarPago();
  }

  // ---------- Cambios en la lista ----------

  function agregarProducto(p) {
    const ya = estado.carrito.find(function (x) { return x.id === p.id; });
    if (ya) {
      cambiarCantidad(p.id, ya.cantidad + 1);
    } else if (p.existencia > 0) {
      estado.carrito.push(Object.assign({}, p, { cantidad: 1 }));
      pintarCarrito();
    }
  }

  function cambiarCantidad(id, cantidad) {
    const p = estado.carrito.find(function (x) { return x.id === id; });
    if (!p) { return; }
    if (!Number.isFinite(cantidad) || cantidad < 1) { cantidad = 1; }
    p.cantidad = Math.min(cantidad, p.existencia);
    pintarCarrito();
  }

  function quitarProducto(id) {
    estado.carrito = estado.carrito.filter(function (x) { return x.id !== id; });
    pintarCarrito();
  }

  // ---------- Buscadores ----------

  function buscador(opciones) {
    let espera = null;
    let ultimaBusqueda = '';
    let resultados = [];

    function cerrar() {
      opciones.lista.classList.add('d-none');
      opciones.lista.replaceChildren();
    }

    function mostrar(items) {
      resultados = items;
      opciones.lista.replaceChildren();
      if (items.length === 0) {
        opciones.lista.appendChild(elemento('div', 'result-item text-muted', opciones.vacio));
      }
      items.forEach(function (item) {
        const boton = elemento('button', 'result-item d-flex align-items-center justify-content-between gap-3');
        boton.type = 'button';
        boton.setAttribute('role', 'option');
        const textos = elemento('div');
        textos.appendChild(elemento('div', 'result-name', opciones.nombre(item)));
        textos.appendChild(elemento('div', 'result-detail', opciones.detalle(item)));
        boton.appendChild(textos);
        if (opciones.derecha) { boton.appendChild(elemento('div', 'result-precio', opciones.derecha(item))); }
        boton.disabled = opciones.deshabilitado ? opciones.deshabilitado(item) : false;
        boton.addEventListener('click', function () { opciones.elegir(item); cerrar(); opciones.entrada.value = ''; opciones.entrada.focus(); });
        opciones.lista.appendChild(boton);
      });
      opciones.lista.classList.remove('d-none');
    }

    function mensaje(texto, esError) {
      opciones.lista.replaceChildren(elemento('div', 'result-item ' + (esError ? 'text-danger' : 'text-muted'), texto));
      opciones.lista.classList.remove('d-none');
    }

    // desdeBoton: con el botón "Consultar" se avisa si falta texto (o se listan los recientes, si se permite)
    function buscar(desdeBoton) {
      const texto = opciones.entrada.value.trim();
      const listarRecientes = desdeBoton && texto === '' && opciones.permiteVacio;
      if (texto.length < 2 && !listarRecientes) {
        if (desdeBoton) { mensaje(opciones.faltaTexto, false); } else { cerrar(); }
        return Promise.resolve([]);
      }
      ultimaBusqueda = texto;
      return fetch(opciones.url + '?q=' + encodeURIComponent(texto), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (r) {
          const esJson = (r.headers.get('content-type') || '').indexOf('application/json') !== -1;
          if (r.status === 401 || r.status === 419 || (r.ok && !esJson)) { throw new Error('sesion'); }
          if (!r.ok) { throw new Error('error ' + r.status); }
          return r.json();
        })
        .then(function (items) {
          if (texto === ultimaBusqueda) { mostrar(items); }
          return items;
        })
        .catch(function (error) {
          mensaje(error.message === 'sesion'
            ? 'Tu sesión se cerró. Recarga la página e inicia sesión otra vez.'
            : 'No se pudo buscar (' + error.message + '). Recarga la página; si sigue igual, avisa al encargado del sistema.', true);
          return [];
        });
    }

    opciones.entrada.addEventListener('input', function () {
      clearTimeout(espera);
      espera = setTimeout(buscar, 250);
    });

    // Enter: busca ya; si sólo hay un resultado, lo elige (sirve con lector de código de barras)
    opciones.entrada.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { cerrar(); return; }
      if (e.key !== 'Enter') { return; }
      e.preventDefault();
      clearTimeout(espera);
      buscar(true).then(function (items) {
        const elegibles = items.filter(function (i) { return !(opciones.deshabilitado && opciones.deshabilitado(i)); });
        if (items.length === 1 && elegibles.length === 1) {
          opciones.elegir(elegibles[0]);
          cerrar();
          opciones.entrada.value = '';
        }
      });
    });

    opciones.boton.addEventListener('click', function () { clearTimeout(espera); buscar(true); });

    return { cerrar: cerrar, resultados: function () { return resultados; } };
  }

  buscador({
    entrada: $('buscarCliente'),
    boton: $('consultarCliente'),
    lista: $('resultadosClientes'),
    url: form.dataset.buscarClientes,
    vacio: 'Ningún cliente coincide. Puedes registrarlo como nuevo.',
    faltaTexto: 'Escribe al menos 2 letras o números.',
    permiteVacio: true, // "Consultar cliente" sin texto muestra los últimos registrados
    nombre: function (c) { return c.nombre; },
    detalle: function (c) { return c.codigo + ' · ' + c.telefono; },
    elegir: function (c) { estado.cliente = c; pintarCliente(); }
  });

  buscador({
    entrada: $('buscarProducto'),
    boton: $('consultarProducto'),
    lista: $('resultadosProductos'),
    url: form.dataset.buscarProductos,
    vacio: 'Ninguna refacción coincide.',
    faltaTexto: 'Escribe al menos 2 letras o números del nombre, código o marca.',
    nombre: function (p) { return p.codigo + ' · ' + p.nombre; },
    detalle: function (p) {
      return (p.detalle ? p.detalle + ' · ' : '') + (p.existencia > 0 ? p.existencia + ' en existencia' : 'Agotado');
    },
    derecha: function (p) { return dinero(Math.round(p.precio * 100)); },
    deshabilitado: function (p) { return p.existencia < 1; },
    elegir: agregarProducto
  });

  $('clienteQuitar').addEventListener('click', function () {
    estado.cliente = null;
    pintarCliente();
    $('buscarCliente').focus();
  });

  // ---------- Descuento ----------

  function aplicarDescuento() {
    const entrada = $('descuento');
    const texto = entrada.value.trim();
    const valor = texto === '' ? 0 : Number(texto);
    const valido = Number.isFinite(valor) && valor >= 0 && valor <= 100 && Math.round(valor * 100) === valor * 100;

    entrada.classList.toggle('is-invalid', !valido);
    $('descuentoAyuda').textContent = valido
      ? (valor > 0 ? 'Descuento de ' + valor + ' % aplicado.' : 'Porcentaje de 0 a 100.')
      : 'Escribe un número de 0 a 100 (máximo 2 decimales).';
    if (valido) {
      estado.descuento = valor;
      pintarResumen();
    }
  }

  $('aplicarDescuento').addEventListener('click', aplicarDescuento);
  $('descuento').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); aplicarDescuento(); }
  });

  // ---------- Enviar ----------

  function oculto(nombre, valor) {
    const e = document.createElement('input');
    e.type = 'hidden';
    e.name = nombre;
    e.value = valor;
    return e;
  }

  form.addEventListener('submit', function (e) {
    if (estado.carrito.length === 0) {
      e.preventDefault();
      return;
    }

    // Si escribió un descuento pero no le dio "Aplicar", se aplica ahora para que lo vea
    if ($('descuento').value.trim() !== String(estado.descuento === 0 ? '' : estado.descuento)) {
      aplicarDescuento();
      if ($('descuento').classList.contains('is-invalid')) {
        e.preventDefault();
        $('descuento').focus();
        return;
      }
    }

    const campos = $('camposOcultos');
    campos.replaceChildren();
    if (estado.cliente) { campos.appendChild(oculto('cliente', estado.cliente.id)); }
    estado.carrito.forEach(function (p, i) {
      campos.appendChild(oculto('productos[' + i + '][id]', p.id));
      campos.appendChild(oculto('productos[' + i + '][cantidad]', p.cantidad));
    });
    campos.appendChild(oculto('descuento', estado.descuento));

    // Pago: todo o una parte. "Mostrar cotización" no lo necesita.
    const credito = esCredito();
    const quien = e.submitter;
    const esCobro = !quien || (!quien.getAttribute('formtarget') && !quien.getAttribute('formaction'));
    if (credito && esCobro) {
      const pago = pagoInicialValido(calcular().total);
      if (pago === null) {
        e.preventDefault();
        $('pagoInicial').focus();
        return;
      }
      campos.appendChild(oculto('pagoInicial', (pago / 100).toFixed(2)));
    }
    campos.appendChild(oculto('condicion', credito ? 'CREDITO' : 'CONTADO'));
    campos.appendChild(oculto('totalEsperado', (calcular().total / 100).toFixed(2)));

    // "Mostrar cotización" se imprime en un marco invisible (javascript/imprimir.js): esta página sigue igual
    const boton = e.submitter;
    if (boton && !boton.getAttribute('formtarget')) {
      form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
      boton.textContent = 'Guardando…';
    }
  });

  // ---------- Registrar cliente sin salir de la venta ----------

  const formCliente = $('formClienteRapido');
  if (formCliente && form.dataset.registrarCliente) {
    const modal = $('modalClienteRapido');
    const errores = $('clienteRapidoErrores');
    const guardar = $('clienteRapidoGuardar');

    modal.addEventListener('show.bs.modal', function () {
      formCliente.reset();
      errores.classList.add('d-none');
      // Lo que se buscó sirve de nombre o teléfono
      const buscado = $('buscarCliente').value.trim();
      if (/\d{4,}/.test(buscado)) { $('clienteRapidoTelefono').value = buscado; } else { $('clienteRapidoNombre').value = buscado; }
    });
    modal.addEventListener('shown.bs.modal', function () { $('clienteRapidoNombre').focus(); });

    formCliente.addEventListener('submit', function (e) {
      e.preventDefault();
      guardar.disabled = true;
      errores.classList.add('d-none');

      fetch(form.dataset.registrarCliente, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: new FormData(formCliente)
      })
        .then(function (r) {
          return r.json().catch(function () { return {}; }).then(function (datos) { return { estado: r.status, datos: datos }; });
        })
        .then(function (res) {
          if (res.estado === 201) {
            estado.cliente = res.datos;
            pintarCliente();
            bootstrap.Modal.getOrCreateInstance(modal).hide();
            return;
          }
          const lista = res.datos.errores || [res.estado === 419
            ? 'La sesión expiró. Recarga la página.'
            : 'No se pudo guardar el cliente. Intenta de nuevo.'];
          errores.replaceChildren();
          lista.forEach(function (t) { errores.appendChild(elemento('div', '', t)); });
          errores.classList.remove('d-none');
        })
        .catch(function () {
          errores.textContent = 'No hay conexión con el servidor.';
          errores.classList.remove('d-none');
        })
        .finally(function () { guardar.disabled = false; });
    });
  }

  // Cerrar las listas de resultados al hacer clic fuera
  document.addEventListener('click', function (e) {
    ['resultadosClientes', 'resultadosProductos'].forEach(function (id) {
      const lista = $(id);
      const buscadorDe = lista.parentElement;
      if (!buscadorDe.contains(e.target)) { lista.classList.add('d-none'); }
    });
  });

  // ---------- Al cargar ----------

  const descuentoAnterior = $('descuento').value.trim();
  if (descuentoAnterior !== '') { aplicarDescuento(); }
  pintarCliente();
  pintarCarrito();
})();

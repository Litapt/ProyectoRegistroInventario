// Ventana "Editar venta" (Consultar ventas).
// Las cuentas son las mismas que App\Support\CalculoVenta; el servidor vuelve a calcular todo.
(function () {
  const modal = document.getElementById('modalEditarVenta');
  if (!modal) {
    return;
  }

  const $ = function (id) { return document.getElementById(id); };
  const form = $('formEditarVenta');
  let estado = { cliente: null, renglones: [], descuento: 0, descuentoAntes: 0, totalAntes: 0 };

  function elemento(etiqueta, clase, texto) {
    const e = document.createElement(etiqueta);
    if (clase) { e.className = clase; }
    if (texto !== undefined && texto !== null) { e.textContent = texto; }
    return e;
  }

  // ---------- Cuentas (centavos, igual que CalculoVenta.php) ----------
  function dinero(centavos) {
    return '$' + (centavos / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function porcentaje(centavos, puntos) {
    return Math.floor((centavos * puntos * 2 + 10000) / 20000);
  }
  function calcular() {
    const puntos = Math.round(estado.descuento * 100);
    const lineas = estado.renglones.map(function (r) {
      return { subtotal: Math.round(r.precio * 100) * r.cantidad, tasa: Math.round(r.tasa * 100) };
    });
    const subtotal = lineas.reduce(function (s, l) { return s + l.subtotal; }, 0);
    const descuento = porcentaje(subtotal, puntos);
    let repartido = 0;
    let iva = 0;
    lineas.forEach(function (l, i) {
      const d = i === lineas.length - 1 ? descuento - repartido : porcentaje(l.subtotal, puntos);
      repartido += d;
      iva += porcentaje(l.subtotal - d, l.tasa);
    });
    return { subtotal: subtotal, descuento: descuento, iva: iva, total: subtotal - descuento + iva };
  }

  // ---------- Pintar ----------
  function pintarCliente() {
    const c = estado.cliente;
    $('editarClienteNombre').textContent = c ? c.nombre : 'Público en general';
    $('editarClienteDetalle').textContent = c ? c.codigo + ' · ' + c.telefono : 'Sin cliente';
    $('editarClienteQuitar').classList.toggle('d-none', !c);
    $('editarClienteElegido').classList.remove('d-none');
    $('editarClienteBuscador').classList.add('d-none');
  }

  function pintarRenglones() {
    const caja = $('editarRenglones');
    caja.replaceChildren();
    if (estado.renglones.length === 0) {
      caja.appendChild(elemento('div', 'text-muted text-center py-2', 'Agrega al menos un producto. Para quitar todo, mejor cancela la venta.'));
    }

    estado.renglones.forEach(function (r) {
      const fila = elemento('div', 'renglon-edicion');
      fila.dataset.id = r.id;

      const nombre = elemento('div');
      nombre.appendChild(elemento('div', 'cell-main', r.nombre));
      nombre.appendChild(elemento('div', 'cell-sub', r.codigo + (r.detalle ? ' · ' + r.detalle : '') + (r.yaEstaba ? '' : ' · nuevo')));
      fila.appendChild(nombre);

      const control = elemento('div', 'qty-control d-inline-flex align-items-center');
      const menos = elemento('button', '', '−');
      menos.type = 'button';
      menos.setAttribute('aria-label', 'Una pieza menos de ' + r.nombre);
      menos.disabled = r.cantidad <= 1;
      menos.addEventListener('click', function () { cambiar(r.id, r.cantidad - 1); });
      const entrada = elemento('input');
      entrada.type = 'number';
      entrada.min = 1;
      entrada.max = r.maximo;
      entrada.value = r.cantidad;
      entrada.setAttribute('aria-label', 'Cantidad de ' + r.nombre);
      entrada.addEventListener('change', function () { cambiar(r.id, parseInt(entrada.value, 10)); });
      entrada.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); entrada.blur(); } });
      const mas = elemento('button', '', '+');
      mas.type = 'button';
      mas.setAttribute('aria-label', 'Una pieza más de ' + r.nombre);
      mas.disabled = r.cantidad >= r.maximo;
      mas.addEventListener('click', function () { cambiar(r.id, r.cantidad + 1); });
      control.append(menos, entrada, mas);
      fila.appendChild(control);

      fila.appendChild(elemento('div', 'text-end text-nowrap fw-semibold text-dark', dinero(Math.round(r.precio * 100) * r.cantidad)));

      const quitar = elemento('button', 'btn-icon btn-icon-danger');
      quitar.type = 'button';
      quitar.title = 'Quitar';
      quitar.setAttribute('aria-label', 'Quitar ' + r.nombre);
      const icono = elemento('span', 'icono');
      icono.style.setProperty('--icono', "url('" + modal.dataset.iconoQuitar + "')");
      quitar.appendChild(icono);
      quitar.addEventListener('click', function () {
        estado.renglones = estado.renglones.filter(function (x) { return x.id !== r.id; });
        pintarRenglones();
      });
      fila.appendChild(quitar);

      caja.appendChild(fila);
    });

    const c = calcular();
    $('editarSubtotal').textContent = dinero(c.subtotal);
    $('editarDescuentoFila').classList.toggle('d-none', estado.descuento <= 0);
    $('editarPorcentaje').textContent = estado.descuento;
    $('editarDescuento').textContent = '−' + dinero(c.descuento);
    $('editarIva').textContent = dinero(c.iva);
    $('editarTotal').textContent = dinero(c.total);
    $('editarTotalAntes').textContent = dinero(Math.round(estado.totalAntes * 100));

    const vacio = estado.renglones.length === 0;
    $('editarEnviarSolicitud').disabled = vacio;
    $('editarAutorizar').disabled = vacio;
  }

  function cambiar(id, cantidad) {
    const r = estado.renglones.find(function (x) { return x.id === id; });
    if (!r) { return; }
    if (!Number.isFinite(cantidad) || cantidad < 1) { cantidad = 1; }
    r.cantidad = Math.min(cantidad, r.maximo);
    pintarRenglones();
  }

  function cargar(url, datos) {
    form.action = url;
    $('editarFolio').textContent = 'Venta ' + datos.folio;
    estado = {
      cliente: datos.cliente,
      renglones: datos.renglones.map(function (r) { return Object.assign({}, r); }),
      descuento: datos.descuento || 0,
      descuentoAntes: datos.descuentoAntes || 0,
      totalAntes: datos.totalAntes
    };
    $('editarDescuentoInput').value = estado.descuento > 0 ? estado.descuento : '';
    $('editarDescuentoInput').classList.remove('is-invalid');
    pintarCliente();
    pintarDescuentoAyuda(true);
    pintarRenglones();
  }

  // ---------- Descuento (se recalcula al escribir) ----------
  function leerDescuento() {
    const texto = $('editarDescuentoInput').value.trim();
    const valor = texto === '' ? 0 : Number(texto);
    const valido = Number.isFinite(valor) && valor >= 0 && valor <= 100 && Math.round(valor * 100) === valor * 100;
    return { valor: valor, valido: valido };
  }

  function pintarDescuentoAyuda(valido) {
    $('editarDescuentoAyuda').textContent = valido
      ? 'Antes: ' + estado.descuentoAntes + ' %. Porcentaje de 0 a 100.'
      : 'Escribe un número de 0 a 100 (máximo 2 decimales).';
    $('editarDescuentoAyuda').classList.toggle('text-danger', !valido);
  }

  $('editarDescuentoInput').addEventListener('input', function () {
    const d = leerDescuento();
    $('editarDescuentoInput').classList.toggle('is-invalid', !d.valido);
    pintarDescuentoAyuda(d.valido);
    if (d.valido) {
      estado.descuento = d.valor;
      pintarRenglones();
    }
  });
  $('editarDescuentoInput').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });

  // ---------- Buscadores (Enter o al escribir) ----------
  function buscador(entrada, lista, url, pintar, elegir) {
    let espera = null;
    function cerrar() { lista.classList.add('d-none'); lista.replaceChildren(); }
    function buscar() {
      const texto = entrada.value.trim();
      if (texto.length < 2) { cerrar(); return; }
      fetch(url + '?q=' + encodeURIComponent(texto), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) {
          if (!r.ok || (r.headers.get('content-type') || '').indexOf('json') === -1) { throw new Error(); }
          return r.json();
        })
        .then(function (items) {
          lista.replaceChildren();
          if (items.length === 0) { lista.appendChild(elemento('div', 'result-item text-muted', 'Sin resultados.')); }
          items.forEach(function (item) {
            const b = elemento('button', 'result-item d-flex justify-content-between gap-3');
            b.type = 'button';
            const info = pintar(item);
            const textos = elemento('div');
            textos.appendChild(elemento('div', 'result-name', info.nombre));
            textos.appendChild(elemento('div', 'result-detail', info.detalle));
            b.appendChild(textos);
            if (info.derecha) { b.appendChild(elemento('div', 'result-precio', info.derecha)); }
            b.disabled = !!info.deshabilitado;
            b.addEventListener('click', function () { elegir(item); cerrar(); entrada.value = ''; });
            lista.appendChild(b);
          });
          lista.classList.remove('d-none');
        })
        .catch(function () {
          lista.replaceChildren(elemento('div', 'result-item text-danger', 'No se pudo buscar. Recarga la página.'));
          lista.classList.remove('d-none');
        });
    }
    entrada.addEventListener('input', function () { clearTimeout(espera); espera = setTimeout(buscar, 250); });
    entrada.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); clearTimeout(espera); buscar(); }
      if (e.key === 'Escape') { e.stopPropagation(); cerrar(); }
    });
  }

  buscador($('editarBuscarCliente'), $('editarResultadosClientes'), modal.dataset.buscarClientes,
    function (c) { return { nombre: c.nombre, detalle: c.codigo + ' · ' + c.telefono }; },
    function (c) { estado.cliente = c; pintarCliente(); });

  buscador($('editarBuscarProducto'), $('editarResultadosProductos'), modal.dataset.buscarProductos,
    function (p) {
      const ya = estado.renglones.find(function (r) { return r.id === p.id; });
      return {
        nombre: p.codigo + ' · ' + p.nombre,
        detalle: ya ? 'Ya está en la venta' : (p.existencia > 0 ? p.existencia + ' en existencia' : 'Agotado'),
        derecha: dinero(Math.round(p.precio * 100)),
        deshabilitado: !!ya || p.existencia < 1
      };
    },
    function (p) {
      estado.renglones.push({
        id: p.id, codigo: p.codigo, nombre: p.nombre, detalle: p.detalle, precio: p.precio, tasa: p.tasa,
        cantidad: 1, maximo: p.existencia, yaEstaba: false
      });
      pintarRenglones();
    });

  $('editarClienteCambiar').addEventListener('click', function () {
    $('editarClienteElegido').classList.add('d-none');
    $('editarClienteBuscador').classList.remove('d-none');
    $('editarBuscarCliente').focus();
  });
  $('editarClienteQuitar').addEventListener('click', function () { estado.cliente = null; pintarCliente(); });

  // ---------- Abrir ----------
  modal.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    if (!boton || !boton.dataset.editar) { return; }
    cargar(boton.dataset.url, JSON.parse(boton.dataset.editar));
    ['editarMotivo', 'editarCorreo', 'editarContrasena'].forEach(function (id) { $(id).value = ''; });
    const errores = $('editarErrores');
    if (errores) { errores.remove(); }
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
    const boton = e.submitter;
    const accion = boton ? boton.value : 'solicitud';

    if (!leerDescuento().valido) {
      e.preventDefault();
      $('editarDescuentoInput').focus();
      return;
    }
    if (!$('editarMotivo').value.trim()) {
      e.preventDefault();
      $('editarMotivo').classList.add('is-invalid');
      $('editarMotivo').focus();
      return;
    }
    if (accion === 'autorizar' && (!$('editarCorreo').value.trim() || !$('editarContrasena').value)) {
      e.preventDefault();
      $('editarCorreo').classList.add('is-invalid');
      $('editarContrasena').classList.add('is-invalid');
      ($('editarCorreo').value.trim() ? $('editarContrasena') : $('editarCorreo')).focus();
      return;
    }

    const campos = $('editarOcultos');
    campos.replaceChildren(oculto('accion', accion), oculto('descuento', estado.descuento));
    if (estado.cliente) { campos.appendChild(oculto('cliente', estado.cliente.id)); }
    estado.renglones.forEach(function (r, i) {
      campos.appendChild(oculto('productos[' + i + '][id]', r.id));
      campos.appendChild(oculto('productos[' + i + '][cantidad]', r.cantidad));
    });

    // "Enviar solicitud" no usa la contraseña: no se manda
    if (accion !== 'autorizar') { $('editarContrasena').value = ''; }

    form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
    if (boton) { boton.textContent = 'Guardando…'; }
  });

  ['editarMotivo', 'editarCorreo', 'editarContrasena'].forEach(function (id) {
    $(id).addEventListener('input', function () { $(id).classList.remove('is-invalid'); });
  });

  // Regresó con un error: se vuelve a abrir con lo que se había escrito
  if (modal.dataset.reabrir) {
    const r = JSON.parse(modal.dataset.reabrir);
    cargar(r.url, r.datos);
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
})();

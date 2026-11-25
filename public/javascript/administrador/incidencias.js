// Alertas · Incidencias: ventana de detalle (en revisión / resolver) y "Registrar incidencia".
(function () {
  const $ = function (id) { return document.getElementById(id); };

  function elemento(etiqueta, clase, texto) {
    const e = document.createElement(etiqueta);
    if (clase) { e.className = clase; }
    if (texto !== undefined && texto !== null) { e.textContent = texto; }
    return e;
  }

  // ---------- Detalle ----------
  const detalle = $('modalIncidencia');
  if (detalle) {
    const estados = { PENDIENTE: 'Abierta', EN_REVISION: 'En revisión', RESUELTA: 'Resuelta' };

    function cargar(d, urlRevision, urlResolver, limpiar) {
      $('incFolio').textContent = d.folio;
      $('incSubtitulo').textContent = d.tipo + ' · ' + d.fecha;
      $('incProducto').textContent = d.codigo + ' · ' + d.producto;
      $('incCantidad').textContent = d.cantidad + (d.cantidad === 1 ? ' pieza' : ' piezas');
      $('incPrioridad').textContent = d.prioridad;
      $('incReporto').textContent = d.reporto || '—';
      $('incEstado').textContent = (estados[d.estado] || d.estado) + (d.responsable ? ' · ' + d.responsable : '');
      $('incDescripcion').textContent = d.descripcion;

      const resuelta = d.estado === 'RESUELTA';
      $('incResuelta').classList.toggle('d-none', !resuelta);
      $('incResolucion').textContent = resuelta
        ? d.fechaResolucion + ' · ' + d.resolucion + (d.piezasDeBaja > 0 ? ' · Se dieron de baja ' + d.piezasDeBaja + ' piezas del inventario.' : '')
        : '';
      $('formResolver').classList.toggle('d-none', resuelta);
      $('incBotonResolver').classList.toggle('d-none', resuelta);
      $('formRevision').classList.toggle('d-none', d.estado !== 'PENDIENTE');

      $('formRevision').action = urlRevision;
      $('formResolver').action = urlResolver;
      $('incBaja').max = d.existencia;
      $('incBajaAyuda').textContent = 'Pon 0 si las piezas siguen sirviendo. Hay ' + d.existencia + ' en inventario.';
      if (limpiar) {
        $('incComentario').value = '';
        $('incBaja').value = 0;
        const errores = $('incErrores');
        if (errores) { errores.remove(); }
      }
      $('incBotonResolver').disabled = false;
    }

    detalle.addEventListener('show.bs.modal', function (event) {
      const b = event.relatedTarget;
      if (!b || !b.dataset.incidencia) { return; }
      cargar(JSON.parse(b.dataset.incidencia), b.dataset.revision, b.dataset.resolver, true);
    });

    $('formResolver').addEventListener('submit', function (e) {
      if ($('incComentario').value.trim().length < 5) {
        e.preventDefault();
        $('incComentario').classList.add('is-invalid');
        $('incComentario').focus();
        return;
      }
      $('incBotonResolver').disabled = true;
    });
    $('incComentario').addEventListener('input', function () { this.classList.remove('is-invalid'); });

    if (detalle.dataset.reabrir) {
      const r = JSON.parse(detalle.dataset.reabrir);
      cargar(r.datos, r.revision, r.resolver, false);
      bootstrap.Modal.getOrCreateInstance(detalle).show();
    }
  }

  // ---------- Registrar ----------
  const registrar = $('modalRegistrarIncidencia');
  if (registrar) {
    let espera = null;

    function elegir(p) {
      $('regProducto').value = p.id;
      $('regElegidoNombre').textContent = p.nombre;
      $('regElegidoDetalle').textContent = p.codigo + ' · ' + p.existencia + ' en existencia';
      $('regElegido').classList.remove('d-none');
      $('regBuscador').classList.add('d-none');
      $('regResultados').classList.add('d-none');
    }

    function buscar() {
      const texto = $('regBuscar').value.trim();
      const lista = $('regResultados');
      if (texto.length < 2) { lista.classList.add('d-none'); return; }
      fetch(registrar.dataset.buscarProductos + '?q=' + encodeURIComponent(texto), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) {
          if (!r.ok || (r.headers.get('content-type') || '').indexOf('json') === -1) { throw new Error(); }
          return r.json();
        })
        .then(function (items) {
          lista.replaceChildren();
          if (items.length === 0) { lista.appendChild(elemento('div', 'result-item text-muted', 'Ningún producto coincide.')); }
          items.forEach(function (p) {
            const b = elemento('button', 'result-item d-flex justify-content-between gap-3');
            b.type = 'button';
            const t = elemento('div');
            t.appendChild(elemento('div', 'result-name', p.codigo + ' · ' + p.nombre));
            t.appendChild(elemento('div', 'result-detail', (p.detalle ? p.detalle + ' · ' : '') + p.existencia + ' en existencia'));
            b.appendChild(t);
            b.addEventListener('click', function () { elegir(p); });
            lista.appendChild(b);
          });
          lista.classList.remove('d-none');
        })
        .catch(function () {
          lista.replaceChildren(elemento('div', 'result-item text-danger', 'No se pudo buscar. Recarga la página.'));
          lista.classList.remove('d-none');
        });
    }

    $('regBuscar').addEventListener('input', function () { clearTimeout(espera); espera = setTimeout(buscar, 250); });
    $('regBuscar').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(espera); buscar(); } });
    $('regCambiar').addEventListener('click', function () {
      $('regProducto').value = '';
      $('regElegido').classList.add('d-none');
      $('regBuscador').classList.remove('d-none');
      $('regBuscar').focus();
    });

    $('formRegistrarIncidencia').addEventListener('submit', function (e) {
      if (!$('regProducto').value) {
        e.preventDefault();
        $('regBuscar').classList.add('is-invalid');
        $('regBuscar').focus();
        return;
      }
      $('regGuardar').disabled = true;
    });

    if (registrar.dataset.reabrir) {
      bootstrap.Modal.getOrCreateInstance(registrar).show();
    }
  }
})();

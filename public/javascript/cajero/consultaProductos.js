// Cajero · Ventana "Consultar productos" (Figma: "Menú consulta"). Sólo para ver.
// Pide los productos al servidor con los filtros, sin salir de la pantalla en la que está el cajero.

(function () {
  const modal = document.getElementById('modalConsultaProductos');
  if (!modal) { return; }
  const $ = (id) => document.getElementById(id);
  const form = $('formConsultaProductos');
  const insignias = { disponible: ['badge-ok', 'Disponible'], bajo: ['badge-warning', 'Bajo'], agotado: ['badge-danger', 'Agotado'] };
  let pagina = 1;
  let paginas = 1;
  let pidiendo = null;
  let espera = null;

  function celda(texto, clase, sub) {
    const td = document.createElement('td');
    if (clase) { td.className = clase; }
    const principal = document.createElement('div');
    principal.textContent = texto === null || texto === undefined ? '' : texto;
    td.appendChild(principal);
    if (sub) {
      const chico = document.createElement('div');
      chico.className = 'cell-sub';
      chico.textContent = sub;
      td.appendChild(chico);
    }
    return td;
  }

  function mensaje(texto) {
    const fila = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = 7;
    td.className = 'text-center py-5 text-muted';
    td.textContent = texto;
    fila.appendChild(td);
    $('consultaResultados').replaceChildren(fila);
  }

  function pintar(datos) {
    paginas = datos.paginas;
    $('consultaConteo').textContent = datos.total + (datos.total === 1 ? ' producto' : ' productos') + ' · precios en MXN';
    $('consultaMostrando').textContent = datos.total ? 'Mostrando ' + datos.desde + '–' + datos.hasta + ' de ' + datos.total : '';
    $('consultaPagina').textContent = datos.paginas > 1 ? 'Página ' + datos.pagina + ' de ' + datos.paginas : '';
    $('consultaAnterior').disabled = datos.pagina <= 1;
    $('consultaSiguiente').disabled = datos.pagina >= datos.paginas;

    if (datos.productos.length === 0) {
      mensaje('Ningún producto coincide con la búsqueda.');
      return;
    }

    const filas = datos.productos.map(function (p) {
      const fila = document.createElement('tr');
      fila.appendChild(celda(p.codigo, 'text-muted small text-nowrap'));
      fila.appendChild(celda(p.nombre, 'cell-main', p.ubicacion ? 'Ubicación: ' + p.ubicacion : ''));
      fila.appendChild(celda(p.categoria, 'text-muted'));
      fila.appendChild(celda(p.marca, 'text-muted'));
      fila.appendChild(celda(p.vehiculo, 'small text-dark', p.anios));

      const stock = document.createElement('td');
      const insignia = document.createElement('span');
      const [clase, texto] = insignias[p.estado] || ['badge-ok', ''];
      insignia.className = 'badge-estado ' + clase;
      insignia.textContent = p.existencia + ' · ' + texto;
      stock.appendChild(insignia);
      fila.appendChild(stock);

      fila.appendChild(celda(p.precio, 'text-end text-nowrap text-dark fw-semibold', 'con IVA ' + p.precioConIva));
      return fila;
    });
    $('consultaResultados').replaceChildren(...filas);
  }

  function buscar() {
    if (pidiendo) { pidiendo.abort(); }
    pidiendo = new AbortController();
    const parametros = new URLSearchParams(new FormData(form));
    parametros.set('page', pagina);
    fetch(form.dataset.url + '?' + parametros.toString(), { headers: { Accept: 'application/json' }, signal: pidiendo.signal })
      .then(function (r) {
        if (r.status === 401 || r.status === 419) { throw new Error('sesion'); }
        return r.json();
      })
      .then(pintar)
      .catch(function (e) {
        if (e.name === 'AbortError') { return; }
        mensaje(e.message === 'sesion' ? 'Tu sesión se cerró. Recarga la página.' : 'No hay conexión con el servidor.');
      });
  }

  function desdeElPrincipio() { pagina = 1; buscar(); }

  form.addEventListener('submit', function (e) { e.preventDefault(); desdeElPrincipio(); });
  form.querySelectorAll('select').forEach(function (s) { s.addEventListener('change', desdeElPrincipio); });
  $('consultaBuscar').addEventListener('input', function () {
    clearTimeout(espera);
    espera = setTimeout(desdeElPrincipio, 300);
  });
  $('consultaLimpiar').addEventListener('click', function () { form.reset(); desdeElPrincipio(); });
  $('consultaAnterior').addEventListener('click', function () { if (pagina > 1) { pagina--; buscar(); } });
  $('consultaSiguiente').addEventListener('click', function () { if (pagina < paginas) { pagina++; buscar(); } });

  modal.addEventListener('show.bs.modal', function () { buscar(); });
  modal.addEventListener('shown.bs.modal', function () { $('consultaBuscar').focus(); });
})();

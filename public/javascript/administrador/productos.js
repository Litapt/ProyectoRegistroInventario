// Productos del administrador: filtros, ventana Registrar/Editar y ventana Eliminar.
(function () {
  // ---------- Filtros: al cambiar un select se busca solo ----------
  document.querySelectorAll('.filtro-auto').forEach(function (select) {
    select.addEventListener('change', function () {
      document.getElementById('formFiltros').submit();
    });
  });

  // ---------- Ventana Registrar / Editar ----------
  const modal = document.getElementById('modalProducto');
  if (!modal) {
    return;
  }

  const form = document.getElementById('formProducto');
  const lista = document.getElementById('listaVehiculos');
  const vacio = document.getElementById('vehiculosVacio');
  const errorVehiculo = document.getElementById('vehiculoError');
  const inputImagen = document.getElementById('productoImagen');
  const vista = document.getElementById('imagenVista');
  const indicaciones = document.getElementById('imagenIndicaciones');
  const cambiar = document.getElementById('imagenCambiar');
  const stock = document.getElementById('productoStock');
  const maxBytes = 5 * 1024 * 1024;
  let siguiente = lista.children.length; // índice para vehiculos[n][...]

  function campo(id) {
    return document.getElementById(id);
  }

  function texto(etiqueta, valor) {
    const elemento = document.createElement(etiqueta);
    elemento.textContent = valor;
    return elemento;
  }

  function actualizarVacio() {
    vacio.classList.toggle('d-none', lista.children.length > 0);
  }

  // ---------- Vehículos: marca → modelos de esa marca ----------
  const modelosPorMarca = JSON.parse(document.getElementById('modelosPorMarca').textContent || '{}');
  const selMarca = campo('vehiculoMarca');
  const selModelo = campo('vehiculoModelo');

  function llenarModelos() {
    const modelos = modelosPorMarca[selMarca.value] || [];
    selModelo.innerHTML = '';
    const primera = document.createElement('option');
    primera.value = '';
    primera.textContent = selMarca.value ? 'Elige el modelo' : 'Primero la marca';
    selModelo.appendChild(primera);
    modelos.forEach(function (m) {
      const opcion = document.createElement('option');
      opcion.value = m.id;
      opcion.textContent = m.nombre;
      selModelo.appendChild(opcion);
    });
    selModelo.disabled = !selMarca.value;
  }

  selMarca.addEventListener('change', llenarModelos);

  // Un "chip" con el vehículo y sus 3 campos ocultos (se crean con textContent: nada de HTML del usuario)
  function agregarChip(vehiculo) {
    const chip = document.createElement('span');
    chip.className = 'chip-vehiculo';
    const anios = vehiculo.desde + (vehiculo.hasta ? '–' + vehiculo.hasta : '');
    chip.appendChild(document.createTextNode(vehiculo.texto + ' · ' + anios + ' '));

    ['modelo', 'desde', 'hasta'].forEach(function (parte) {
      const oculto = document.createElement('input');
      oculto.type = 'hidden';
      oculto.name = 'vehiculos[' + siguiente + '][' + parte + ']';
      oculto.value = vehiculo[parte] === null || vehiculo[parte] === undefined ? '' : vehiculo[parte];
      chip.appendChild(oculto);
    });

    const quitar = texto('button', '×');
    quitar.type = 'button';
    quitar.className = 'chip-quitar';
    quitar.setAttribute('aria-label', 'Quitar vehículo');
    chip.appendChild(quitar);

    lista.appendChild(chip);
    siguiente++;
    actualizarVacio();
  }

  lista.addEventListener('click', function (event) {
    if (event.target.classList.contains('chip-quitar')) {
      event.target.closest('.chip-vehiculo').remove();
      actualizarVacio();
    }
  });

  campo('botonAgregarVehiculo').addEventListener('click', function () {
    const desde = campo('vehiculoDesde').value.trim();
    const hasta = campo('vehiculoHasta').value.trim();
    const anioMax = new Date().getFullYear() + 1;
    let error = '';

    if (!selMarca.value || !selModelo.value || !desde) {
      error = 'Elige la marca y el modelo, y escribe el año inicial del vehículo.';
    } else if (+desde < 1950 || +desde > anioMax || (hasta && (+hasta < 1950 || +hasta > anioMax))) {
      error = 'Los años deben estar entre 1950 y ' + anioMax + '.';
    } else if (hasta && +hasta < +desde) {
      error = 'El año final no puede ser menor que el inicial.';
    }

    errorVehiculo.textContent = error;
    errorVehiculo.classList.toggle('d-none', !error);
    if (error) {
      return;
    }

    agregarChip({
      modelo: selModelo.value,
      texto: selMarca.options[selMarca.selectedIndex].text + ' ' + selModelo.options[selModelo.selectedIndex].text,
      desde: desde,
      hasta: hasta
    });
    // Se queda la marca elegida (suelen agregarse varios modelos de la misma); se limpian modelo y años
    selModelo.value = '';
    campo('vehiculoDesde').value = '';
    campo('vehiculoHasta').value = '';
    selModelo.focus();
  });

  // Enter dentro de los campos del vehículo agrega el vehículo en vez de enviar el formulario
  document.getElementById('nuevoVehiculo').addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      campo('botonAgregarVehiculo').click();
    }
  });

  // ---------- Imagen: vista previa, arrastrar y soltar ----------
  function mostrarImagen(url) {
    vista.src = url || '';
    vista.classList.toggle('d-none', !url);
    indicaciones.classList.toggle('d-none', !!url);
    cambiar.classList.toggle('d-none', !url);
  }

  inputImagen.addEventListener('change', function () {
    const archivo = inputImagen.files[0];
    if (!archivo) {
      return;
    }
    if (!['image/png', 'image/jpeg'].includes(archivo.type) || archivo.size > maxBytes) {
      inputImagen.value = '';
      avisoImagen('Sólo se aceptan imágenes PNG o JPG de hasta 5 MB.');
      return;
    }
    avisoImagen('');
    mostrarImagen(URL.createObjectURL(archivo));
  });

  // Texto debajo de la caja de la imagen: normal o, si hay error, en rojo
  function avisoImagen(error) {
    const ayuda = campo('imagenAyuda');
    ayuda.textContent = error || 'PNG o JPG · Hasta 5 MB';
    ayuda.classList.toggle('text-danger', !!error);
  }

  const zona = document.querySelector('.zona-imagen');
  ['dragenter', 'dragover'].forEach(function (evento) {
    zona.addEventListener(evento, function (event) {
      event.preventDefault();
      zona.classList.add('is-dragging');
    });
  });
  ['dragleave', 'drop'].forEach(function (evento) {
    zona.addEventListener(evento, function () {
      zona.classList.remove('is-dragging');
    });
  });
  zona.addEventListener('drop', function (event) {
    event.preventDefault();
    if (event.dataTransfer.files.length) {
      inputImagen.files = event.dataTransfer.files;
      inputImagen.dispatchEvent(new Event('change'));
    }
  });

  // ---------- Abrir en modo Registrar o Editar ----------
  function limpiar() {
    // Se vacían a mano: form.reset() regresaría lo que se había escrito antes de un error
    form.querySelectorAll('input[type=text], input[type=number], input[type=file], select').forEach(function (e) {
      e.value = '';
    });
    avisoImagen('');
    lista.innerHTML = '';
    siguiente = 0;
    actualizarVacio();
    mostrarImagen('');
    llenarModelos();
    errorVehiculo.classList.add('d-none');
    form.querySelectorAll('.is-invalid').forEach(function (e) {
      e.classList.remove('is-invalid');
    });
    const errores = form.querySelector('.alert-danger');
    if (errores) {
      errores.remove();
    }
  }

  function modoRegistrar() {
    limpiar();
    form.action = modal.dataset.urlRegistrar;
    campo('productoMetodo').value = 'POST';
    campo('modalProductoTitulo').textContent = 'Registrar producto';
    campo('modalProductoSubtitulo').textContent = 'Completa los datos para agregar una refacción al inventario.';
    campo('productoGuardar').textContent = 'Guardar';
    campo('productoStockEtiqueta').textContent = 'Stock inicial';
    campo('productoStockAyuda').classList.add('d-none');
    stock.disabled = false;
    stock.value = '';
  }

  function modoEditar(url, datos) {
    limpiar();
    form.action = url;
    campo('productoMetodo').value = 'PUT';
    campo('modalProductoTitulo').textContent = 'Editar producto';
    campo('modalProductoSubtitulo').textContent = 'Actualiza los datos y la compatibilidad de la refacción.';
    campo('productoGuardar').textContent = 'Guardar cambios';
    campo('productoStockEtiqueta').textContent = 'Stock actual';
    campo('productoStockAyuda').classList.remove('d-none');

    campo('productoCodigo').value = datos.codigo;
    campo('productoNombre').value = datos.nombre;
    campo('productoCategoria').value = datos.categoria;
    campo('productoMarca').value = datos.marca;
    campo('productoPrecioCompra').value = datos.precioCompra;
    campo('productoPrecioVenta').value = datos.precioVenta;
    campo('productoStockMinimo').value = datos.stockMinimo === null ? '' : datos.stockMinimo;
    campo('productoUbicacion').value = datos.ubicacion;
    stock.value = datos.existencia;
    stock.disabled = true; // no se envía: el stock cambia con "Ajuste stock"
    datos.vehiculos.forEach(agregarChip);
    mostrarImagen(datos.imagen);
  }

  modal.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    if (!boton) {
      return; // se abrió solo porque regresó con errores: ya viene lleno
    }
    if (boton.dataset.modo === 'editar') {
      modoEditar(boton.dataset.url, JSON.parse(boton.dataset.producto));
    } else {
      modoRegistrar();
    }
  });

  // Evita enviar dos veces con doble clic
  form.addEventListener('submit', function () {
    campo('productoGuardar').disabled = true;
  });

  actualizarVacio();

  if (modal.dataset.reabrir) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
})();

// ---------- Ventana de autorización: Eliminar (desactivar) o Reactivar ----------
(function () {
  const modal = document.getElementById('modalDesactivar');
  if (!modal) {
    return;
  }

  const form = document.getElementById('formDesactivar');
  const boton = document.getElementById('desactivarBoton');

  const textos = {
    desactivar: {
      titulo: 'Eliminar producto',
      texto: '¿Seguro que deseas quitar este producto del inventario? Se desactiva: ya no aparecerá en ventas ni en las consultas, y sólo un gerente o administrador puede reactivarlo.',
      boton: 'Eliminar',
      clase: 'btn-app-danger'
    },
    reactivar: {
      titulo: 'Reactivar producto',
      texto: 'El producto volverá a aparecer en el inventario y en las ventas.',
      boton: 'Reactivar',
      clase: 'btn-app-primary'
    }
  };

  modal.addEventListener('show.bs.modal', function (event) {
    const origen = event.relatedTarget;
    const t = textos[origen.dataset.accion] || textos.desactivar;

    form.reset();
    form.action = origen.dataset.url;
    document.getElementById('modalDesactivarTitulo').textContent = t.titulo;
    document.getElementById('modalDesactivarTexto').textContent = t.texto;
    document.getElementById('desactivarNombre').textContent = origen.dataset.nombre;
    document.getElementById('desactivarDetalle').textContent = origen.dataset.detalle;
    boton.textContent = t.boton;
    boton.classList.remove('btn-app-danger', 'btn-app-primary');
    boton.classList.add(t.clase);
    boton.disabled = false;
  });

  form.addEventListener('submit', function () {
    boton.disabled = true;
  });
})();

// Ventana de autorización reutilizable (partials/modalAutorizacion.blade.php).
window.Autorizacion = (function () {
  const modal = document.getElementById('modalAutorizacion');
  if (!modal) {
    return null;
  }

  const form = document.getElementById('formAutorizacion');
  const boton = document.getElementById('autorizacionBoton');
  let formularioDestino = null; // si se llenó desde JavaScript (modo "Editar")

  function poner(opciones) {
    form.reset();
    formularioDestino = opciones.formulario || null;
    form.action = opciones.url || '';
    document.getElementById('autorizacionTitulo').textContent = opciones.titulo || 'Autorización';
    document.getElementById('autorizacionTexto').textContent = opciones.texto || '';
    document.getElementById('autorizacionNombre').textContent = opciones.nombre || '';
    document.getElementById('autorizacionDetalle').textContent = opciones.detalle || '';
    boton.textContent = opciones.boton || 'Confirmar';
    boton.classList.remove('btn-app-danger', 'btn-app-primary');
    boton.classList.add(opciones.clase || 'btn-app-danger');
    boton.disabled = false;
  }

  // Modo 1: abierta por un botón con data-*
  modal.addEventListener('show.bs.modal', function (event) {
    const origen = event.relatedTarget;
    if (!origen) {
      return; // modo 2: ya se llenó con abrir()
    }
    poner({
      url: origen.dataset.url,
      titulo: origen.dataset.titulo,
      texto: origen.dataset.texto,
      nombre: origen.dataset.nombre,
      detalle: origen.dataset.detalle,
      boton: origen.dataset.boton,
      clase: origen.dataset.clase
    });
  });

  form.addEventListener('submit', function (event) {
    boton.disabled = true; // evita mandar dos veces
    if (!formularioDestino) {
      return; // modo 1: se manda este formulario tal cual
    }
    // Modo 2: se copian correo y contraseña al formulario que se está confirmando
    event.preventDefault();
    ['correo_autoriza', 'contrasena_autoriza'].forEach(function (nombre) {
      let campo = formularioDestino.querySelector('input[name="' + nombre + '"]');
      if (!campo) {
        campo = document.createElement('input');
        campo.type = 'hidden';
        campo.name = nombre;
        formularioDestino.appendChild(campo);
      }
      campo.value = form.elements[nombre].value;
    });
    formularioDestino.submit();
  });

  return {
    // Modo 2: Autorizacion.abrir({ titulo, texto, nombre, detalle, boton, clase, formulario })
    abrir: function (opciones) {
      poner(opciones);
      bootstrap.Modal.getOrCreateInstance(modal).show();
    }
  };
})();

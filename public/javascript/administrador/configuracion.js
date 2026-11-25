// Configuración: marca en el menú de "Secciones" la que se está viendo y
// muestra el logo elegido antes de guardarlo.
(function () {
  const enlaces = document.querySelectorAll('.config-nav-link');

  function marcar(clave) {
    enlaces.forEach(function (a) { a.classList.toggle('is-active', a.dataset.seccion === clave); });
  }

  // La sección actual es la última cuyo título ya pasó el tercio de arriba de la pantalla;
  // hasta abajo de la página, siempre la última
  const secciones = Array.prototype.slice.call(document.querySelectorAll('.config-seccion'));
  function seccionActual() {
    if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 60) {
      return secciones[secciones.length - 1].id;
    }
    let actual = secciones[0].id;
    secciones.forEach(function (s) {
      if (s.getBoundingClientRect().top <= window.innerHeight * 0.33) { actual = s.id; }
    });
    return actual;
  }
  let pendiente = false;
  window.addEventListener('scroll', function () {
    if (pendiente) { return; }
    pendiente = true;
    requestAnimationFrame(function () { pendiente = false; marcar(seccionActual()); });
  }, { passive: true });

  // Al volver de guardar (…/configuracion#ventas) o con un error, se va a esa sección
  const irA = document.querySelector('.config-nav').dataset.irA || location.hash.slice(1);
  if (irA && document.getElementById(irA)) {
    document.getElementById(irA).scrollIntoView();
    marcar(irA);
  }

  // Vista previa del logo
  const archivo = document.getElementById('logo');
  if (archivo) {
    archivo.addEventListener('change', function () {
      const elegido = archivo.files[0];
      if (!elegido) { return; }
      const ayuda = document.getElementById('nombreLogo');
      if (!/^image\/(png|jpeg)$/.test(elegido.type)) {
        ayuda.textContent = 'Ese archivo no es PNG ni JPG.';
        ayuda.classList.add('text-danger');
        archivo.value = '';
        return;
      }
      if (elegido.size > 2 * 1024 * 1024) {
        ayuda.textContent = 'Pesa más de 2 MB. Elige una imagen más ligera.';
        ayuda.classList.add('text-danger');
        archivo.value = '';
        return;
      }
      ayuda.classList.remove('text-danger');
      ayuda.textContent = elegido.name + ' · se guardará al presionar «Guardar cambios»';
      document.getElementById('vistaLogo').src = URL.createObjectURL(elegido);
      const quitar = document.getElementById('quitarLogo');
      if (quitar) { quitar.checked = false; }
    });
  }
})();

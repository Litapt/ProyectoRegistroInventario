// Imprimir sin abrir otra pestaña.
//
// 1) Un ENLACE con data-imprimir carga su página en un marco invisible y abre
//    directamente la ventana de impresión del navegador:
//      <a href="/ruta/del/ticket" data-imprimir>Imprimir ticket</a>
//
// 2) Un BOTÓN de formulario con formtarget="marcoImpresion" manda el formulario a ese
//    marco invisible y, cuando llega la hoja, la imprime (así funciona "Mostrar cotización"):
//      <button type="submit" formaction="/ruta" formtarget="marcoImpresion">Mostrar cotización</button>
//    Si el servidor regresa un error en lugar de la hoja, se muestra arriba de la página.
//
// Si algo falla, el enlace funciona normal y abre la página.
(function () {
  const NOMBRE = 'marcoImpresion';

  function marcoNuevo() {
    const viejo = document.querySelector('iframe[name="' + NOMBRE + '"]');
    if (viejo) { viejo.remove(); }
    const marco = document.createElement('iframe');
    marco.name = NOMBRE;
    marco.setAttribute('aria-hidden', 'true');
    marco.tabIndex = -1;
    marco.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
    document.body.appendChild(marco);
    return marco;
  }

  function avisarError(texto) {
    const aviso = document.createElement('div');
    aviso.className = 'alert alert-danger text-center mb-0 rounded-0';
    aviso.setAttribute('role', 'alert');
    aviso.textContent = texto;
    const contenido = document.getElementById('pageContent') || document.body;
    contenido.prepend(aviso);
    window.scrollTo(0, 0);
  }

  // Cuando el marco termina de cargar: si es una hoja para imprimir, se imprime
  function alCargar(marco, boton, textoOriginal) {
    marco.addEventListener('load', function () {
      if (boton) {
        boton.textContent = textoOriginal;
        boton.classList.remove('disabled');
        boton.disabled = false;
      }
      let doc = null;
      try { doc = marco.contentDocument; } catch (e) { doc = null; }
      if (!doc || marco.contentWindow.location.href === 'about:blank') { return; }

      if (doc.querySelector('main.documento')) {
        marco.contentWindow.focus();
        marco.contentWindow.print();
        return;
      }

      // No llegó la hoja: el servidor regresó la página con un error
      const error = doc.querySelector('.alert-danger, .alert-warning');
      avisarError(error ? error.innerText.trim() : 'No se pudo preparar la hoja para imprimir. Intenta de nuevo.');
    });
  }

  // 1) Enlaces
  document.addEventListener('click', function (e) {
    const enlace = e.target.closest('a[data-imprimir]');
    if (!enlace) { return; }
    e.preventDefault();
    const marco = marcoNuevo();
    const texto = enlace.textContent;
    enlace.textContent = 'Preparando…';
    enlace.classList.add('disabled');
    alCargar(marco, enlace, texto);
    marco.src = enlace.href;
  });

  // 2) Formularios (el marco se prepara justo antes de que el formulario se envíe)
  document.addEventListener('click', function (e) {
    const boton = e.target.closest('button[formtarget="' + NOMBRE + '"]');
    if (!boton || boton.disabled) { return; }
    const marco = marcoNuevo();
    alCargar(marco, boton, boton.textContent);
    setTimeout(function () {
      boton.textContent = 'Preparando…';
    }, 0);
  }, true);

  // 3) data-imprimir-auto: se imprime solo al llegar a la página (ej. el ticket después de cobrar)
  const automatico = document.querySelector('a[data-imprimir][data-imprimir-auto]');
  if (automatico) { automatico.click(); }
})();

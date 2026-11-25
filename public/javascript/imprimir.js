// Imprimir sin abrir otra pestaña.
// Un enlace con data-imprimir carga su página en un marco invisible y abre
// directamente la ventana de impresión del navegador (la de elegir impresora).
//   <a href="/ruta/del/ticket" data-imprimir>Imprimir ticket</a>
// Si algo falla, el enlace funciona normal y abre la página.
(function () {
  let marco = null;

  function imprimir(url, boton) {
    if (marco) { marco.remove(); }
    marco = document.createElement('iframe');
    marco.setAttribute('aria-hidden', 'true');
    marco.tabIndex = -1;
    marco.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';

    const textoOriginal = boton.textContent;
    boton.textContent = 'Preparando…';
    boton.classList.add('disabled');

    marco.addEventListener('load', function () {
      boton.textContent = textoOriginal;
      boton.classList.remove('disabled');
      try {
        marco.contentWindow.focus();
        marco.contentWindow.print();
      } catch (e) {
        window.location.href = url; // si el navegador no deja, se abre la página
      }
    });

    marco.src = url;
    document.body.appendChild(marco);
  }

  document.addEventListener('click', function (e) {
    const enlace = e.target.closest('a[data-imprimir]');
    if (!enlace) { return; }
    e.preventDefault();
    imprimir(enlace.href, enlace);
  });
})();

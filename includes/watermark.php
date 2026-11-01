<?php
// Marca de agua provisional del equipo de trabajo (esquina inferior derecha).
// REGLA: TODAS las páginas del sistema deben incluirla.
// Es semitransparente y no bloquea clics (ver .watermark en styles.css).
// Para ponerla en una página nueva (que esté en la raíz del proyecto),
// agrega justo antes de cerrar el body una línea de PHP con:
//   require __DIR__ . '/includes/watermark.php';
?>
  <!-- Marca de agua provisional del equipo de trabajo (se puede quitar después) -->
  <!-- Usa la copia pequeña (192x192) para que la página cargue rápido -->
  <img src="imagenes/ImagenEquipoNF-192.png" width="96" height="96" alt="" aria-hidden="true" class="watermark">

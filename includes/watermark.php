<?php
// Marca de agua del equipo: se incluye en todas las páginas.
// Si la página está dentro de una carpeta, antes debe indicar $rootPath = '../';
// para que la imagen se encuentre desde esa ubicación.
$watermarkRoot = isset($rootPath) ? $rootPath : '';
?>
  <img src="<?php echo htmlspecialchars($watermarkRoot . 'imagenes/ImagenEquipoNF-192.png'); ?>" class="watermark" alt="">

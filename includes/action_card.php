<?php
// Función compartida: dibuja un botón grande (tarjeta) de los paneles.
// La usan panelAdministrador.php y panelExistencias.php.

/**
 * Recibe un arreglo con 'label' (texto), 'icon' (archivo en public/img) y 'href' (a dónde lleva).
 */
function renderActionCard(array $action): void
{
    $label = htmlspecialchars($action['label']);
    $icon = htmlspecialchars($action['icon']);
    $href = htmlspecialchars($action['href']);
    echo <<<HTML
          <a href="{$href}" class="action-card d-flex gap-3 bg-white text-decoration-none">
            <span class="action-icon d-flex align-items-center justify-content-center align-self-center flex-shrink-0">
              <img src="../public/img/{$icon}" width="24" height="24" alt="">
            </span>
            <span class="action-label fw-semibold">{$label}</span>
          </a>

HTML;
}

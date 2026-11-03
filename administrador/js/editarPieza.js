// Edición de una pieza: detecta los cambios y solo permite guardar si hay alguno.
// Todavía no hay base de datos: lo guardado se pierde al salir de la pantalla.
(function () {
  const editForm = document.getElementById('editForm');
  const saveButton = document.getElementById('saveButton');
  const saveIconOff = document.getElementById('saveIconOff');
  const saveIconOn = document.getElementById('saveIconOn');
  const saveStatus = document.getElementById('saveStatus');
  const editNote = document.getElementById('editNote');
  const successOverlay = document.getElementById('successOverlay');
  const pageContent = document.getElementById('pageContent');

  const NOTE_NO_CHANGES = 'Guardar cambios se habilita solo si hay valores distintos de los originales. Si restableces todos los valores originales, no habrá cambios pendientes.';
  const NOTE_CHANGES = 'Los campos marcados aún no se han guardado. Haz clic en Guardar cambios para guardarlos juntos. Si restableces todos los valores originales, no habrá cambios pendientes.';

  // Reúne cada campo con su valor original, que sirve para comparar
  const fields = Array.from(document.querySelectorAll('.edit-field')).map(function (field) {
    const input = field.querySelector('input, textarea, select');
    return {
      element: field,
      input: input,
      kind: field.dataset.kind,
      original: input.value,
      flag: field.querySelector('.edit-flag'),
      before: field.querySelector('.edit-before')
    };
  });

  function isNumberField(field) {
    return field.kind === 'price' || field.kind === 'units';
  }

  // Cualquier diferencia cuenta como cambio; en números, 185 y 185.00 son el mismo valor
  function hasChanged(field) {
    if (isNumberField(field) && field.input.value !== '') {
      return Number(field.input.value) !== Number(field.original);
    }
    return field.input.value !== field.original;
  }

  // Convierte el valor original en el texto que aparece después de "Antes:"
  function formatOriginal(field) {
    if (field.kind === 'price') {
      return '$' + Number(field.original).toFixed(2);
    }
    if (field.kind === 'units') {
      return field.original + (Number(field.original) === 1 ? ' unidad' : ' unidades');
    }
    return field.original;
  }

  // Marca los campos modificados y activa o desactiva el botón de guardar
  function update() {
    let changes = 0;
    fields.forEach(function (field) {
      const changed = hasChanged(field);
      changes += changed ? 1 : 0;
      field.element.classList.toggle('is-modified', changed);
      field.flag.classList.toggle('d-none', !changed);
      field.before.classList.toggle('d-none', !changed);
      field.before.textContent = changed ? 'Antes: ' + formatOriginal(field) : '';
      // Un campo de texto con solo espacios cuenta como vacío
      field.input.setCustomValidity(field.input.value.trim() === '' ? 'Este campo es obligatorio.' : '');
    });

    saveButton.disabled = changes === 0;
    saveIconOff.classList.toggle('d-none', changes > 0);
    saveIconOn.classList.toggle('d-none', changes === 0);
    saveStatus.classList.toggle('has-changes', changes > 0);
    if (changes === 0) {
      saveStatus.textContent = 'Sin cambios pendientes';
    } else {
      saveStatus.textContent = changes + (changes === 1 ? ' cambio sin guardar' : ' cambios sin guardar');
    }
    editNote.textContent = changes === 0 ? NOTE_NO_CHANGES : NOTE_CHANGES;
  }

  fields.forEach(function (field) {
    field.input.addEventListener('input', update);
    // Un clic en cualquier parte del recuadro permite escribir en el campo
    field.element.querySelector('.edit-box').addEventListener('click', function () {
      field.input.focus();
    });
  });

  editForm.addEventListener('submit', function (event) {
    event.preventDefault();
    // Si falta un dato o no es válido, el navegador lo señala y no se guarda
    if (!editForm.reportValidity()) {
      return;
    }
    // Los valores actuales pasan a ser los originales
    fields.forEach(function (field) {
      field.original = field.input.value;
    });
    update();
    successOverlay.classList.remove('d-none');
    // Mientras se ve la notificación, la página de atrás no recibe clics ni teclado
    pageContent.inert = true;
    document.getElementById('successClose').focus();
  });

  document.getElementById('successClose').addEventListener('click', function () {
    successOverlay.classList.add('d-none');
    pageContent.inert = false;
  });

  update();
})();

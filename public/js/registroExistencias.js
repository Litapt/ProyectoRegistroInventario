// Muestra la ventana de éxito encima del formulario con los datos registrados.
// IMPORTANTE: por ahora NO guarda nada en MySQL; falta la tabla de productos.

const stockForm = document.getElementById('stockForm');
const successModal = new bootstrap.Modal(document.getElementById('successModal'));

// Convierte un número a pesos mexicanos, por ejemplo 185 → "$185.00"
const moneyFormat = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

// Pone un texto dentro del elemento con ese id.
// Usamos textContent (no innerHTML) para que nada de lo escrito se interprete como código.
function setText(id, value) {
  document.getElementById(id).textContent = value;
}

// El evento "submit" solo ocurre cuando todos los campos obligatorios están llenos
// (el navegador revisa los "required" antes).
stockForm.addEventListener('submit', function (event) {
  event.preventDefault(); // evita que la página se recargue

  const data = new FormData(stockForm);

  setText('summaryPartName', data.get('part_name'));
  setText('summarySerial', data.get('serial_number'));
  setText('summaryBrand', data.get('brand'));
  setText('summaryDescription', data.get('description'));
  setText('summaryPurchasePrice', moneyFormat.format(Number(data.get('purchase_price'))));
  setText('summarySalePrice', moneyFormat.format(Number(data.get('sale_price'))));
  setText('summaryStock', data.get('stock') + ' unidades');
  setText('summaryMinStock', data.get('min_stock') + ' unidades');
  setText('summaryLocation', data.get('location'));
  setText('summaryShelf', data.get('shelf'));
  setText('summaryRack', data.get('rack'));

  successModal.show();
});

// Botón "Registrar otra pieza": cierra la ventana y deja el formulario en blanco
document.getElementById('registerAnother').addEventListener('click', function () {
  stockForm.reset();
  successModal.hide();
  document.getElementById('part_name').focus();
});

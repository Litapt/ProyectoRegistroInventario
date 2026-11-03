// Botón del ojo: muestra u oculta la contraseña que se está escribiendo.
const passwordInput = document.getElementById('password');
const togglePasswordButton = document.getElementById('togglePassword');

togglePasswordButton.addEventListener('click', function () {
  const isHidden = passwordInput.type === 'password';
  passwordInput.type = isHidden ? 'text' : 'password';
  togglePasswordButton.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
});

// Muestra u oculta la contraseña al hacer clic en el ícono del ojo
const passwordInput = document.getElementById('password');
const toggleButton = document.getElementById('togglePassword');

toggleButton.addEventListener('click', function () {
  const isHidden = passwordInput.type === 'password';
  passwordInput.type = isHidden ? 'text' : 'password';
  toggleButton.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
});

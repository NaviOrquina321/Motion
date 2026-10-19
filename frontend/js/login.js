const form = document.getElementById('loginForm');
const errBox = document.getElementById('loginError');
const pw = document.getElementById('password');

// Already logged in? Skip the form.
api('auth/session.php').then(d => { location.href = d.user.role === 'admin' ? 'dashboard.html' : 'pos.html'; }).catch(() => {});

document.getElementById('pwToggle').onclick = e => {
  const show = pw.type === 'password';
  pw.type = show ? 'text' : 'password';
  e.target.textContent = show ? 'Hide' : 'Show';
};

form.addEventListener('submit', async e => {
  e.preventDefault();
  errBox.classList.add('hidden');
  const username = document.getElementById('username').value.trim();
  const password = pw.value;
  if (!username || !password) return showErr('Please enter your username and password.');
  const btn = document.getElementById('loginBtn');
  btn.disabled = true; btn.textContent = 'Signing in...';
  try {
    const d = await api('auth/login.php', { json: { username, password } });
    location.href = d.redirect;
  } catch (err) {
    showErr(err.message);
    btn.disabled = false; btn.textContent = 'Login';
  }
});
function showErr(m) { errBox.textContent = m; errBox.classList.remove('hidden'); }

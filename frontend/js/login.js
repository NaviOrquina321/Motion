const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const errBox = document.getElementById('loginError');
const pw = document.getElementById('password');
const tabLogin = document.getElementById('tabLogin');
const tabRegister = document.getElementById('tabRegister');
const authSubtitle = document.getElementById('authSubtitle');

// Already logged in? Redirect.
api('auth/session.php').then(d => {
  location.href = d.user.role === 'admin' ? 'dashboard.html' : 'pos.html';
}).catch(() => {});

// Tab switching logic
tabLogin.onclick = () => switchTab('login');
tabRegister.onclick = () => switchTab('register');

function switchTab(mode) {
  errBox.classList.add('hidden');
  if (mode === 'login') {
    tabLogin.style.background = 'var(--primary)';
    tabLogin.style.color = '#fff';
    tabRegister.style.background = '#f1f5f9';
    tabRegister.style.color = 'var(--muted)';
    loginForm.classList.remove('hidden');
    registerForm.classList.add('hidden');
    authSubtitle.textContent = 'Sign in to your account';
  } else {
    tabRegister.style.background = 'var(--ok)';
    tabRegister.style.color = '#fff';
    tabLogin.style.background = '#f1f5f9';
    tabLogin.style.color = 'var(--muted)';
    registerForm.classList.remove('hidden');
    loginForm.classList.add('hidden');
    authSubtitle.textContent = 'Register a new user account';
  }
}

// Password toggle
document.getElementById('pwToggle').onclick = e => {
  const show = pw.type === 'password';
  pw.type = show ? 'text' : 'password';
  e.target.textContent = show ? 'Hide' : 'Show';
};

// Login form handler
loginForm.addEventListener('submit', async e => {
  e.preventDefault();
  errBox.classList.add('hidden');
  const username = document.getElementById('username').value.trim();
  const password = pw.value;

  if (!username || !password) return showErr('Please enter your username and password.');

  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.textContent = 'Signing in...';

  try {
    const d = await api('auth/login.php', { json: { username, password } });
    location.href = d.redirect;
  } catch (err) {
    showErr(err.message);
    btn.disabled = false;
    btn.textContent = 'Sign In';
  }
});

// Registration form handler
registerForm.addEventListener('submit', async e => {
  e.preventDefault();
  errBox.classList.add('hidden');

  const fullName = document.getElementById('reg_fullName').value.trim();
  const username = document.getElementById('reg_username').value.trim();
  const password = document.getElementById('reg_password').value;
  const confirmPassword = document.getElementById('reg_confirmPassword').value;

  if (!fullName) return showErr('Please enter your full name.');
  if (!username) return showErr('Please choose a username.');
  if (!/^[A-Za-z0-9_]{3,50}$/.test(username)) return showErr('Username must be 3-50 letters, numbers, or underscores.');
  if (!password || password.length < 6) return showErr('Password must be at least 6 characters long.');
  if (password !== confirmPassword) return showErr('Passwords do not match.');

  const btn = document.getElementById('registerBtn');
  btn.disabled = true;
  btn.textContent = 'Creating Account...';

  try {
    const d = await api('auth/register.php', {
      json: { full_name: fullName, username, password, confirm_password: confirmPassword }
    });
    location.href = d.redirect;
  } catch (err) {
    showErr(err.message);
    btn.disabled = false;
    btn.textContent = 'Create Account & Sign In';
  }
});

function showErr(m) {
  errBox.textContent = m;
  errBox.classList.remove('hidden');
}

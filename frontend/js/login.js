const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const errBox = document.getElementById('authError');
const pw = document.getElementById('password');
const regPw = document.getElementById('regPassword');

const tabLoginBtn = document.getElementById('tabLoginBtn');
const tabRegisterBtn = document.getElementById('tabRegisterBtn');
const authSubtitle = document.getElementById('authSubtitle');

// Already logged in? Skip the form.
api('auth/session.php').then(d => { location.href = d.user.role === 'admin' ? 'dashboard.html' : 'pos.html'; }).catch(() => {});

// Tab switching
tabLoginBtn.onclick = () => switchTab('login');
tabRegisterBtn.onclick = () => switchTab('register');

function switchTab(tab) {
  errBox.classList.add('hidden');
  if (tab === 'login') {
    tabLoginBtn.classList.add('active');
    tabRegisterBtn.classList.remove('active');
    loginForm.classList.remove('hidden');
    registerForm.classList.add('hidden');
    authSubtitle.textContent = 'Sign in to continue';
  } else {
    tabRegisterBtn.classList.add('active');
    tabLoginBtn.classList.remove('active');
    registerForm.classList.remove('hidden');
    loginForm.classList.add('hidden');
    authSubtitle.textContent = 'Create a new cashier account';
  }
}

// Password toggles
document.getElementById('pwToggle').onclick = e => {
  const show = pw.type === 'password';
  pw.type = show ? 'text' : 'password';
  e.target.textContent = show ? 'Hide' : 'Show';
};

document.getElementById('regPwToggle').onclick = e => {
  const show = regPw.type === 'password';
  regPw.type = show ? 'text' : 'password';
  e.target.textContent = show ? 'Hide' : 'Show';
};

// Login submit
loginForm.addEventListener('submit', async e => {
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

// Register submit
registerForm.addEventListener('submit', async e => {
  e.preventDefault();
  errBox.classList.add('hidden');
  const fullName = document.getElementById('regFullName').value.trim();
  const username = document.getElementById('regUsername').value.trim();
  const password = regPw.value;
  if (!fullName || !username || !password) return showErr('Please fill in all fields.');
  const btn = document.getElementById('regBtn');
  btn.disabled = true; btn.textContent = 'Creating account...';
  try {
    const d = await api('auth/register.php', { json: { full_name: fullName, username, password } });
    location.href = d.redirect;
  } catch (err) {
    showErr(err.message);
    btn.disabled = false; btn.textContent = 'Create Account';
  }
});

function showErr(m) { errBox.textContent = m; errBox.classList.remove('hidden'); }

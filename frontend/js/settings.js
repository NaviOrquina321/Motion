const $ = id => document.getElementById(id);

async function loadSettings() {
  try {
    const res = await api('settings/api.php?action=get');
    const s = res.settings || {};
    $('tax_enabled').checked = s.tax_enabled === '1';
    $('tax_rate').value = s.tax_rate || '12.00';
    $('receipt_header').value = s.receipt_header || 'QUICK BUY RETAIL';
    $('receipt_footer').value = s.receipt_footer || 'Thank you for shopping!';
  } catch (e) { toastError(e); }
}

$('settingsForm').addEventListener('submit', async e => {
  e.preventDefault();
  $('formError').textContent = '';
  const taxRate = parseFloat($('tax_rate').value);

  if (isNaN(taxRate) || taxRate < 0 || taxRate > 100) {
    return ($('formError').textContent = 'Tax rate must be between 0 and 100%.');
  }

  try {
    const payload = {
      tax_enabled: $('tax_enabled').checked ? '1' : '0',
      tax_rate: taxRate,
      receipt_header: $('receipt_header').value.trim(),
      receipt_footer: $('receipt_footer').value.trim()
    };
    const res = await api('settings/api.php?action=save', { json: payload });
    toast(res.message);
  } catch (ex) {
    $('formError').textContent = ex.message;
  }
});

(async () => {
  if (!await initPage('settings')) return;
  loadSettings();
})();

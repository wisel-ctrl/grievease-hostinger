// currency_formatter.js - Centralized auto-comma formatter for price inputs

/**
 * Attaches real-time currency formatting to an input field.
 * - Keeps type="text" but validates as number (digits + 1 decimal, max 2 places).
 * - Formats: "1000" → "1,000" (real-time); "1000.5" → "1,000.5".
 * - On blur: Adds ".00" if whole number.
 * - Usage: attachCurrencyFormatter(document.getElementById('myInputId'));
 * - For form submit: Value is always clean (e.g., "1000.00" without commas? No—formatted; strip in backend if needed).
 */
function attachCurrencyFormatter(input) {
  if (!input || input.dataset.formatted === 'true') return; // Avoid duplicates

  // Initial format on load
  formatInputValue(input);

  // Real-time formatting on input
  input.addEventListener('input', function(e) {
    formatInputValue(this);
  });

  // Append .00 on blur if no decimal
  input.addEventListener('blur', function(e) {
    let cleanValue = this.value.replace(/,/g, '');
    if (cleanValue && !cleanValue.includes('.')) {
      this.value = cleanValue + '.00';
    }
  });

  // Prevent non-numeric keys (except decimal, backspace, etc.)
  input.addEventListener('keydown', function(e) {
    const key = e.key;
    const hasDecimal = this.value.includes('.');
    const isDecimalKey = key === '.';
    const isNumberKey = /\d/.test(key);
    const isControlKey = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(key);

    if (!isNumberKey && !isControlKey && !(isDecimalKey && !hasDecimal)) {
      e.preventDefault();
    }
  });

  input.dataset.formatted = 'true'; // Mark as attached
}

/**
 * Core formatting logic (reusable).
 * @param {HTMLInputElement} input - The input to format.
 */
function formatInputValue(input) {
  let value = input.value.replace(/,/g, ''); // Strip existing commas

  // Validate: digits + at most one '.', max 2 decimal places
  value = value.replace(/[^0-9.]/g, ''); // Remove non-digits/decimal
  let parts = value.split('.');
  if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join(''); // Collapse multiple '.'
  if (parts[1] && parts[1].length > 2) parts[1] = parts[1].substring(0, 2); // Limit decimals
  value = parts.join('.');

  // Ensure only one '.'
  const dotCount = (value.match(/\./g) || []).length;
  if (dotCount > 1) {
    value = value.replace(/\./g, ''); // Strip all if invalid
  }

  if (!value) {
    input.value = '';
    return;
  }

  // Format: Commas on integer part
  let [integerStr, decimalStr] = value.split('.');
  let integer = parseInt(integerStr) || 0;
  let formatted = integer.toLocaleString('en-US'); // "1000" → "1,000"

  if (decimalStr !== undefined) {
    formatted += '.' + decimalStr;
  }

  input.value = formatted;
}

// Export for use in other files (if using modules; otherwise global)
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { attachCurrencyFormatter };
}
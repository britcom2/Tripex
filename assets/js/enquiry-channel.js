document.querySelectorAll('form[data-enquiry-form]').forEach((form) => {
  const channels = Array.from(form.querySelectorAll('[data-enquiry-channel]'));
  const emailField = form.querySelector('[data-enquiry-email-field]');
  const emailInput = emailField?.querySelector('input[type="email"]');
  const phoneInput = form.querySelector('[data-phone-field] input[name="phone"]');
  const phoneRequired = form.querySelector('[data-phone-required]');
  const phoneHelp = form.querySelector('[data-phone-help]');
  const channelNote = form.querySelector('[data-channel-note]');
  const submitButton = form.querySelector('[data-enquiry-submit]');
  const submitLabel = submitButton?.querySelector('[data-submit-label]');
  let countryCodeInput;

  if (phoneInput) {
    const countryCodeLabel = document.createElement('label');
    countryCodeLabel.className = 'phone-country-code-field';
    countryCodeLabel.append(document.createTextNode('Country code '));

    countryCodeInput = document.createElement('input');
    countryCodeInput.type = 'tel';
    countryCodeInput.inputMode = 'tel';
    countryCodeInput.autocomplete = 'tel-country-code';
    countryCodeInput.value = '+254';
    countryCodeInput.maxLength = 5;
    countryCodeInput.pattern = '\\+[1-9][0-9]{0,3}';
    countryCodeInput.setAttribute('aria-label', 'Country calling code');
    countryCodeInput.setAttribute('aria-describedby', 'enquiry-phone-format');
    countryCodeInput.dataset.countryCode = '';
    countryCodeLabel.append(countryCodeInput);
    phoneInput.closest('[data-phone-field]').before(countryCodeLabel);

    const formatHelp = document.createElement('small');
    formatHelp.className = 'field-help';
    formatHelp.id = 'enquiry-phone-format';
    formatHelp.textContent = 'Kenya is selected by default (+254). Format: +254 7XX XXX XXX.';
    countryCodeLabel.append(formatHelp);

    const phoneLabel = phoneInput.closest('[data-phone-field]');
    phoneLabel.insertBefore(formatHelp.cloneNode(true), phoneInput.nextSibling);

    // Values returned after a validation error may already contain the country code.
    const currentDigits = phoneInput.value.replace(/\D/g, '');
    const defaultCodeDigits = '254';
    if (currentDigits.startsWith(defaultCodeDigits)) {
      phoneInput.value = currentDigits.slice(defaultCodeDigits.length);
    }
  }

  const updateChannel = () => {
    const channel = form.querySelector('[data-enquiry-channel]:checked')
      || form.querySelector('input[name="channel"][type="hidden"]');
    const isWhatsApp = channel?.value === 'whatsapp';

    if (emailField && emailInput) {
      const needsEmail = !isWhatsApp;
      emailField.hidden = !needsEmail;
      emailInput.disabled = !needsEmail;
      emailInput.required = needsEmail;
    }

    if (phoneInput) {
      phoneInput.required = isWhatsApp;
      phoneInput.setCustomValidity(
        isWhatsApp && phoneInput.value.replace(/\D/g, '').length < 7
          ? 'Enter a valid phone or WhatsApp number with at least 7 digits.'
          : ''
      );
      if (phoneRequired) phoneRequired.hidden = !isWhatsApp;
      if (phoneHelp) phoneHelp.hidden = !isWhatsApp;
      if (countryCodeInput) {
        countryCodeInput.required = isWhatsApp || phoneInput.value.trim() !== '';
        countryCodeInput.setCustomValidity(
          countryCodeInput.value && !/^\+[1-9]\d{0,3}$/.test(countryCodeInput.value.trim())
            ? 'Enter a country calling code, for example +254.'
            : ''
        );
      }
    }

    if (channelNote) {
      const note = isWhatsApp ? channelNote.dataset.whatsappNote : channelNote.dataset.emailNote;
      if (note) channelNote.textContent = note;
    }

    channels.forEach((option) => {
      option.closest('.enquiry-channel-card')?.classList.toggle('is-selected', option.checked);
    });

    if (submitLabel && submitButton) {
      submitLabel.textContent = isWhatsApp
        ? submitButton.dataset.whatsappLabel
        : submitButton.dataset.emailLabel;
    }
  };

  channels.forEach((channel) => channel.addEventListener('change', updateChannel));
  phoneInput?.addEventListener('input', updateChannel);
  countryCodeInput?.addEventListener('input', updateChannel);
  updateChannel();

  form.addEventListener('submit', () => {
    if (!submitButton || submitButton.disabled) return;
    if (phoneInput && countryCodeInput && phoneInput.value.trim() !== '') {
      const code = countryCodeInput.value.trim();
      let localDigits = phoneInput.value.replace(/\D/g, '');
      const codeDigits = code.replace(/\D/g, '');
      if (localDigits.startsWith(codeDigits)) localDigits = localDigits.slice(codeDigits.length);
      if (localDigits.startsWith('0')) localDigits = localDigits.slice(1);
      phoneInput.value = `${code}${localDigits}`;
    }
    submitButton.setAttribute('aria-busy', 'true');
    if (submitLabel) submitLabel.textContent = 'Preparing your enquiry...';
  });
});

document.querySelector('.alert-error[role="alert"]')?.focus();

document.addEventListener('click', (event) => {
  document.querySelectorAll('.account-menu[open]').forEach((menu) => {
    if (!menu.contains(event.target)) menu.removeAttribute('open');
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    document.querySelectorAll('.account-menu[open]').forEach((menu) => menu.removeAttribute('open'));
  }
});

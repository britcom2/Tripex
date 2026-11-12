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
  const defaultCountryCode = '+254';

  const normalizeKenyaPhone = (value) => {
    const trimmed = (value ?? '').trim();
    if (!trimmed) return '';
    const digits = trimmed.replace(/\D/g, '');
    if (!digits) return '';
    const local = digits.startsWith('254') ? digits.slice(3) : digits.replace(/^0+/, '');
    return `+254${local}`;
  };

  const updatePhoneFormat = () => {
    if (!phoneInput) return;
    const channel = form.querySelector('[data-enquiry-channel]:checked')
      || form.querySelector('input[name="channel"][type="hidden"]');
    const isWhatsApp = channel?.value === 'whatsapp';

    if (!isWhatsApp) {
      phoneInput.placeholder = 'Optional phone number';
      phoneInput.setAttribute('aria-describedby', '');
      if (phoneHelp) {
        phoneHelp.textContent = '';
        phoneHelp.hidden = true;
      }
      if (phoneInput.value === defaultCountryCode) {
        phoneInput.value = '';
      }
      return;
    }

    phoneInput.placeholder = `${defaultCountryCode} 7XX XXX XXX`;
    phoneInput.setAttribute('aria-describedby', 'enquiry-phone-help');
    if (phoneHelp) {
      phoneHelp.textContent = 'Format: +254 7XX XXX XXX.';
      phoneHelp.hidden = false;
    }

    const formatted = normalizeKenyaPhone(phoneInput.value);
    if (formatted && formatted !== phoneInput.value) {
      phoneInput.value = formatted;
    }

    if (!phoneInput.value.trim()) {
      phoneInput.value = defaultCountryCode;
    }
  };

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
      updatePhoneFormat();
      const number = normalizeKenyaPhone(phoneInput.value);
      const isValidKenyaPhone = /^\+254\d{9,10}$/.test(number || '');
      phoneInput.required = isWhatsApp;
      phoneInput.setCustomValidity(
        isWhatsApp && !isValidKenyaPhone
          ? 'Enter a valid Kenya phone number in the format +254 7XX XXX XXX.'
          : ''
      );
      if (phoneRequired) phoneRequired.hidden = !isWhatsApp;
      if (phoneHelp) phoneHelp.hidden = !isWhatsApp;
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
  phoneInput?.addEventListener('input', updatePhoneFormat);
  updateChannel();

  form.addEventListener('submit', () => {
    if (!submitButton || submitButton.disabled) return;
    const channel = form.querySelector('[data-enquiry-channel]:checked')
      || form.querySelector('input[name="channel"][type="hidden"]');
    const isWhatsApp = channel?.value === 'whatsapp';

    if (phoneInput && isWhatsApp) {
      const formatted = normalizeKenyaPhone(phoneInput.value);
      if (formatted) phoneInput.value = formatted;
      if (!formatted) phoneInput.value = defaultCountryCode;
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

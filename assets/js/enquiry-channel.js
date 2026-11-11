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
  updateChannel();

  form.addEventListener('submit', () => {
    if (!submitButton || submitButton.disabled) return;
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

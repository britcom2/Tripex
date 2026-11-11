document.querySelectorAll('form[data-enquiry-form]').forEach((form) => {
  const channel = form.querySelector('[data-enquiry-channel]');
  const emailField = form.querySelector('[data-enquiry-email-field]');
  const emailInput = emailField?.querySelector('input[type="email"]');

  if (channel && emailField && emailInput) {
    const updateEmailField = () => {
      const needsEmail = channel.value !== 'whatsapp';
      emailField.hidden = !needsEmail;
      emailInput.disabled = !needsEmail;
      emailInput.required = needsEmail;
    };

    channel.addEventListener('change', updateEmailField);
    updateEmailField();
  }
});

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

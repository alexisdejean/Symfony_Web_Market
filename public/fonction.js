document.addEventListener('DOMContentLoaded', () => {
    const passwordFields = document.querySelectorAll('input[type="password"]');

    passwordFields.forEach((passwordField, index) => {
        const checkboxId = `show-password-${index}`;
        const toggle = document.createElement('div');
        const checkbox = document.createElement('input');
        const label = document.createElement('label');

        toggle.className = 'password-toggle';
        checkbox.type = 'checkbox';
        checkbox.id = checkboxId;
        checkbox.className = 'password-toggle__checkbox';
        checkbox.setAttribute('aria-controls', passwordField.id || '');

        label.htmlFor = checkboxId;
        label.className = 'password-toggle__label';
        label.textContent = ' Afficher le mot de passe';

        checkbox.addEventListener('change', () => {
            passwordField.type = checkbox.checked ? 'text' : 'password';
        });

        toggle.append(checkbox, label);
        passwordField.insertAdjacentElement('afterend', toggle);
    });
});
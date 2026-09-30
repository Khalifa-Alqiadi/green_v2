const initialized = new WeakSet();

function selectedText(select) {
    const values = Array.from(select.selectedOptions)
        .filter((option) => option.value !== '')
        .map((option) => option.textContent.trim())
        .filter(Boolean);

    return values.length > 0 ? values.join(', ') : select.dataset.placeholder;
}

function enhanceSelect(select) {
    if (initialized.has(select)) {
        return;
    }

    initialized.add(select);
    const options = Array.from(select.options).filter((option) => option.value !== '');
    const details = document.createElement('details');
    const summary = document.createElement('summary');
    const panel = document.createElement('div');
    const search = document.createElement('input');
    const list = document.createElement('div');
    const controls = [];

    details.className = 'velocity-choice';
    summary.className = 'velocity-choice__summary';
    summary.setAttribute('aria-label', select.dataset.label || select.dataset.placeholder);
    panel.className = 'velocity-choice__panel';
    search.className = 'velocity-choice__search';
    search.type = 'search';
    search.placeholder = select.dataset.searchLabel || 'Search';
    search.setAttribute('aria-label', search.placeholder);
    list.className = 'velocity-choice__options';

    options.forEach((option) => {
        const label = document.createElement('label');
        const control = document.createElement('input');
        const text = document.createElement('span');

        label.className = 'velocity-choice__option';
        control.type = select.multiple ? 'checkbox' : 'radio';
        control.checked = option.selected;
        control.dataset.value = option.value;
        text.textContent = option.textContent.trim();
        control.setAttribute('aria-label', text.textContent);
        label.append(control, text);
        list.append(label);
        controls.push({ control, label, option });

        control.addEventListener('change', () => {
            if (select.multiple) {
                option.selected = control.checked;
            } else {
                options.forEach((candidate) => { candidate.selected = candidate === option; });
                controls.forEach((candidate) => { candidate.control.checked = candidate.option === option; });
                details.open = false;
            }
            select.dispatchEvent(new Event('change', { bubbles: true }));
            summary.textContent = selectedText(select);
        });
    });

    search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase();
        controls.forEach(({ label, option }) => {
            label.hidden = query !== '' && ! option.textContent.toLocaleLowerCase().includes(query);
        });
    });

    const sync = () => {
        controls.forEach(({ control, option }) => { control.checked = option.selected; });
        summary.textContent = selectedText(select);
    };

    select.addEventListener('change', sync);
    select.addEventListener('invalid', () => {
        details.open = true;
        summary.focus();
    });
    select.form?.addEventListener('reset', () => window.setTimeout(sync));
    details.addEventListener('toggle', () => {
        if (details.open) {
            search.value = '';
            controls.forEach(({ label }) => { label.hidden = false; });
        }
    });

    panel.append(search, list);
    details.append(summary, panel);
    select.classList.add('velocity-select--native-enhanced');
    select.setAttribute('aria-hidden', 'true');
    select.tabIndex = -1;
    select.insertAdjacentElement('afterend', details);
    sync();
}

export function setupFormControls(root = document) {
    const selects = root.querySelectorAll('select[data-velocity-enhanced-select]');
    selects.forEach(enhanceSelect);
    return selects.length;
}

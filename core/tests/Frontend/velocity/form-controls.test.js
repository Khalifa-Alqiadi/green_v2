import { afterEach, describe, expect, test } from 'vitest';
import { setupFormControls } from '../../../resources/themes/velocity/js/modules/form-controls.js';

afterEach(() => {
    document.body.innerHTML = '';
});

describe('Velocity enhanced selects', () => {
    test('preserves native selects while synchronizing searchable single and multiple controls', () => {
        document.body.innerHTML = `
            <form>
                <select name="contact" data-velocity-enhanced-select data-label="Contact" data-placeholder="Select" data-search-label="Search">
                    <option value="">Select</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                </select>
                <select name="times[]" multiple data-velocity-enhanced-select data-label="Times" data-placeholder="Select" data-search-label="Search">
                    <option value="morning">Morning</option>
                    <option value="evening">Evening</option>
                </select>
            </form>
        `;

        expect(setupFormControls(document)).toBe(2);
        expect(setupFormControls(document)).toBe(2);
        expect(document.querySelectorAll('.velocity-choice')).toHaveLength(2);

        const selects = document.querySelectorAll('select');
        expect(selects[0].getAttribute('aria-hidden')).toBe('true');
        expect(selects[0].tabIndex).toBe(-1);
        const choices = document.querySelectorAll('.velocity-choice');
        const phone = choices[0].querySelectorAll('input[type="radio"]')[1];
        expect(phone.getAttribute('aria-label')).toBe('Phone');
        phone.checked = true;
        phone.dispatchEvent(new Event('change', { bubbles: true }));

        const morning = choices[1].querySelector('input[type="checkbox"]');
        morning.checked = true;
        morning.dispatchEvent(new Event('change', { bubbles: true }));

        expect(selects[0].value).toBe('phone');
        expect(Array.from(selects[1].selectedOptions).map((option) => option.value)).toEqual(['morning']);
        expect(choices[0].querySelector('summary').textContent).toBe('Phone');
        expect(choices[1].querySelector('summary').textContent).toBe('Morning');

        const search = choices[1].querySelector('input[type="search"]');
        search.value = 'even';
        search.dispatchEvent(new Event('input', { bubbles: true }));
        expect(choices[1].querySelectorAll('.velocity-choice__option')[0].hidden).toBe(true);
        expect(choices[1].querySelectorAll('.velocity-choice__option')[1].hidden).toBe(false);
    });

    test('opens the enhanced control when a required native select is invalid', () => {
        document.body.innerHTML = `
            <form><select required data-velocity-enhanced-select data-placeholder="Select"><option value="">Select</option><option value="1">One</option></select></form>
        `;
        setupFormControls(document);
        const select = document.querySelector('select');
        select.dispatchEvent(new Event('invalid'));
        expect(document.querySelector('.velocity-choice').open).toBe(true);
    });
});

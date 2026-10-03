/**
 * Dynamic condition/action rows for the workflow builder.
 *
 * Each row has a "kind" select (field or action type) whose choice decides
 * which kind of value input is shown: a select of known options, a free text
 * input, or nothing at all.
 */
const builder = document.getElementById('workflow-builder');
const config = JSON.parse(document.getElementById('workflow-config').textContent);

function valueInput(name, source, currentValue) {
    if (source === null) {
        const placeholder = document.createElement('input');
        placeholder.type = 'hidden';
        placeholder.name = name;
        placeholder.value = '';
        return placeholder;
    }

    if (Array.isArray(source)) {
        const select = document.createElement('select');
        select.className = 'form-select';
        select.name = name;
        source.forEach((option) => select.add(new Option(option.label, option.value, false, option.value === String(currentValue ?? ''))));
        return select;
    }

    const input = document.createElement(source === 'textarea' ? 'textarea' : 'input');
    input.className = 'form-control';
    input.name = name;
    input.value = currentValue ?? '';
    if (source === 'textarea') {
        input.rows = 2;
    }
    return input;
}

function renderValue(row) {
    const list = row.closest('[data-list]').dataset.list;
    const index = row.dataset.index;
    const slot = row.querySelector('[data-value-slot]');
    const kind = row.querySelector('[data-kind]').value;
    const current = slot.querySelector('[name]')?.value ?? row.dataset.value;

    let source;

    if (list === 'conditions') {
        const operator = row.querySelector('[data-operator]').value;
        source = config.operatorsWithoutValue.includes(operator) ? null : (config.fieldValues[kind] ?? 'text');
    } else {
        source = config.actionValues[kind] === undefined ? 'text' : config.actionValues[kind];
    }

    slot.replaceChildren(valueInput(`${list}[${index}][value]`, source, current));
    delete row.dataset.value;
}

function addRow(list, values = {}) {
    const container = builder.querySelector(`[data-list="${list}"]`);
    const template = document.getElementById(`${list}-template`);
    const index = container.dataset.next++;
    const row = template.content.firstElementChild.cloneNode(true);

    row.dataset.index = index;
    row.dataset.value = values.value ?? '';
    row.querySelectorAll('[data-name]').forEach((element) => {
        element.name = `${list}[${index}][${element.dataset.name}]`;
        if (values[element.dataset.name] !== undefined) {
            element.value = values[element.dataset.name];
        }
    });

    container.appendChild(row);
    renderValue(row);
}

builder.addEventListener('change', (event) => {
    if (event.target.matches('[data-kind], [data-operator]')) {
        const row = event.target.closest('.workflow-row');
        row.querySelector('[data-value-slot]').replaceChildren();
        renderValue(row);
    }
});

builder.addEventListener('click', (event) => {
    const add = event.target.closest('[data-add]');
    if (add) {
        addRow(add.dataset.add);
    }

    const remove = event.target.closest('[data-remove]');
    if (remove) {
        remove.closest('.workflow-row').remove();
    }
});

config.conditions.forEach((condition) => addRow('conditions', condition));
config.actions.forEach((action) => addRow('actions', action));

if (config.actions.length === 0) {
    addRow('actions');
}

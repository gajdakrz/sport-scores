/**
 * Preferences modal: selects that depend on the favourite sport (competition, team) offer only
 * the options of the selected sport, and are disabled without one. Only a convenience – the server
 * validates the choice.
 */
export function initOptionsBySport(container: HTMLElement, sportSelectId: string, dependentSelectIds: string[]): void {
    const sportSelect = container.querySelector<HTMLSelectElement>(`#${sportSelectId}`);
    if (!sportSelect) return;

    const dependentSelects = dependentSelectIds
        .map(id => container.querySelector<HTMLSelectElement>(`#${id}`))
        .filter((select): select is HTMLSelectElement => select !== null);

    const sync = (): void => {
        dependentSelects.forEach(select => syncOptions(select, sportSelect.value));
    };

    sportSelect.addEventListener('change', sync);
    sync();
}

function syncOptions(select: HTMLSelectElement, sportId: string): void {
    Array.from(select.options).forEach(option => {
        if (option.value === '') return;

        const matches = option.dataset.sportId === sportId;
        option.hidden = !matches;
        option.disabled = !matches;
    });

    if (select.selectedOptions[0]?.disabled) {
        select.value = '';
    }
    select.disabled = sportId === '';
}

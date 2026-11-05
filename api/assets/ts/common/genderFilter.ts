/**
 * Narrows a team <select> to teams of the same gender category as the selected competition
 * (exact match: male, female or mixed). An empty gender (nothing selected yet) shows every team.
 * Only a UX helper – the server validates the same rule (GameResultTeamGenderMatch).
 */
export function filterTeamOptionsByGender(select: HTMLSelectElement, gender: string): void {
    Array.from(select.options).forEach(option => {
        if (option.value === '') {
            return;
        }

        const allowed = gender === '' || option.dataset.gender === gender;

        option.hidden = !allowed;
        option.disabled = !allowed;

        if (!allowed && option.selected) {
            select.value = '';
        }
    });
}

export function selectedOptionGender(select: HTMLSelectElement): string {
    return select.selectedOptions[0]?.dataset.gender ?? '';
}
/**
 * What a competition requires from a team. An empty value means "no restriction":
 * - gender: nothing selected yet,
 * - country: nothing selected yet or an international competition.
 */
export interface TeamFilterCriteria {
    gender: string;
    country: string;
}

/**
 * Narrows a team <select> to teams the selected competition admits:
 * the same gender category (exact match: male, female or mixed) and, for a national
 * competition, the same country. Only a UX helper – the server validates the same rules
 * (GameResultTeamGenderMatch, GameResultTeamCountryMatch).
 */
export function filterTeamOptions(select: HTMLSelectElement, criteria: TeamFilterCriteria): void {
    Array.from(select.options).forEach(option => {
        if (option.value === '') {
            return;
        }

        const allowed = matches(criteria.gender, option.dataset.gender)
            && matches(criteria.country, option.dataset.country);

        option.hidden = !allowed;
        option.disabled = !allowed;

        if (!allowed && option.selected) {
            select.value = '';
        }
    });
}

export function selectedOptionCriteria(select: HTMLSelectElement): TeamFilterCriteria {
    const dataset = select.selectedOptions[0]?.dataset;

    return {
        gender: dataset?.gender ?? '',
        country: dataset?.country ?? '',
    };
}

function matches(required: string, actual: string | undefined): boolean {
    return required === '' || actual === required;
}
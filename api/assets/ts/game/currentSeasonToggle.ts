/**
 * Games filter: a season picked in the select takes precedence over "Current season",
 * so the switch is disabled while a season is selected.
 */
export function initCurrentSeasonToggle(): void {
    const seasonSelect = document.getElementById('seasonId') as HTMLSelectElement | null;
    const currentSeason = document.getElementById('currentSeason') as HTMLInputElement | null;
    if (!seasonSelect || !currentSeason) return;

    const sync = (): void => {
        currentSeason.disabled = seasonSelect.value !== '';
    };

    seasonSelect.addEventListener('change', sync);
    sync();
}

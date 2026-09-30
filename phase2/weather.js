const citySearch = document.querySelector('#city-search');
const citySuggestions = document.querySelector('#city-suggestions');
const historyFilter = document.querySelector('#history-filter');
const historyEntries = [...document.querySelectorAll('.history-entry')];
const historyNoMatch = document.querySelector('#history-no-match');
const weatherSearchForm = document.querySelector('#weather-search');

let suggestionTimer;
let suggestionRequest;

function filterHistory(value) {
    const prefix = value.trim().toLocaleLowerCase();
    let visibleCount = 0;

    historyEntries.forEach((entry) => {
        const matches = entry.dataset.city.startsWith(prefix);
        entry.hidden = !matches;
        if (matches) visibleCount += 1;
    });

    if (historyNoMatch) {
        historyNoMatch.hidden = historyEntries.length === 0 || visibleCount > 0;
    }
}

if (historyFilter) {
    historyFilter.addEventListener('input', () => filterHistory(historyFilter.value));
}

if (citySearch && citySuggestions) {
    citySearch.addEventListener('input', () => {
        const prefix = citySearch.value.trim();
        if (historyFilter) historyFilter.value = prefix;
        filterHistory(prefix);
        clearTimeout(suggestionTimer);
        suggestionRequest?.abort();
        citySuggestions.replaceChildren();

        if (prefix.length < 2) return;

        suggestionTimer = setTimeout(async () => {
            suggestionRequest = new AbortController();
            try {
                const response = await fetch(`dashboard.php?suggest=${encodeURIComponent(prefix)}`, {
                    headers: { Accept: 'application/json' },
                    signal: suggestionRequest.signal,
                });
                if (!response.ok) return;

                const suggestions = await response.json();
                suggestions.forEach((suggestion) => {
                    const cityName = suggestion.label.split(' · ')[0];
                    if (!cityName.toLocaleLowerCase().startsWith(prefix.toLocaleLowerCase())) return;

                    const option = document.createElement('option');
                    option.value = suggestion.value;
                    option.label = suggestion.label;
                    citySuggestions.append(option);
                });
            } catch (error) {
                if (error.name !== 'AbortError') citySuggestions.replaceChildren();
            }
        }, 250);
    });
}

historyEntries.forEach((entry) => {
    entry.addEventListener('click', () => {
        if (!citySearch || !weatherSearchForm) return;
        citySearch.value = entry.dataset.query;
        weatherSearchForm.requestSubmit();
    });
});

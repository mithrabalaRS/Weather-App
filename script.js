const API_KEY = 'a96bcbc5b06b9206cb7f5bd230b4663c';

const cityNames = [
  'Tirupati', 'Tirunelveli', 'Tiruchirappalli', 'Tiruppur', 'Tindivanam', 'Tinsukia', 'Titlagarh', 'Thiruvananthapuram', 'Thanjavur',
  'Tokyo', 'Toronto', 'Toronto', 'Tokyo', 'Delhi', 'Mumbai', 'Bengaluru', 'Chennai', 'Hyderabad', 'Kolkata', 'Patna', 'Jaipur', 'Lucknow',
  'New York', 'London', 'Paris', 'Berlin', 'Rome', 'Madrid', 'Lisbon', 'Cairo', 'Dubai', 'Singapore', 'Bangkok', 'Seoul', 'Tokyo', 'Sydney',
  'Cape Town', 'Johannesburg', 'Lagos', 'Nairobi', 'Moscow', 'Istanbul', 'Athens', 'Prague', 'Budapest', 'Warsaw', 'Amsterdam', 'Oslo',
  'San Francisco', 'Los Angeles', 'Chicago', 'Miami', 'Seattle', 'Boston', 'Austin', 'Denver', 'Phoenix', 'Las Vegas', 'Houston', 'Dallas',
  'Mexico City', 'Bogota', 'Lima', 'Santiago', 'Buenos Aires', 'Rio de Janeiro', 'Sao Paulo', 'Caracas', 'Panama City', 'Havana',
  'Milan', 'Vienna', 'Zurich', 'Brussels', 'Stockholm', 'Helsinki', 'Copenhagen', 'Reykjavik', 'Tallinn', 'Riga', 'Vilnius', 'Kyiv', 'Bucharest',
  'Karachi', 'Lahore', 'Dhaka', 'Kathmandu', 'Colombo', 'Kuala Lumpur', 'Jakarta', 'Manila', 'Hanoi', 'Ho Chi Minh City', 'Phnom Penh',
  'Perth', 'Melbourne', 'Brisbane', 'Adelaide', 'Canberra', 'Auckland', 'Wellington', 'Fiji', 'Honolulu', 'Guam', 'Vancouver', 'Montreal', 'Calgary',
  'Ahmedabad', 'Surat', 'Pune', 'Nagpur', 'Indore', 'Bhopal', 'Visakhapatnam', 'Coimbatore', 'Madurai', 'Salem', 'Erode', 'Vellore', 'Trichy',
  'Tirupati', 'Tirunelveli', 'Tiruchirappalli', 'Tiruppur'
];

const loginScreen = document.getElementById('loginScreen');
const appScreen = document.getElementById('appScreen');
const loginForm = document.getElementById('loginForm');
const usernameInput = document.getElementById('username');
const passwordInput = document.getElementById('password');
const cityInput = document.getElementById('cityInput');
const searchBtn = document.getElementById('searchBtn');
const suggestionsBox = document.getElementById('suggestions');
const historyList = document.getElementById('historyList');
const favoritesList = document.getElementById('favoritesList');
const favoriteBtn = document.getElementById('favoriteBtn');
const clearHistoryBtn = document.getElementById('clearHistoryBtn');
const logoutBtn = document.getElementById('logoutBtn');
const forecastSection = document.getElementById('forecastSection');
const locationName = document.getElementById('locationName');
const weatherDate = document.getElementById('weatherDate');
const conditionBadge = document.getElementById('conditionBadge');
const mainTemp = document.getElementById('mainTemp');
const mainCondition = document.getElementById('mainCondition');
const humidityValue = document.getElementById('humidityValue');
const windValue = document.getElementById('windValue');
const feelsValue = document.getElementById('feelsValue');

let currentCity = 'Tirupati';

const uniqueCities = [...new Set(cityNames.map(city => city.trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b));

function showLogin() {
  loginScreen.classList.remove('hidden');
  appScreen.classList.add('hidden');
}

function showApp() {
  loginScreen.classList.add('hidden');
  appScreen.classList.remove('hidden');
}

function getHistory() {
  return JSON.parse(localStorage.getItem('weatherHistory') || '[]');
}

function getFavorites() {
  return JSON.parse(localStorage.getItem('weatherFavorites') || '[]');
}

function saveHistory(city) {
  const history = getHistory();
  const nextHistory = [city, ...history.filter(item => item.toLowerCase() !== city.toLowerCase())].slice(0, 6);
  localStorage.setItem('weatherHistory', JSON.stringify(nextHistory));
  renderHistory();
}

function saveFavorites(favorites) {
  localStorage.setItem('weatherFavorites', JSON.stringify(favorites));
  renderFavorites();
}

function renderHistory() {
  const history = getHistory();
  historyList.innerHTML = '';

  if (!history.length) {
    historyList.innerHTML = '<span class="muted">No searches yet</span>';
    return;
  }

  history.forEach(city => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'history-chip';
    button.textContent = city;
    button.addEventListener('click', () => {
      cityInput.value = city;
      fetchWeatherForCity(city);
    });
    historyList.appendChild(button);
  });
}

function renderFavorites() {
  const favorites = getFavorites();
  favoritesList.innerHTML = '';

  if (!favorites.length) {
    favoritesList.innerHTML = '<span class="muted">No favorite cities</span>';
    return;
  }

  favorites.forEach(city => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `favorite-chip ${city.toLowerCase() === currentCity.toLowerCase() ? 'active' : ''}`;
    button.textContent = city;
    button.addEventListener('click', () => {
      cityInput.value = city;
      fetchWeatherForCity(city);
    });
    favoritesList.appendChild(button);
  });
}

function toggleFavorite(city) {
  const favorites = getFavorites();
  const normalizedCity = city.trim();

  if (!normalizedCity) return;

  const exists = favorites.some(item => item.toLowerCase() === normalizedCity.toLowerCase());
  const nextFavorites = exists
    ? favorites.filter(item => item.toLowerCase() !== normalizedCity.toLowerCase())
    : [normalizedCity, ...favorites].slice(0, 6);

  saveFavorites(nextFavorites);
  favoriteBtn.textContent = exists ? 'Add Favorite' : 'Remove Favorite';
}

function applyWeatherTheme(condition, temp) {
  const normalizedCondition = (condition || 'clear').toLowerCase();
  const bodyClasses = ['weather-clear', 'weather-clouds', 'weather-rain', 'weather-drizzle', 'weather-thunderstorm', 'weather-snow', 'weather-mist', 'weather-hot', 'weather-cold'];
  document.body.classList.remove(...bodyClasses);

  if (normalizedCondition.includes('rain') || normalizedCondition.includes('storm')) {
    document.body.classList.add(normalizedCondition.includes('storm') ? 'weather-thunderstorm' : 'weather-rain');
  } else if (normalizedCondition.includes('snow')) {
    document.body.classList.add('weather-snow');
  } else if (normalizedCondition.includes('cloud')) {
    document.body.classList.add('weather-clouds');
  } else if (normalizedCondition.includes('mist') || normalizedCondition.includes('fog')) {
    document.body.classList.add('weather-mist');
  } else {
    document.body.classList.add('weather-clear');
  }

  if (temp >= 30) {
    document.body.classList.add('weather-hot');
  } else if (temp <= 10) {
    document.body.classList.add('weather-cold');
  }
}

function buildSuggestions(value) {
  const trimmed = value.trim();
  if (!trimmed) {
    suggestionsBox.innerHTML = '';
    suggestionsBox.classList.add('hidden');
    return;
  }

  const matched = uniqueCities.filter(city => city.toLowerCase().startsWith(trimmed.toLowerCase())).slice(0, 8);

  if (!matched.length) {
    suggestionsBox.innerHTML = '<button class="suggestion-item" type="button">No city found</button>';
    suggestionsBox.classList.remove('hidden');
    return;
  }

  suggestionsBox.innerHTML = '';
  matched.forEach(city => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'suggestion-item';
    btn.textContent = city;
    btn.addEventListener('click', () => {
      cityInput.value = city;
      suggestionsBox.classList.add('hidden');
      fetchWeatherForCity(city);
    });
    suggestionsBox.appendChild(btn);
  });

  suggestionsBox.classList.remove('hidden');
}

function displayError(message) {
  const msg = message || 'Unable to fetch weather right now.';
  if (locationName) {
    locationName.textContent = 'Unable to load weather';
  }
  mainTemp.textContent = '--°';
  mainCondition.textContent = msg;
}

async function fetchWeatherForCity(cityName) {
  const normalizedCity = cityName.trim();
  if (!normalizedCity) return;

  try {
    const geoResponse = await fetch(`https://api.openweathermap.org/geo/1.0/direct?q=${encodeURIComponent(normalizedCity)}&limit=1&appid=${API_KEY}`);
    const geoData = await geoResponse.json();

    if (!geoData || !geoData.length) {
      displayError(`No weather data found for ${normalizedCity}.`);
      return;
    }

    const { lat, lon, name, country } = geoData[0];
    const weatherResponse = await fetch(`https://api.openweathermap.org/data/2.5/forecast?lat=${lat}&lon=${lon}&units=metric&appid=${API_KEY}`);
    const weatherData = await weatherResponse.json();

    if (!weatherData || !weatherData.list || weatherData.list.length === 0) {
      displayError('Forecast data is unavailable.');
      return;
    }

    const current = weatherData.list[0];
    const dailyData = [];
    const seenDates = new Set();

    weatherData.list.forEach(item => {
      const date = item.dt_txt.split(' ')[0];
      if (!seenDates.has(date) && dailyData.length < 7) {
        seenDates.add(date);
        dailyData.push(item);
      }
    });

    locationName.textContent = `${name}, ${country}`;
    weatherDate.textContent = new Date(current.dt * 1000).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
    conditionBadge.textContent = current.weather[0].main;
    mainTemp.textContent = `${Math.round(current.main.temp)}°`;
    mainCondition.textContent = current.weather[0].description;
    humidityValue.textContent = `${current.main.humidity}%`;
    windValue.textContent = `${Math.round(current.wind.speed * 3.6)} km/h`;
    feelsValue.textContent = `${Math.round(current.main.feels_like)}°`;

    const currentTemp = Math.round(current.main.temp);
    const currentCondition = current.weather[0].main;
    applyWeatherTheme(currentCondition, currentTemp);
    renderForecast(dailyData);
    saveHistory(name);
    currentCity = name;
    favoriteBtn.textContent = getFavorites().some(item => item.toLowerCase() === name.toLowerCase()) ? 'Remove Favorite' : 'Add Favorite';
    renderFavorites();
    suggestionsBox.classList.add('hidden');
  } catch (error) {
    displayError('Something went wrong while fetching weather data.');
  }
}

function renderForecast(data) {
  forecastSection.innerHTML = '';

  data.forEach(day => {
    const card = document.createElement('div');
    card.className = 'forecast-card';

    const date = new Date(day.dt * 1000);
    const dayLabel = date.toLocaleDateString([], { weekday: 'short' });

    card.innerHTML = `
      <div class="day">${dayLabel}</div>
      <div>${day.weather[0].main}</div>
      <div class="forecast-temp">${Math.round(day.main.temp)}°</div>
      <div class="forecast-conditions">${day.weather[0].description}</div>
    `;

    forecastSection.appendChild(card);
  });
}

loginForm.addEventListener('submit', (event) => {
  event.preventDefault();
  const username = usernameInput.value.trim();
  const password = passwordInput.value.trim();

  if (!username || !password) {
    alert('Please enter both username and password.');
    return;
  }

  showApp();
  cityInput.value = currentCity;
  fetchWeatherForCity(currentCity);
});

searchBtn.addEventListener('click', () => {
  const city = cityInput.value.trim();
  if (!city) {
    alert('Please enter a city name.');
    return;
  }
  fetchWeatherForCity(city);
});

cityInput.addEventListener('input', (event) => {
  buildSuggestions(event.target.value);
});

cityInput.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    const city = cityInput.value.trim();
    if (city) {
      fetchWeatherForCity(city);
    }
  }
});

favoriteBtn.addEventListener('click', () => {
  if (!currentCity) return;
  toggleFavorite(currentCity);
});

clearHistoryBtn.addEventListener('click', () => {
  localStorage.removeItem('weatherHistory');
  renderHistory();
});

logoutBtn.addEventListener('click', () => {
  showLogin();
  loginForm.reset();
});

renderHistory();
renderFavorites();
showLogin();
fetchWeatherForCity('Tirupati');

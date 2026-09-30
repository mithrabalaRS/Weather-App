<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

$apiKey = 'a96bcbc5b06b9206cb7f5bd230b4663c';
$currentWeather = null;
$weeklyForecast = [];
$message = '';
$messageType = 'success';
$forecastMessage = '';

function fetchWeatherJson(string $url): ?array
{
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function weatherDescriptionFromCode(int $code): string
{
    $descriptions = [
        0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Depositing rime fog', 51 => 'Light drizzle', 53 => 'Drizzle',
        55 => 'Dense drizzle', 56 => 'Freezing drizzle', 57 => 'Dense freezing drizzle',
        61 => 'Light rain', 63 => 'Rain', 65 => 'Heavy rain', 66 => 'Freezing rain',
        67 => 'Heavy freezing rain', 71 => 'Light snow', 73 => 'Snow', 75 => 'Heavy snow',
        77 => 'Snow grains', 80 => 'Light rain showers', 81 => 'Rain showers',
        82 => 'Heavy rain showers', 85 => 'Snow showers', 86 => 'Heavy snow showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm with hail', 99 => 'Thunderstorm with heavy hail',
    ];

    return $descriptions[$code] ?? 'Variable conditions';
}

function weatherIconFromCode(int $code): string
{
    if ($code === 0) return '☀️';
    if (in_array($code, [1, 2], true)) return '🌤️';
    if (in_array($code, [3, 45, 48], true)) return $code === 3 ? '☁️' : '🌫️';
    if (($code >= 51 && $code <= 67) || ($code >= 80 && $code <= 82)) return '🌧️';
    if (($code >= 71 && $code <= 77) || in_array($code, [85, 86], true)) return '❄️';
    if ($code >= 95) return '⛈️';
    return '🌦️';
}

if (isset($_GET['suggest'])) {
    header('Content-Type: application/json; charset=utf-8');
    $prefix = is_string($_GET['suggest']) ? trim($_GET['suggest']) : '';
    $suggestions = [];

    if (strlen($prefix) >= 2) {
        $geoUrl = 'https://api.openweathermap.org/geo/1.0/direct?q=' . urlencode($prefix) . '&limit=20&appid=' . $apiKey;
        $places = fetchWeatherJson($geoUrl) ?? [];

        foreach ($places as $place) {
            $name = trim($place['name'] ?? '');
            if ($name === '' || strncasecmp($name, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $country = trim($place['country'] ?? '');
            $state = trim($place['state'] ?? '');
            $suggestions[] = [
                'value' => implode(',', array_filter([$name, $country])),
                'label' => implode(' · ', array_filter([$name, $state, $country])),
            ];
        }
    }

    echo json_encode($suggestions, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city'])) {
    $city = trim($_POST['city']);
    if ($city !== '') {
        $geoUrl = 'https://api.openweathermap.org/geo/1.0/direct?q=' . urlencode($city) . '&limit=1&appid=' . $apiKey;
        $geoData = fetchWeatherJson($geoUrl);

        if (!empty($geoData)) {
            $lat = $geoData[0]['lat'];
            $lon = $geoData[0]['lon'];
            $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}";
            $weatherData = fetchWeatherJson($weatherUrl);

            if (!empty($weatherData['main'])) {
                $currentWeather = $weatherData;
                $insert = $pdo->prepare('INSERT INTO weather_searches (user_id, city_name, country, temperature, weather_condition) VALUES (:user_id, :city_name, :country, :temperature, :weather_condition)');
                $insert->execute([
                    'user_id' => $userId,
                    'city_name' => $weatherData['name'],
                    'country' => $weatherData['sys']['country'] ?? '',
                    'temperature' => $weatherData['main']['temp'] ?? 0,
                    'weather_condition' => $weatherData['weather'][0]['main'] ?? '',
                ]);

                $message = 'Weather loaded for ' . ($weatherData['name'] ?? $city) . ' and added to your search history.';

                $forecastUrl = 'https://api.open-meteo.com/v1/forecast?latitude=' . urlencode((string) $lat)
                    . '&longitude=' . urlencode((string) $lon)
                    . '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max'
                    . '&forecast_days=7&timezone=auto';
                $forecastData = fetchWeatherJson($forecastUrl);
                if (!empty($forecastData['daily']['time'])) {
                    foreach ($forecastData['daily']['time'] as $index => $date) {
                        $weeklyForecast[] = [
                            'date' => $date,
                            'code' => (int) ($forecastData['daily']['weather_code'][$index] ?? -1),
                            'high' => $forecastData['daily']['temperature_2m_max'][$index] ?? null,
                            'low' => $forecastData['daily']['temperature_2m_min'][$index] ?? null,
                            'precipitation' => $forecastData['daily']['precipitation_probability_max'][$index] ?? null,
                        ];
                    }
                } else {
                    $forecastMessage = 'The weekly forecast is temporarily unavailable.';
                }
            } else {
                $message = 'Unable to fetch weather data. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'City not found.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please enter a city.';
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_city'])) {
    $favoriteCity = trim($_POST['favorite_city']);
    if ($favoriteCity !== '') {
        $check = $pdo->prepare('SELECT id FROM favorites WHERE user_id = :user_id AND city_name = :city_name');
        $check->execute(['user_id' => $userId, 'city_name' => $favoriteCity]);

        if (!$check->fetch()) {
            $stmtInsert = $pdo->prepare('INSERT INTO favorites (user_id, city_name) VALUES (:user_id, :city_name)');
            $stmtInsert->execute(['user_id' => $userId, 'city_name' => $favoriteCity]);
            $message = 'Favorite city added.';
        } else {
            $message = 'City already in favorites.';
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . APP_BASE_PATH . '/auth/login.php');
    exit;
}

$searchStmt = $pdo->prepare('SELECT * FROM weather_searches WHERE user_id = :user_id ORDER BY searched_at DESC LIMIT 30');
$searchStmt->execute(['user_id' => $userId]);
$searchHistory = $searchStmt->fetchAll();

$favStmt = $pdo->prepare('SELECT * FROM favorites WHERE user_id = :user_id ORDER BY created_at DESC');
$favStmt->execute(['user_id' => $userId]);
$favorites = $favStmt->fetchAll();
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<div class="dashboard-app">
    <nav class="dashboard-nav">
        <div class="dashboard-nav-inner">
            <a class="dashboard-brand" href="dashboard.php">
                <span class="dashboard-brand-mark" aria-hidden="true">W</span>
                <span>Wewo</span>
            </a>
            <div class="dashboard-links">
                <a class="dashboard-link is-active" href="dashboard.php">Overview</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a class="dashboard-link" href="admin/dashboard.php">Admin</a>
                <?php endif; ?>
                <span class="dashboard-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <a class="dashboard-link dashboard-logout" href="dashboard.php?logout=1">Sign out</a>
            </div>
        </div>
    </nav>

    <main class="container dashboard-container">
        <section class="dashboard-heading">
            <div>
                <p class="dashboard-eyebrow">YOUR WEATHER BOARD</p>
                <h1>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                <p class="dashboard-subtitle">A clear look at the places on your list.</p>
            </div>
            <span class="dashboard-account-role"><?= htmlspecialchars(ucfirst($user['role'])) ?> account</span>
        </section>

        <?php if (!empty($message)): ?>
            <div class="alert dashboard-alert <?= htmlspecialchars($messageType) ?>" role="<?= $messageType === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="dashboard-controls">
            <form class="dashboard-card search-card" id="weather-search" method="POST" action="dashboard.php">
                <label class="dashboard-field-label" for="city-search">Search a city</label>
                <div class="dashboard-control-row">
                    <input id="city-search" type="search" name="city" placeholder="Start typing a city name" list="city-suggestions" autocomplete="off" required>
                    <datalist id="city-suggestions"></datalist>
                    <button class="dashboard-button primary" type="submit">Search weather <span aria-hidden="true">&#8594;</span></button>
                </div>
            </form>

            <form class="dashboard-card favorite-form" method="POST" action="dashboard.php">
                <label class="dashboard-field-label" for="favorite-city">Add a favorite</label>
                <div class="dashboard-control-row">
                    <input id="favorite-city" type="text" name="favorite_city" placeholder="City name" required>
                    <button class="dashboard-button secondary" type="submit">Add</button>
                </div>
            </form>
        </section>

        <?php if (!$currentWeather): ?>
            <section class="weather-empty" aria-label="Weather overview">
                <span class="weather-empty-symbol" aria-hidden="true">☁</span>
                <div>
                    <p class="weather-eyebrow">WEATHER OVERVIEW</p>
                    <h2>Ready when you are</h2>
                    <p>Search for a city to see its current conditions.</p>
                </div>
            </section>
        <?php endif; ?>

    <?php if ($currentWeather): ?>
        <?php
        $currentCondition = $currentWeather['weather'][0]['description'] ?? 'Current conditions';
        $weatherIcon = $currentWeather['weather'][0]['icon'] ?? '';
        ?>
        <section class="weather-panel current-weather" aria-labelledby="current-weather-title">
            <div class="current-weather-heading">
                <div>
                    <p class="weather-eyebrow">CURRENT CONDITIONS</p>
                    <h2 id="current-weather-title">
                        <?= htmlspecialchars($currentWeather['name'] ?? '') ?><?= !empty($currentWeather['sys']['country']) ? ', ' . htmlspecialchars($currentWeather['sys']['country']) : '' ?>
                    </h2>
                    <p class="weather-updated"><?= date('l, F j', (int) ($currentWeather['dt'] ?? time())) ?></p>
                </div>
                <div class="current-condition">
                    <?php if (preg_match('/^[0-9]{2}[dn]$/', $weatherIcon)): ?>
                        <img src="https://openweathermap.org/img/wn/<?= htmlspecialchars($weatherIcon) ?>@2x.png" alt="">
                    <?php endif; ?>
                    <span><?= htmlspecialchars(ucfirst($currentCondition)) ?></span>
                </div>
            </div>
            <div class="current-weather-body">
                <strong class="current-temperature"><?= round((float) ($currentWeather['main']['temp'] ?? 0)) ?>°</strong>
                <div class="weather-metrics">
                    <div><span>Feels like</span><strong><?= round((float) ($currentWeather['main']['feels_like'] ?? 0)) ?>°C</strong></div>
                    <div><span>Humidity</span><strong><?= (int) ($currentWeather['main']['humidity'] ?? 0) ?>%</strong></div>
                    <div><span>Wind</span><strong><?= round((float) ($currentWeather['wind']['speed'] ?? 0) * 3.6) ?> km/h</strong></div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($forecastMessage): ?>
        <div class="alert error" role="status"><?= htmlspecialchars($forecastMessage) ?></div>
    <?php endif; ?>

    <?php if ($weeklyForecast): ?>
        <section class="weather-panel weekly-weather" aria-labelledby="weekly-weather-title">
            <div class="section-heading">
                <div>
                    <p class="weather-eyebrow">PLAN AHEAD</p>
                    <h2 id="weekly-weather-title">7-day forecast</h2>
                </div>
                <span class="forecast-unit">°C</span>
            </div>
            <div class="forecast-grid">
                <?php foreach ($weeklyForecast as $index => $day): ?>
                    <article class="forecast-day">
                        <h3><?= $index === 0 ? 'Today' : htmlspecialchars(date('D', strtotime($day['date']))) ?></h3>
                        <span class="forecast-icon" aria-hidden="true"><?= weatherIconFromCode($day['code']) ?></span>
                        <p><?= htmlspecialchars(weatherDescriptionFromCode($day['code'])) ?></p>
                        <div class="forecast-temperatures">
                            <strong><?= $day['high'] !== null ? round((float) $day['high']) . '°' : '--' ?></strong>
                            <span><?= $day['low'] !== null ? round((float) $day['low']) . '°' : '--' ?></span>
                        </div>
                        <?php if ($day['precipitation'] !== null): ?>
                            <small><?= (int) $day['precipitation'] ?>% rain</small>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

        <section class="dashboard-lists">
            <section class="dashboard-card history-card">
                <div class="dashboard-card-heading history-heading">
                    <div>
                        <p class="dashboard-eyebrow">RECENT PLACES</p>
                        <h2>Search history</h2>
                    </div>
                    <input id="history-filter" type="search" placeholder="Filter cities" aria-label="Filter search history">
                </div>
                <div class="history-list">
                    <?php if ($searchHistory): ?>
                        <?php foreach ($searchHistory as $item): ?>
                            <button class="history-entry" type="button" data-city="<?= htmlspecialchars(strtolower($item['city_name']), ENT_QUOTES, 'UTF-8') ?>" data-query="<?= htmlspecialchars($item['city_name'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Search weather for <?= htmlspecialchars($item['city_name'], ENT_QUOTES, 'UTF-8') ?> again">
                                <span><?= htmlspecialchars($item['city_name']) ?></span>
                                <strong><?= htmlspecialchars($item['temperature']) ?>°C</strong>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="history-empty">No searches yet.</p>
                    <?php endif; ?>
                    <p id="history-no-match" class="history-empty" hidden>No matching cities.</p>
                </div>
            </section>

            <section class="dashboard-card favorites-card">
                <div class="dashboard-card-heading">
                    <div>
                        <p class="dashboard-eyebrow">SAVED</p>
                        <h2>Favorite cities</h2>
                    </div>
                </div>
                <div class="favorites-list">
                    <?php if ($favorites): ?>
                        <?php foreach ($favorites as $item): ?>
                            <span class="chip"><?= htmlspecialchars($item['city_name']) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="history-empty">No favorite cities yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>
</div>
<script src="<?= htmlspecialchars(APP_BASE_PATH, ENT_QUOTES, 'UTF-8') ?>/weather.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>

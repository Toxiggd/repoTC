<?php
header('Content-Type: application/json');
require_once 'auth.php';

/// DATA FOLDER
define('DATA_DIR', __DIR__ . '/data/');
define('MOVIES_FILE', DATA_DIR . 'movies.json');
define('USERS_FILE', DATA_DIR . 'users.json');

///stworz data
if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!file_exists(MOVIES_FILE)) file_put_contents(MOVIES_FILE, json_encode([]));
if (!file_exists(USERS_FILE)) {
    /// DEFAULT
    $defaultHash = password_hash('admin123', PASSWORD_BCRYPT);
    file_put_contents(USERS_FILE, json_encode([
        ['id' => 1, 'username' => 'admin', 'password_hash' => $defaultHash]
    ]));
}

///aUTORYZACJA
authenticateRequest();

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$segments = explode('/', $path);

///GET/POST/PUT/DELETE
if ($segments[0] === 'movies') {
    $id = isset($segments[1]) ? (int)$segments[1] : null;
    
    switch ($method) {
        case 'GET':
            handleGetMovies($id);
            break;
        case 'POST':
            handlePostMovie();
            break;
        case 'PUT':
            if ($id) handlePutMovie($id);
            else errorResponse(400, 'Missing movie ID');
            break;
        case 'DELETE':
            if ($id) handleDeleteMovie($id);
            else errorResponse(400, 'Missing movie ID');
            break;
        default:
            errorResponse(405, 'Method not allowed');
    }
} else {
    errorResponse(404, 'Endpoint not found');
}

///funkcje

function handleGetMovies($id = null) {
    $movies = loadMovies();

    if ($id) {
        foreach ($movies as $movie) {
            if ($movie['id'] == $id) {
                jsonResponse(200, $movie);
                return;
            }
        }
        errorResponse(404, 'Movie not found');
    } else {
        if (isset($_GET['min_rating']) && $_GET['min_rating'] !== '') {

            $filter = $_GET['min_rating'];

            ///FILTR
            if (strpos($filter, '-') !== false) {
                list($min, $max) = explode('-', $filter);
                $min = (int)$min;
                $max = (int)$max;

                $movies = array_filter($movies, function($m) use ($min, $max) {
                    return $m['rating'] >= $min && $m['rating'] <= $max;
                });

            } else {
                $min = (int)$filter;

                $movies = array_filter($movies, function($m) use ($min) {
                    return $m['rating'] == $min;
                });
            }

            $movies = array_values($movies);
        }

        jsonResponse(200, $movies);
    }
}

function handlePostMovie() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['title']) || !isset($data['year']) || !isset($data['rating']) || !isset($data['watch_date'])) {
        errorResponse(400, 'Missing required fields: title, year, rating, watch_date');
    }

    $movies = loadMovies();

/// DUPLIKAT
    foreach ($movies as $m) {
        if (strtolower($m['title']) === strtolower($data['title'])) {
            errorResponse(409, 'Movie already exists');
        }
    }

    $newId = empty($movies) ? 1 : max(array_column($movies, 'id')) + 1;

    $newMovie = [
        'id' => $newId,
        'title' => $data['title'],
        'year' => (int)$data['year'],
        'rating' => (int)$data['rating'],
        'watch_date' => $data['watch_date']
    ];

    $movies[] = $newMovie;
    saveMovies($movies);

    jsonResponse(201, $newMovie);
}

function handlePutMovie($id) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['title']) || !isset($data['year']) || !isset($data['rating']) || !isset($data['watch_date'])) {
        errorResponse(400, 'Missing required fields: title, year, rating, watch_date');
    }

    $movies = loadMovies();
    $found = false;
	
    foreach ($movies as $m) {
        if ($m['id'] != $id && strtolower($m['title']) === strtolower($data['title'])) {
            errorResponse(409, 'Movie already exists');
        }
    }

    foreach ($movies as &$movie) {
        if ($movie['id'] == $id) {
            $movie['title'] = $data['title'];
            $movie['year'] = (int)$data['year'];
            $movie['rating'] = (int)$data['rating'];
            $movie['watch_date'] = $data['watch_date'];
            $found = true;
            break;
        }
    }

    if (!$found) errorResponse(404, 'Movie not found');

    saveMovies($movies);
    jsonResponse(200, ['message' => 'Movie updated']);
}

function handleDeleteMovie($id) {
    $movies = loadMovies();
    $newMovies = array_filter($movies, function($m) use ($id) {
        return $m['id'] != $id;
    });

    if (count($newMovies) == count($movies)) {
        errorResponse(404, 'Movie not found');
    }

    saveMovies(array_values($newMovies));
    jsonResponse(200, ['message' => 'Movie deleted']);
}

function loadMovies() {
    $content = file_get_contents(MOVIES_FILE);
    return json_decode($content, true) ?? [];
}

function saveMovies($movies) {
    file_put_contents(MOVIES_FILE, json_encode($movies, JSON_PRETTY_PRINT));
}

function jsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function errorResponse($status, $message) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}
?>

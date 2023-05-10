<?php

// Import dependencies
require_once('connection.php');
require_once('helpers.php');
require_once('config.php');
require_once 'Logger/logger.php';
require __DIR__ . "/vendor/autoload.php";

// Create Logger instance
$logger = Logger::getLogger();

// TODO
// use this variable
// PATH_INFO


// Define the API version and endpoint path
$api_version = 'v1';
$endpoint_path = 'users';

// Set headers
set_headers();

// Define the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request URI
$request_uri = $_SERVER['REQUEST_URI'];

$path = parse_url($request_uri, PHP_URL_PATH);

// Check for JWT token

// Retrieve the Authorization header
$authHeader = isset(getallheaders()['Authorization']) ? getallheaders()['Authorization'] : '';
$tokenArr = explode(' ', $authHeader);

// Split the api uri
$segments = explode('/', $path);

if (isset($segments[2]) && $segments[2] !== $api_version) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported API version']);
    exit(1);
}

// Check if the request path matches the endpoint path
if (isset($segments[3]) && $segments[3] === $endpoint_path) {
    // Handle the CRUD operations based on the HTTP method
    switch ($method) {
        case 'GET':

            /* This code block handles a GET request to retrieve users from the database. It starts by verifying the JWT token passed with the request, then checks if a specific user ID has been requested. If a user ID has been provided, the code fetches that user's details from the database and returns it as a JSON-encoded response. If no specific ID has been requested, the code fetches all users' details from the database and returns them as a JSON-encoded response. */

            try {
                require 'jwt.php';
                require 'user.php';
                $user = new User();

                JWTToken::verifyJWTToken($tokenArr[1]);

                if (isset($segments[4]) && !empty(trim($segments[4]))) {
                    // Sanitize the user id
                    $userId = filter_var($segments[4], FILTER_SANITIZE_NUMBER_INT);
                    $data = $user->fetchSingleUser($pdo, $userId);
                    if (!$data) {
                        http_response_code(404);
                        echo json_encode(['error' => 'User not found']);
                    } else {
                        echo json_encode($data);
                    }
                } else {
                    $data =  $user->fetchAllUsers($pdo);
                    echo json_encode($data);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'POST':

            /* The code handles a POST request to add a new user to a database. The request body is parsed to extract the name, gender, and email fields. The input is sanitized using htmlspecialchars and filter_var functions, respectively. If any of the required parameters are missing, a 400 Bad Request response is returned.

            If all required parameters are present, the user data is inserted into the database. If the insertion is successful, the newly created user is retrieved from the database using the lastInsertId method. The user's data is then sanitized, and a JSON Web Token (JWT) is generated for the user. Finally, a JSON response is returned containing the JWT. */

            $data = json_decode(file_get_contents('php://input'), true);
            // sanitize input
            $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
            $gender = htmlspecialchars($data['gender'], ENT_QUOTES, 'UTF-8');
            $user_name = htmlspecialchars($data['user_name'], ENT_QUOTES, 'UTF-8');
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
            $password = $data['password'];

            require 'user.php';
            $user = new User();

            // validate the request body
            if (empty($name) || empty($gender) || empty($email) || empty($user_name) || empty($password)) {
                $user->logger->log('Missing required parameters');
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit();
            }

            $token = $user->registration($pdo, $name, $user_name, $gender, $email, $password);
            echo json_encode(['token' => $token]);

            break;

        case 'PUT':
            // Handle PUT request
            if (!isset($segments[4]) && empty(trim($segments[4]))) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing User ID']);
            }
            $userId = filter_var($segments[4], FILTER_SANITIZE_NUMBER_INT);
            // validate the request body
            $data = json_decode(file_get_contents('php://input'), true);

            // sanitize input
            $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
            $user_name = htmlspecialchars($data['user_name'], ENT_QUOTES, 'UTF-8');
            $gender = htmlspecialchars($data['gender'], ENT_QUOTES, 'UTF-8');
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

            $postData = [
                'userId' => $userId,
                'name' => $name,
                'user_name' => $user_name,
                'gender' => $gender,
                'email' => $email,
            ];
            require 'user.php';
            $user = new User();

            // validate the request body
            if (empty($name) || empty($gender) || empty($email)) {
                $user->logger->log('Missing required parameters');
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit();
            }

            $data = $user->updateUser($pdo, $postData);

            if ($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => 'User not found',
                ]);
            }
            break;

        case 'DELETE':
            // Handle PUT request
            if (!isset($segments[4])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing User ID']);
            }
            // Delete a single user
            $userId = filter_var($segments[4], FILTER_SANITIZE_NUMBER_INT);
            require 'user.php';
            $user = new User();
            $data = $user->deleteUser($pdo, $userId);
            if ($data === 204) {
                http_response_code(204); // Not Found
                echo json_encode(['error' => 'User delete successfully.']);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'User not found.']);
            }
            break;

        default:
            // Method not supported
            http_response_code(405);
            echo json_encode(['error' => 'Method not supported']);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Resource not found!']);
}

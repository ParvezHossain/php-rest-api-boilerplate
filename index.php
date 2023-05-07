<?php

require_once('connection.php');
require_once('helpers.php');


// Define the API version and endpoint path
$api_version = 'v1';
$endpoint_path = '/users';

// Set headers
set_headers();

// Define the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request URI
$request_uri = $_SERVER['REQUEST_URI'];

$path = parse_url($request_uri, PHP_URL_PATH);

// Check for JWT token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$tokenArr = explode(' ', $authHeader);

echo $path . PHP_EOL;
echo $endpoint_path; die();


// Check if the request path matches the endpoint path
if (strpos($path, $endpoint_path) === 0) {
    $remaining_path = substr($path, strlen($endpoint_path));

    // Split the remaining path into an array of segments
    $segments = explode('/', $remaining_path);

    // Check the API version
    if (isset($segments[1]) && $segments[1] !== $api_version) {
        header('HTTP/1.1 400 Bad Request');
        die('Unsupported API version');
    }

    // Handle the CRUD operations based on the HTTP method

    switch ($method) {
        case 'GET':
            // Handle GET request
            try {

                if (!$jwt) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit();
                }

                // Verify JWT token
                try {
                    $decoded = JWT::decode($jwt, 'secret_key', array('HS356'));
                } catch (Exception $e) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                }

                if (isset($segments[2])) {
                    // Get a single user by ID
                    $user_id = filter_var($segments[2], FILTER_SANITIZE_NUMBER_INT);
                    $stmt = $pdo->prepare('SELECT * FROM USERS WHERE id = ?');
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$user) {
                        header('HTTP/1.1 404 Not Found');
                        echo json_encode(['error' => 'User not found']);
                    } else {
                        echo json_encode($user);
                    }
                } else {
                    $stmt = $pdo->query('SELECT * FROM users');
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($users);
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
            // Handle POST request
            $data = json_decode(file_get_contents('php://input'), true);

            // sanitize input
            $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
            $gender = htmlspecialchars($data['gender'], ENT_QUOTES, 'UTF-8');
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

            // validate the request body
            if (empty($name) || empty($gender) || empty($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit();
            }

            try {
                $pdo->beginTransaction();
                // insert the user into the database
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, gender, email) VALUES (:name, :gender, :email)'
                );
                $stmt->execute([$name, $gender, $email]);
                $stmt->execute();

                // retrieve the newly created user from the database
                $user_id = $pdo->lastInsertId();
                $stmt = $conn->prepare('SELECT * FROM users WHERE id = :id');
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $pdo->commit();

                // sanitize the user data before returning it
                $user['name'] = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
                $user['gender'] = htmlspecialchars($user['gender'], ENT_QUOTES, 'UTF-8');
                $user['email'] = filter_var($user['email'], FILTER_SANITIZE_EMAIL);

                require __DIR__ . 'jwt.php';
                $user['token'] = JWTToken::generateJWTToken('secret_key', 'Server', 'Audience', time(), $issuedat_claim + 10, $issuedat_claim + 60, [$user_id, $user['name']]);

                echo json_encode($user);
            } catch (PDOException $e) {
                $pdo->rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                exit();
            } catch (Exception $e) {
                $pdo->rollback();
                http_response_code($e->getCode());
                echo json_encode(['error' => $e->getMessage()]);
                exit();
            }
            break;

        case 'PUT':
            // Handle PUT request
            if (!isset($segments[2])) {
                header('HTTP/1.1 400 Bad Request');
                die('Missing user ID');
            }
            $user_id = filter_var($segments[2], FILTER_SANITIZE_NUMBER_INT);

            // validate the request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // sanitize input
            $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
            $gender = htmlspecialchars($data['gender'], ENT_QUOTES, 'UTF-8');
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

            // validate the request body
            if (empty($name) || empty($gender) || empty($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit();
            }

            try {
                $pdo->beginTransaction();
                // update the user in the database
                $stmt = $pdo->prepare(
                    'UPDATE users SET name = :name, gender = :gender, email = :email WHERE id = :id'
                );
                $stmt->bindParam(':id', $data['id']);
                $stmt->bindParam(':name', $data['name']);
                $stmt->bindParam(':gender', $data['gender']);
                $stmt->bindParam(':email', $data['email']);
                $stmt->execute();

                // retrieve the updated user from the database
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $pdo->commit();

                // sanitize the user data before returning it
                $user['name'] = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
                $user['gender'] = htmlspecialchars($user['gender'], ENT_QUOTES, 'UTF-8');
                $user['email'] = filter_var($user['email'], FILTER_SANITIZE_EMAIL);

                if (!$user) {
                    header('HTTP/1.1 404 Not Found');
                    die('User not found');
                } else {
                    echo json_encode($user);
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                header('HTTP/1.1 500 Internal Server Error');
                echo json_encode([
                    'error' => 'Database error: ' . $e->getMessage(),
                ]);
            }

            break;

        case 'DELETE':
            // Handle PUT request
            if (!isset($segments[2])) {
                header('HTTP/1.1 400 Bad Request');
                die('Missing user ID');
            }

            try {
                $pdo->beginTransaction();
                // Delete a single user
                $user_id = filter_var($segments[2], FILTER_SANITIZE_NUMBER_INT);
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();

                $pdo->commit();

                $affectedRows = $stmt->rowCount();
                if ($affectedRows > 0) {
                    http_response_code(204); // No Content
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'User not found.']);
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                http_response_code(500); // Internal Server Error
                json_encode([
                    'error' => 'Unable to delete user: ' . $e->getMessage(),
                ]);
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

<?php
require_once 'jwt.php';
class User
{
    public function __construct()
    {
    }

    /*
        Function Name: registration
        Description:
            This function registers a new user and generates a JWT token. It accepts four parameters - $pdo which is an instance of PDO class, $name which is a string that contains the user's name, $gender which is a string that contains the user's gender and $email which is a string that contains the user's email address. It returns a string that contains the generated JWT token.

        Parameters:
            $pdo: An instance of PDO class.
            $name: A string that contains the user's name.
            $gender: A string that contains the user's gender.
            $email: A string that contains the user's email address.

        Return Value:
            A string that contains the generated JWT token.
        
        Exceptions:
            If a PDOException occurs while inserting the user data into the database, it will throw an HTTP 500 error along with the message 'Database error: ' and the error message.
            If an Exception occurs, it will throw an HTTP error code along with the error message. 
*/
    public function registration($pdo, $name, $gender, $email): string
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, gender, email) VALUES (:name, :gender, :email)');
            $stmt->execute([$name, $gender, $email]);
            $user_id = $pdo->lastInsertId();
            return JWTToken::generateJWTToken([$user_id, $name]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit();
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }
    public function login()
    {
    }

    /* 
    Function Name: fetchSingleUser
    Input Parameters:
        $pdo: an instance of the PDO class representing a connection to the database
        $user_id: an integer representing the ID of the user to fetch

    Return Value:
        If the user with the given ID is found in the database, an associative array containing the user's data is returned.
        If the user with the given ID is not found in the database, null is returned.

    Functionality:
        This function fetches a single user from the database using the provided user ID. It first prepares a SQL statement that selects all columns from the 'users' table where the 'id' column matches the given user ID. The statement is then executed using the provided PDO object, and the result is fetched as an associative array. If a user with the given ID is found, the function returns the user data as an array. If the user is not found, the function returns null.

    Exception Handling:
        The function catches any PDOException or Exception that might occur during the execution of the SQL query. If an exception is caught, the function sets the HTTP response code to 500 and returns an error message as a JSON-encoded string.
*/
    public function fetchSingleUser(PDO $pdo, int $userId): ?array
    {
        try {
            $stmt = $pdo->prepare('SELECT * FROM USERS WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetches all users from the database.
     * 
     * @param PDO $pdo The PDO object representing the database connection.
     * 
     * @return array Returns an array of associative arrays, each containing a user's details.
     * 
     * @throws PDOException If there is an error executing the database query.
     */
    public function fetchAllUsers(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query('SELECT * FROM users');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $users;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /* 
    Function Name: updateUser

    Input Parameters:

        $pdo: an instance of the PDO class representing a connection to the database.
        $data: an associative array containing the data to be updated for the user.
    Return Value:

        If the user with the given ID is successfully updated in the database, an associative array containing the updated user data is returned.
        If the user with the given ID is not found in the database, the function returns an HTTP response code 404 with an error message indicating that the user was not found.
        If there is a database error, the function returns an HTTP response code 500 with an error message indicating that there was a database error.
        If there is a server error, the function returns an HTTP response code 500 with an error message indicating that there was a server error.
    Functionality:
        The updateUser function updates the user data in the database based on the provided $data array. It first prepares a SQL statement that updates the 'name', 'gender', and 'email' columns in the 'users' table where the 'id' column matches the given user ID. The statement is then executed using the provided PDO object. If the update is successful, the function returns an associative array containing the updated user data. If the user is not found, the function returns an HTTP response code 404 with an error message indicating that the user was not found. If there is a database error, the function returns an HTTP response code 500 with an error message indicating that there was a database error. If there is a server error, the function returns an HTTP response code 500 with an error message indicating that there was a server error.

    Exception Handling:
        The updateUser function catches any PDOException or Exception that might occur during the execution of the SQL query. If there is a database error, the function returns an HTTP response code 500 with an error message indicating that there was a database error. If there is a server error, the function returns an HTTP response code 500 with an error message indicating that there was a server error. If the user is not found, the function returns an HTTP response code 404 with an error message indicating that the user was not found.
*/
    public function updateUser($pdo, array $data): array
    {
        try {
            $stmt = $pdo->prepare(
                'UPDATE users SET name = :name, gender = :gender, email = :email WHERE id = :id'
            );
            $stmt->execute([$data['name'], $data['gender'], $data['email'], $data['userId']]);
            $user = [];
            if ($stmt->rowCount() > 0) {
                $user['name'] = $data['name'];
                $user['gender'] = $data['gender'];
                $user['email'] = $data['email'];
                return $user;
            } else {
                return $user;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error: ' . $e->getMessage(),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Server error: ' . $e->getMessage(),
            ]);
        }
    }
/* 
    Function deleteUser() takes two parameters: a PDO object and a user ID. It deletes a user record from the database that matches the given user ID and returns an HTTP status code.

If the deletion is successful, it returns a 204 status code indicating that there is no content to return. If the user ID is not found in the database, it returns a 404 status code indicating that the requested resource was not found. If there is a database error, it returns a 500 status code indicating that there is an internal server error.

The function uses a prepared statement to delete the user record from the database. The statement is prepared with the SQL query to delete a user record from the users table with the matching ID. The user ID is bound to the prepared statement and executed. The number of affected rows is then checked. If the number of affected rows is greater than 0, it returns a 204 status code. Otherwise, it returns a 404 status code.

If there is a PDO exception, the function sets the HTTP response code to 500 and returns an error message in JSON format.
*/
    public function deleteUser($pdo, $userId)
    {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([$userId]);

            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                return 204; // No Content
            } else {
                return 404;
            }
        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            json_encode([
                'error' => 'Unable to delete user: ' . $e->getMessage(),
            ]);
        }
    }
}

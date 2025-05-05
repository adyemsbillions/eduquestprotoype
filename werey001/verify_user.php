<?php
require_once 'db_connection.php';

// Function to prepare and execute with retry and fallback
function prepareAndExecute($conn, $sql, $param_type, ...$params)
{
    $max_attempts = 3;
    $attempt = 0;

    while ($attempt < $max_attempts) {
        $stmt = null;
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error . " (errno: " . $conn->errno . ")");
            }

            if (!empty($params)) {
                $stmt->bind_param($param_type, ...$params);
            }

            if ($stmt->execute()) {
                return $stmt;
            }
            throw new Exception("Execute failed: " . $stmt->error . " (errno: " . $stmt->errno . ")");
        } catch (Exception $e) {
            $attempt++;
            if ($stmt) $stmt->close();

            error_log("Attempt $attempt failed: " . $e->getMessage());

            if ($attempt == $max_attempts) {
                // Fallback to non-prepared statement as last resort
                try {
                    $escaped_params = array_map([$conn, 'real_escape_string'], $params);
                    $query = vsprintf(str_replace('?', "'%s'", $sql), $escaped_params);
                    error_log("Falling back to non-prepared query: $query");
                    if ($conn->query($query)) {
                        return null; // No statement to return
                    }
                    throw new Exception("Fallback query failed: " . $conn->error);
                } catch (Exception $fallback_e) {
                    throw new Exception("All attempts failed: " . $e->getMessage() . "; Fallback: " . $fallback_e->getMessage());
                }
            }

            // Attempt reconnection on specific errors
            if ($conn->errno == 2006 || $conn->errno == 2013) {
                $conn->close();
                $conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
                if ($conn->connect_error) {
                    throw new Exception("Reconnection failed: " . $conn->connect_error);
                }
            }
            usleep(200000); // Increased to 0.2 seconds
        }
    }
}

// Handle user verification/unverification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "Invalid user ID"]);
        exit;
    }

    $verification_types = [
        'blue_verify' => ['value' => 1, 'message' => 'User successfully blue verified'],
        'gold_verify' => ['value' => 2, 'message' => 'User successfully gold verified'],
        'black_verify' => ['value' => 3, 'message' => 'User successfully black verified'],
        'pink_verify' => ['value' => 4, 'message' => 'User successfully pink verified'],
        'unverify' => ['value' => 0, 'message' => 'User successfully unverified']
    ];

    if (!array_key_exists($action, $verification_types)) {
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        exit;
    }

    try {
        $sql = "UPDATE users SET verified = ? WHERE id = ?";
        $stmt = prepareAndExecute($conn, $sql, "ii", $verification_types[$action]['value'], $user_id);
        if ($stmt) $stmt->close();
        echo json_encode([
            "status" => "success",
            "message" => $verification_types[$action]['message']
        ]);
    } catch (Exception $e) {
        error_log("Verification Error: " . $e->getMessage() . " (errno: " . $conn->errno . ")");
        echo json_encode([
            "status" => "error",
            "message" => "Error updating verification: " . $e->getMessage()
        ]);
    }
    exit;
}

// Handle autocomplete search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term']) && !isset($_POST['manual_search'])) {
    $search_term = "%" . $conn->real_escape_string($_POST['search_term']) . "%";
    $sql = "SELECT id, username FROM users WHERE username LIKE ? OR id LIKE ? LIMIT 10";
    try {
        $stmt = prepareAndExecute($conn, $sql, "ss", $search_term, $search_term);
        $result = $stmt ? $stmt->get_result() : $conn->query($sql); // Fallback result
        $suggestions = [];
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = [
                'label' => "{$row['username']} (ID: {$row['id']})",
                'value' => $row['id']
            ];
        }
        echo json_encode($suggestions);
        if ($stmt) $stmt->close();
    } catch (Exception $e) {
        error_log("Autocomplete Error: " . $e->getMessage());
        echo json_encode([]);
    }
    exit;
}

// Handle manual search with button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_search'])) {
    $search_term = "%" . $conn->real_escape_string($_POST['search_term']) . "%";
    $sql = "SELECT id, username, profile_picture, verified FROM users WHERE username LIKE ? OR id LIKE ? ORDER BY username ASC";
    try {
        $stmt = prepareAndExecute($conn, $sql, "ss", $search_term, $search_term);
        $result = $stmt ? $stmt->get_result() : $conn->query($sql);
        if ($stmt) $stmt->close();
    } catch (Exception $e) {
        error_log("Manual Search Error: " . $e->getMessage());
        $result = $conn->query("SELECT id, username, profile_picture, verified FROM users ORDER BY username ASC"); // Fallback
    }
} else {
    $sql = "SELECT id, username, profile_picture, verified FROM users ORDER BY username ASC";
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
    :root {
        --blue: #2196F3;
        --gold: #FFD700;
        --black: #333;
        --pink: #FF66B2;
        --red: #F44336;
        --white: #fff;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(135deg, #f4f7fc 0%, #e8eef6 100%);
        min-height: 100vh;
        color: #333;
    }

    .container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }

    h2 {
        text-align: center;
        color: #2196F3;
        font-size: 2.2rem;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .search-container {
        margin-bottom: 30px;
        text-align: center;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    #search-input {
        padding: 12px 20px;
        width: 50%;
        max-width: 400px;
        border: 2px solid #ddd;
        border-radius: 25px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    #search-input:focus {
        border-color: var(--blue);
        outline: none;
        box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
    }

    #search-btn {
        padding: 12px 20px;
        background: var(--blue);
        color: var(--white);
        border: none;
        border-radius: 25px;
        cursor: pointer;
        transition: var(--transition);
    }

    #search-btn:hover {
        background: #1976D2;
    }

    .ui-autocomplete {
        background: var(--white);
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: var(--shadow);
        z-index: 1000;
    }

    .ui-menu-item {
        padding: 8px 12px;
        cursor: pointer;
    }

    .ui-menu-item:hover {
        background: #f0f0f0;
    }

    .user-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 10px;
    }

    .user-card {
        background: var(--white);
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        text-align: center;
    }

    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .user-card img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 15px;
        object-fit: cover;
        border: 3px solid #ddd;
    }

    .user-card p {
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 15px;
        color: #444;
    }

    .button-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
    }

    .user-card button {
        padding: 8px 16px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: var(--transition);
    }

    .blue-verify-btn {
        background: var(--blue);
        color: var(--white);
    }

    .gold-verify-btn {
        background: var(--gold);
        color: #333;
    }

    .black-verify-btn {
        background: var(--black);
        color: var(--white);
    }

    .pink-verify-btn {
        background: var(--pink);
        color: var(--white);
    }

    .unverify-btn {
        background: var(--red);
        color: var(--white);
    }

    .blue-verify-btn:hover {
        background: #1976D2;
    }

    .gold-verify-btn:hover {
        background: #FFC107;
    }

    .black-verify-btn:hover {
        background: #555;
    }

    .pink-verify-btn:hover {
        background: #FF4081;
    }

    .unverify-btn:hover {
        background: #D32F2F;
    }

    .alert {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 30px;
        border-radius: 8px;
        box-shadow: var(--shadow);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .alert.success {
        background: #4CAF50;
        color: var(--white);
    }

    .alert.error {
        background: var(--red);
        color: var(--white);
    }

    @media (max-width: 768px) {
        .user-card {
            padding: 15px;
        }

        .user-card img {
            width: 60px;
            height: 60px;
        }

        #search-input {
            width: 70%;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 10px;
            margin: 20px auto;
        }

        h2 {
            font-size: 1.8rem;
        }

        #search-input {
            width: 60%;
        }

        .search-container {
            flex-direction: column;
            gap: 15px;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>User Verification</h2>

        <form id="search-form" method="POST">
            <div class="search-container">
                <input type="text" id="search-input" name="search_term" placeholder="Search by username or ID...">
                <button type="submit" id="search-btn" name="manual_search">Search</button>
            </div>
        </form>

        <div id="alert-box" class="alert"></div>

        <div class="user-list" id="user-list">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($user = $result->fetch_assoc()) {
                    $verified = (int)$user['verified'];
                    echo '<div class="user-card" data-user-id="' . $user['id'] . '">';
                    echo '<img src="/dashboard/' . htmlspecialchars($user['profile_picture']) . '" alt="Avatar">';
                    echo '<p>' . htmlspecialchars($user['username']) . ' (ID: ' . $user['id'] . ')</p>';
                    echo '<div class="button-group">';

                    $buttons = [
                        ['class' => 'blue-verify-btn', 'text' => 'Blue', 'action' => 'blue_verify', 'show' => $verified !== 1],
                        ['class' => 'gold-verify-btn', 'text' => 'Gold', 'action' => 'gold_verify', 'show' => $verified !== 2],
                        ['class' => 'black-verify-btn', 'text' => 'Black', 'action' => 'black_verify', 'show' => $verified !== 3],
                        ['class' => 'pink-verify-btn', 'text' => 'Pink', 'action' => 'pink_verify', 'show' => $verified !== 4],
                        ['class' => 'unverify-btn', 'text' => 'Unverify', 'action' => 'unverify', 'show' => $verified !== 0]
                    ];

                    foreach ($buttons as $button) {
                        if ($button['show']) {
                            echo "<button class='{$button['class']}' data-user-id='{$user['id']}' data-action='{$button['action']}'>{$button['text']}</button>";
                        }
                    }
                    echo '</div></div>';
                }
            } else {
                echo '<p class="no-users">No users found.</p>';
            }
            $conn->close();
            ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Alert handling
        function showAlert(status, message) {
            const $alert = $('#alert-box');
            $alert.removeClass('success error')
                .addClass(status)
                .text(message)
                .css('opacity', 1);
            setTimeout(() => $alert.css('opacity', 0), 3000);
        }

        // Verification handling
        function handleVerification($button) {
            const userId = $button.data('user-id');
            const action = $button.data('action');

            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    user_id: userId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    showAlert(response.status, response.message);
                    if (response.status === 'success') {
                        setTimeout(() => location.reload(), 1000);
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('error', 'Verification request failed: ' + error);
                }
            });
        }

        $('.user-card button').on('click', function(e) {
            e.preventDefault();
            handleVerification($(this));
        });

        // Autocomplete search
        $('#search-input').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        search_term: request.term
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.length === 0) {
                            response([{
                                label: 'No results found',
                                value: null
                            }]);
                        } else {
                            response(data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Autocomplete error:', error);
                        response([{
                            label: 'Error fetching suggestions',
                            value: null
                        }]);
                    }
                });
            },
            minLength: 1,
            select: function(event, ui) {
                if (ui.item.value) {
                    $('.user-card').hide();
                    $(`.user-card[data-user-id="${ui.item.value}"]`).show();
                }
                return false;
            },
            open: function() {
                $(this).autocomplete('widget').css('z-index', 1000);
            }
        }).data('ui-autocomplete')._renderItem = function(ul, item) {
            return $('<li>')
                .append($('<div>').text(item.label))
                .appendTo(ul);
        };

        // Manual search button
        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            const searchTerm = $('#search-input').val().trim();
            if (searchTerm) {
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        search_term: searchTerm,
                        manual_search: true
                    },
                    success: function() {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        showAlert('error', 'Search failed: ' + error);
                    }
                });
            } else {
                location.reload();
            }
        });

        // Reset filter when search is cleared
        $('#search-input').on('input', function() {
            if (!this.value) {
                $('.user-card').show();
            }
        });
    });
    </script>
</body>

</html>
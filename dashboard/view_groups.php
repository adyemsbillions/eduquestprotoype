<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to log in first.");
}

$userId = $_SESSION['user_id'];

// Database connection
$conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search query
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchParam = "%" . $searchTerm . "%";

$sql = "SELECT g.id, g.name, g.description, g.is_public, gm.user_id
        FROM `groups` g
        LEFT JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = ?
        WHERE (g.is_public = 1 OR gm.user_id IS NOT NULL)
        AND (g.name LIKE ? OR g.description LIKE ?)";

// Prepare statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $userId, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Groups</title>
    <style>
            :root {
            --primary: #6a1b9a; /* Purple */
            --primary-hover: #4a148c; /* Darker purple */
            --secondary: #f3e5f5; /* Light purple background */
            --text: #333;
            --white: #fff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --border: #d1c4e9; /* Light purple border */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--secondary);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 30px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        h1 {
            text-align: center;
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .group {
            background: var(--white);
            border: 2px solid var(--border);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .group:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(106, 27, 154, 0.2);
            border-color: var(--primary);
        }

        .group h3 {
            margin: 0 0 12px 0;
            font-size: 24px;
            color: var(--primary);
            font-weight: 600;
        }

        .group p {
            font-size: 16px;
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .group form {
            text-align: center;
            margin-top: 15px;
        }

        .group input[type="submit"] {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .group input[type="submit"]:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(74, 20, 140, 0.3);
        }

        .group input[type="submit"]:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .group .already-member {
            font-size: 14px;
            color: var(--primary);
            font-style: italic;
            text-align: center;
            margin-top: 15px;
            background: rgba(106, 27, 154, 0.1);
            padding: 8px;
            border-radius: 5px;
        }

        hr {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 25px 0;
        }

        .no-groups {
            text-align: center;
            font-size: 18px;
            color: #666;
            padding: 30px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin: 20px auto;
            max-width: 600px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px auto;
            }

            h1 {
                font-size: 28px;
            }

            .group h3 {
                font-size: 20px;
            }

            .group p {
                font-size: 14px;
            }

            .group input[type="submit"] {
                padding: 10px 20px;
                font-size: 14px;
            }

            .group .already-member {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 24px;
            }

            .group {
                padding: 15px;
            }

            .group h3 {
                font-size: 18px;
            }

            .group p {
                font-size: 13px;
            }

            .group input[type="submit"] {
                width: 100%;
                padding: 10px;
            }
        }
        /* Previous styles remain unchanged */
        
        /* Add new styles for search */
        .search-container {
            margin-bottom: 30px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 8px rgba(106, 27, 154, 0.2);
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 6px;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: var(--shadow);
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 10px 20px;
            cursor: pointer;
            transition: var(--transition);
        }

        .suggestion-item:hover {
            background: var(--secondary);
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>Available Groups</h1>
    
    <!-- Search Form -->
    <div class="search-container">
        <form method="GET" action="">
            <input type="text" 
                   class="search-input" 
                   name="search" 
                   id="searchInput" 
                   placeholder="Search groups by name or description..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
        </form>
        <div class="suggestions" id="suggestions"></div>
    </div>

<?php
if ($result->num_rows > 0) {
    echo "<div class='groups-container'>";
    while ($row = $result->fetch_assoc()) {
        echo "<div class='group'>";
        echo "<h3>" . htmlspecialchars($row['name']) . "</h3>";
        echo "<p>" . htmlspecialchars($row['description']) . "</p>";
        
        if (is_null($row['user_id'])) {
            echo "<form action='join_group.php' method='POST'>
                    <input type='hidden' name='group_id' value='" . $row['id'] . "'>
                    <input type='submit' value='Join Group'>
                </form>";
        } else {
            echo "<p class='already-member'>You are already a member of this group.</p>";
        }
        
        echo "</div><hr>";
    }
    echo "</div>";
} else {
    echo "<div class='no-groups'>No groups found" . 
         ($searchTerm ? " matching '$searchTerm'" : " available to join") . 
         ".</div>";
}

$stmt->close();
$conn->close();
?>

</div>

<script>
// JavaScript for search suggestions
document.getElementById('searchInput').addEventListener('input', async function(e) {
    const query = e.target.value.trim();
    const suggestionsDiv = document.getElementById('suggestions');
    
    if (query.length < 2) {
        suggestionsDiv.style.display = 'none';
        return;
    }

    try {
        // Simulate fetching suggestions (you'd typically make an AJAX call here)
        const suggestions = await fetchSuggestions(query);
        if (suggestions.length > 0) {
            suggestionsDiv.innerHTML = suggestions
                .map(s => `<div class="suggestion-item" onclick="fillSearch('${s}')">${s}</div>`)
                .join('');
            suggestionsDiv.style.display = 'block';
        } else {
            suggestionsDiv.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching suggestions:', error);
        suggestionsDiv.style.display = 'none';
    }
});

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    const searchContainer = document.querySelector('.search-container');
    if (!searchContainer.contains(e.target)) {
        document.getElementById('suggestions').style.display = 'none';
    }
});

function fillSearch(value) {
    document.getElementById('searchInput').value = value;
    document.getElementById('suggestions').style.display = 'none';
    document.querySelector('form').submit();
}

// Simulated suggestion fetch (replace with actual backend call)
async function fetchSuggestions(query) {
    // This is a mock function - in production, make an AJAX call to a PHP endpoint
    const allGroups = <?php
        $groupNames = [];
        $stmt = $conn->prepare("SELECT name FROM `groups` WHERE is_public = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $groupNames[] = $row['name'];
        }
        echo json_encode($groupNames);
        $stmt->close();
    ?>;
    
    return allGroups
        .filter(name => name.toLowerCase().includes(query.toLowerCase()))
        .slice(0, 5); // Limit to 5 suggestions
}
</script>
</body>
</html>
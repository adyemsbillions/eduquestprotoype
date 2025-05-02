<?php
// Include the database connection
include('db_connection.php');

// Default values for search and filter
$search_query = '';
$filter = 'handouts'; // Default to 'handouts'

if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
}

if (isset($_GET['category'])) {
    $filter = $_GET['category'];
}

// Prepare query to fetch data based on search and filter, ordered by id DESC
$query = "SELECT * FROM $filter WHERE title LIKE '%$search_query%' ORDER BY id DESC";

// Execute the query
$result = $conn->query($query);
$total_materials = $result->num_rows; // Get the total number of materials
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>materials </title>
    <style>
        :root {
            --primary: #6a1b9a; /* Purple */
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #2d2d2d;
            --white: #fff;
            --light-bg: #fafafa;
            --shadow: rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text);
            line-height: 1.7;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 30px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Search Bar */
        .search-bar {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 6px 20px var(--shadow);
            margin-bottom: 40px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .search-bar form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            width: 100%;
        }

        .search-bar input[type="text"],
        .search-bar select {
            flex: 1;
            min-width: 220px;
            padding: 14px 18px;
            border: 2px solid var(--secondary);
            border-radius: 10px;
            font-size: 16px;
            background: var(--white);
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .search-bar input[type="text"]:focus,
        .search-bar select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(106, 27, 154, 0.2);
        }

        .search-bar button {
            padding: 14px 30px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Materials Count */
        .materials-count {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }

        /* Results Section */
        .results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .no-results {
            text-align: center;
            font-size: 18px;
            color: #888;
            padding: 30px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        /* Card Styling */
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 6px 20px var(--shadow);
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .card-note {
            font-size: 15px;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .card a {
            background: var(--primary);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.3s ease;
        }

        .card a:hover {
            background: var(--primary-dark);
        }

        /* Floating Upload Button */
        .floating-button {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: var(--primary);
            color: var(--white);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 500;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .floating-button:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .search-bar form {
                flex-direction: column;
            }

            .search-bar input[type="text"],
            .search-bar select {
                width: 100%;
                min-width: unset;
            }

            .search-bar button {
                width: 100%;
            }

            .results {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .results {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
            }

            .card-title {
                font-size: 20px;
            }

            .card-note {
                font-size: 14px;
            }

            .floating-button {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 26px;
            }

            .search-bar {
                padding: 20px;
            }

            .search-bar input[type="text"],
            .search-bar select,
            .search-bar button {
                padding: 12px;
                font-size: 14px;
            }

            .materials-count {
                font-size: 16px;
            }

            .card-title {
                font-size: 18px;
            }

            .card-note {
                font-size: 13px;
            }

            .card a {
                padding: 10px 20px;
                font-size: 14px;
            }

            .floating-button {
                bottom: 25px;
                right: 25px;
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Resource Download Hub</h1>
    </div>

    <div class="search-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search resources..." value="<?= htmlspecialchars($search_query) ?>">
            <select name="category">
                <option value="handouts" <?= ($filter == 'handouts') ? 'selected' : '' ?>>Handouts</option>
                <option value="past_questions" <?= ($filter == 'past_questions') ? 'selected' : '' ?>>Past Questions</option>
                <option value="summaries" <?= ($filter == 'summaries') ? 'selected' : '' ?>>Summaries</option>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="materials-count">
        <?= $total_materials ?> material<?= ($total_materials == 1) ? '' : 's' ?> available
    </div>

    <div class="results">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-title"><?= htmlspecialchars($row['title']) ?></div>
                    <div class="card-note"><?= htmlspecialchars($row['note']) ?></div>
                    <a href="<?= htmlspecialchars($row['download_link']) ?>" download>Download</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-results">No results found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Button for Upload -->
<a href="upload_handout.php" class="floating-button">+</a>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
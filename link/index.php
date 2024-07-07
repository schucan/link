<?php
// Configuration
define('CONFIG_FILE', 'config.php');

if (!file_exists(CONFIG_FILE)) {
	function randString($length) {
		return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
	}
	file_put_contents(CONFIG_FILE,"<?php // Configuration file for URL shortener\n");
	file_put_contents(CONFIG_FILE, "define('DB_FILE', '". randString(6) . ".db');\n", FILE_APPEND);
	file_put_contents(CONFIG_FILE, "define('ADMIN_COOKIE_NAME', '". randString(4) . "');\n", FILE_APPEND);
	file_put_contents(CONFIG_FILE, "define('ADMIN_COOKIE_VALUE', '". randString(50) . "');\n", FILE_APPEND);
	$randomPassword = randString(10);
	file_put_contents(CONFIG_FILE, "define('ADMIN_PASSWORD_HASH', '" . password_hash($randomPassword, PASSWORD_DEFAULT) . "');\n", FILE_APPEND);
	
	$loginURL = ($_SERVER['HTTPS'] ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '?login';
	
	echo "<p>A new configuration has been created at " . CONFIG_FILE . " with the password '$randomPassword'. Store this password in a safe place.</p>";
	echo '<p>From now on, you will need this Link to log in and create new short URLs: <a href="' . $loginURL .'">' . $loginURL . ' (click here to log in now!)</a>.</p>';
	exit;
	
} else {
	require CONFIG_FILE;
}

// Initialize database
function init_db() {
    $db = new SQLite3(DB_FILE);
    $db->exec("CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        url TEXT NOT NULL,
        public BOOLEAN DEFAULT 0,
        direct BOOLEAN DEFAULT 1,
        description TEXT,
        creation_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    return $db;
}

$db = init_db();

function render_header() {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-size: 7vmin;
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 20px;
        }
        form {
            margin: 20px auto;
            background-color: #f9f9f9;
        }
        input, textarea, button {
            margin: 1vmin 0;
            padding: 1vmin;
            font-size: 7vmin;
            width: 100%;
			font-family: Arial, sans-serif;
            box-sizing:border-box;
        }
		input[type=checkbox] {
			width: 7vmin;
			height: 7vmin;
			margin-left: 1vmin;
		}
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            padding: 1vmin;
			border-radius:1vmin;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
			font-size:14px;
        }
        th, td {
            padding: 1vmin;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        a {
            text-decoration: none;
            color: #4CAF50;
        }
		.go-button {
			box-shadow: 0px 1px 0px 0px #fff6af;
			background:linear-gradient(to bottom, #ffec64 5%, #ffab23 100%);
			background-color:#ffec64;
			border-radius:6vmin;
			border:1px solid #ffaa22;
			display:inline-block;
			cursor:pointer;
			color:#333333;
			font-family:Arial;
			font-size:5vmin;
			font-weight:bold;
			padding:4vmin 6vmin;
			text-decoration:none;
			text-shadow:0px 1px 0px #ffee66;
			width:auto;
		}
		.go-button:hover {
			background:linear-gradient(to bottom, #ffab23 5%, #ffec64 100%);
			background-color:#ffab23;
		}
		.go-button:active {
			position:relative;
			top:1px;
		}
		.URL {
			color:#aaa;
			font-weight:bold;
		}
		.umleitung {
			position: absolute;
			left: 50%;
			top: 50%;
			-webkit-transform: translate(-50%, -50%);
			transform: translate(-50%, -50%);
		}
        @media (min-width: 600px) {
			body {
				font-size: 16px;
			}
			input, textarea, button {
				margin: 10px 0;
				padding: 10px;
				font-size: 16px;
			}
			input[type=checkbox] {
				width: 16px;
				height: 16px;
				margin-left: 10px;
			}
			
		}
    </style>
</head>
<body>
HTML;
}

function render_footer() {
    return <<<HTML
</body>
</html>
HTML;
}

function logVisit($name,$url) {
	$log_file = 'log-'.date('Y-m') . '.csv';
	if (!file_exists($log_file)) {
		file_put_contents($log_file, "time;name;URL;IP;useragent;referer\n");
	}
	file_put_contents($log_file, date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ";$name;".'"'.$url.'"'.";" . $_SERVER['REMOTE_ADDR'] . ';"' . $_SERVER['HTTP_USER_AGENT'] . '";"' . $_SERVER['HTTP_REFERER'] . '"' . "\n", FILE_APPEND);
}
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login form
    if (isset($_POST['password'])) {
        if (password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
            setcookie(ADMIN_COOKIE_NAME, ADMIN_COOKIE_VALUE, time() + (86400 * 30), "/", "", true, true);
        }
        header('Location: /');
        exit;
    }

    // Add/Edit/Delete link
    if (isset($_COOKIE[ADMIN_COOKIE_NAME]) && $_COOKIE[ADMIN_COOKIE_NAME] === ADMIN_COOKIE_VALUE) {
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['name']);
        $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
        $public = isset($_POST['public']) ? 1 : 0;
        $direct = isset($_POST['direct']) ? 1 : 0;
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

        if (isset($_POST['delete'])) {
            $stmt = $db->prepare("DELETE FROM links WHERE name = :name");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->execute();
        } else if (isset($_POST['update'])) {
            $stmt = $db->prepare("UPDATE links SET url = :url, public = :public, direct = :direct, description = :description WHERE name = :name");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
            $stmt->bindValue(':public', $public, SQLITE3_INTEGER);
            $stmt->bindValue(':direct', $direct, SQLITE3_INTEGER);
            $stmt->bindValue(':description', $description, SQLITE3_TEXT);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO links (name, url, public, direct, description) VALUES (:name, :url, :public, :direct, :description)");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
            $stmt->bindValue(':public', $public, SQLITE3_INTEGER);
            $stmt->bindValue(':direct', $direct, SQLITE3_INTEGER);
            $stmt->bindValue(':description', $description, SQLITE3_TEXT);
            $stmt->execute();
        }
        header('Location: /');
        exit;
    }
}

// Handle link redirection and display
if (isset($_GET['path'])) {
    $name = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['path']);
    $stmt = $db->prepare("SELECT * FROM links WHERE name = :name");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
		$authenticated = (isset($_COOKIE[ADMIN_COOKIE_NAME]) && $_COOKIE[ADMIN_COOKIE_NAME] === ADMIN_COOKIE_VALUE);
		if (!$authenticated &&($result['direct'] || (((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') === ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"))))) {
			// Redirect if direct flag is set
			logVisit($name,$result['url']);
            header('Location: '.$result['url']);
            exit;
        }
        echo render_header();
        if ($authenticated) {
            // Admin view for editing or deleting the link
            echo '<form method="POST">';
            echo '<input type="hidden" name="name" value="'.htmlspecialchars($name).'">';
            echo '<input type="text" name="url" value="'.htmlspecialchars($result['url']).'" required>';
            echo '<label><input type="checkbox" name="public" '.($result['public'] ? 'checked' : '').'> Public</label>';
            echo '<label><input type="checkbox" name="direct" '.($result['direct'] ? 'checked' : '').'> Direct</label>';
            echo '<textarea name="description" placeholder="Description">'.strip_tags($result['description']).'</textarea>';
            echo '<button type="submit" name="update">Update</button>';
            echo '<button type="submit" name="delete">Delete</button>';
            echo '</form>';
            echo '<a href="'.$result['url'].'">Go to URL</a>';
        } else {
            // Display URL and description with a "Go!" button
            echo '<div class="umleitung"><p>'.strip_tags($result['description']).'</p>';
            echo '<p class="URL">'.htmlspecialchars($result['url']).'</p>';
            echo '<a href="/'.htmlspecialchars($name).'"><button class="go-button">Go!</button></a></div>';
        }
        echo render_footer();
        exit;
    } else {
		header('Location: /');
	}
} else {
    echo render_header();

    if (isset($_COOKIE[ADMIN_COOKIE_NAME]) && $_COOKIE[ADMIN_COOKIE_NAME] === ADMIN_COOKIE_VALUE) {
        
        // List all links
        $result = $db->query("SELECT * FROM links");
        $table = '';
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $table .= '<tr>';
            $table .= '<td><a title="Edit or Delete" href="/'.htmlspecialchars($row['name']).'">🖊</a></td>';
            $table .= '<td><a title="Edit or Delete" href="/'.htmlspecialchars($row['name']).'">'.htmlspecialchars($row['name']).'</a></td>';
            $table .= '<td><a title="Visit directly" href="'.$row['url'].'" target="_blank">'.htmlspecialchars($row['url']).'</td>';
            $table .= '<td>'.($row['public'] ? 'Yes' : 'No').'</td>';
            $table .= '<td>'.($row['direct'] ? 'Yes' : 'No').'</td>';
            $table .= '<td>'.strip_tags($row['description']).'</td>';
            $table .= '<td>'.htmlspecialchars($row['creation_date']).'</td>';
            $table .= '</tr>';$empty = false;
        }
		if ($table != '') {
			echo '<h1>Existing Links</h1><table><tr><th></th><th>Name</th><th>URL</th><th>Public</th><th>Direct</th><th>Description</th><th>Creation Date</th></tr>';
			echo "$table</table>";
		}
		// Admin view
        echo '<h1>Add new link</h1><form method="POST">';
        echo '<input type="text" name="name" placeholder="Name" required>';
        echo '<input type="url" name="url" placeholder="URL" required>';
        echo '<label><input type="checkbox" name="public"> Public</label>';
        echo '<label><input type="checkbox" name="direct" checked> Direct</label>';
        echo '<textarea name="description" placeholder="Description"></textarea>';
        echo '<button type="submit">Add Link</button>';
        echo '</form>';
    } else {
        // Public view
        $result = $db->query("SELECT * FROM links WHERE public = 1");
        echo '<table>';
		$empty = true;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo '<tr><td><a href="/'.htmlspecialchars($row['name']).'">'.htmlspecialchars($row['name']).'</a>: '.strip_tags($row['description']).'</td></tr>';
			$empty = false;
        }
		if ($empty) {
			echo "<tr><td>This is a private URL shortening service.</td></tr>";
		}
        echo '</table>';
		if (isset($_GET['login'])) {
			// Login form
			
			echo '<form method="POST">';
			echo '<input type="password" name="password" placeholder="Password" required>';
			echo '<button type="submit">Login</button>';
			echo '</form>';
		}
    }
    echo render_footer();
}
?>

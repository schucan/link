<?php
if (isset($_GET['path'])) {
	// manifest
	if ($_GET['path'] == 'manifest.json') {
		header('Content-Type: application/json');
		echo <<<JSON
{
 "name": "Link",
 "short_name": "Link",
 "scope": "/",
 "start_url": "/?login",
 "display": "standalone",
 "description": "URL Shortener",
 "theme_color": "#4CAF50",
 "background_color": "#ffffff"
}
JSON;
		exit;
	}
	// CSS
	else if ($_GET['path'] == 'style.css') {
		header("Content-type: text/css");
		echo <<<CSS
body {
	font-size: 16px;
	font-family: Arial, sans-serif;
	text-align: center;
	margin: 2vh auto;
	max-width:800px;
	position: relative;
	height: 96vh;
}
form {
	margin: 20px;
}
input, textarea, button {
	width: 100%;
	font-family: Arial, sans-serif;
	box-sizing:border-box;
	margin: 10px 0;
	padding: 10px;
	font-size: 16px;
}
input[type=checkbox] {
	width: 16px;
	height: 16px;
	margin-left: 10px;
}
button {
	background-color: #4CAF50;
	color: white;
	border: none;
	cursor: pointer;
	padding: 20px;
	border-radius:40px;
}
button:hover {
	background-color: #45a049;
}
.tabelle {
	overflow-x: auto;
	margin:20px;
	box-sizing:border-box;
}
.tabelle td {
	position:relative;
}
table {
	width: 100%;
	border-collapse: collapse;
	margin: 20px 0;
	font-size:14px;
}
table.center {
	
}
table:first-child td {
	border-top: 1px solid #ddd;
}
th, td {
	padding: 3px;
	text-align: left;
	border-bottom: 1px solid #ddd;
}
td > a::after {
	content: " ";
	position: absolute;
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
}
td > a:hover::after {
	background-color: #4caf5038;
}
tr {
	position: relative;
}
a {
	text-decoration: none;
	color: #4CAF50;
}
.go-button {
	box-shadow: 0px 1px 0px 0px #fff6af;
	background:linear-gradient(to bottom, #ffec64 5%, #ffab23 100%);
	background-color:#ffec64;
	border-radius:10px;
	border:1px solid #ffaa22;
	display:inline-block;
	cursor:pointer;
	color:#333333;
	font-family:Arial;
	font-size:20px;
	font-weight:bold;
	padding:8px 10px;
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
.center {
	position: absolute;
	left: 50%;
	top: 30%;
	-webkit-transform: translate(-50%, -50%);
	transform: translate(-50%, -50%);
	width: 90vw;
	max-width: 800px;
}
.footer {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	font-size: 10px;
}
.footer p {
	display: inline-block;
	margin: 0;
	background-color: #000;
	padding: 3px 15px 5px;
	color: #fff;
	border-top-right-radius: 40px;
	border-top-left-radius: 40px;
	box-shadow: 0px 2px 5px 0px black, 0px 24px 20px 19px black;
}
.footer::before {
	content: " ";
	background-color: black;
	left: 0;
	right: 0;
	bottom: 0;
	position: absolute;
	height: 6px;
	z-index: -1;
	box-shadow: 0px 0px 7px 0px black;
}
CSS;		
		exit;
	}
	
}
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
	<meta name="apple-mobile-web-app-capable" content="yes" >
    <meta name="mobile-web-app-capable" content="yes">
	<link rel="manifest" href="/manifest.json">
	<meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/style.css" type="text/css" />
</head>
<body>
HTML;
}

function render_footer() {
    return <<<HTML
  <div class="footer"><p>minimal URL shortener - <a href="https://github.com/schucan/link" target="_blank">get it from GitHub</a></p></div>
</body>
</html>
HTML;
}

function logVisit($name,$url) {
	$log_file = 'log-'.date('Y-m') . '.csv';
	if (!file_exists($log_file)) {
		file_put_contents($log_file, "time;name;URL;IP;useragent;referer\n");
	}
	file_put_contents($log_file, 
		date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . 
		";$name;".'"'.$url.'"'.";" . $_SERVER['REMOTE_ADDR'] . ';"' . 
		((isset($_SERVER['HTTP_USER_AGENT']))?($_SERVER['HTTP_USER_AGENT']):('')) . '";"' . 
		((isset($_POST['referer']))?($_POST['referer']):((isset($_SERVER['HTTP_REFERER']))?($_SERVER['HTTP_REFERER']):(''))) . '"' . "\n", 
		FILE_APPEND);
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
        $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['name']));
		$newname = ((isset($_POST['newname']) && ($_POST['newname']!='') && ($_POST['newname']!=$name))?(strtolower($_POST['newname'])):($name));
        $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
        $public = isset($_POST['public']) ? 1 : 0;
        $direct = isset($_POST['direct']) ? 1 : 0;
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

        if (isset($_POST['delete'])) {
            $stmt = $db->prepare("DELETE FROM links WHERE name = :name");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->execute();
        } else if (isset($_POST['update'])) {
            $stmt = $db->prepare("UPDATE links SET name = :newname, url = :url, public = :public, direct = :direct, description = :description WHERE name = :name");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':newname', $newname, SQLITE3_TEXT);
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
    $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_GET['path']));
    $stmt = $db->prepare("SELECT * FROM links WHERE name = :name");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
		$authenticated = (isset($_COOKIE[ADMIN_COOKIE_NAME]) && $_COOKIE[ADMIN_COOKIE_NAME] === ADMIN_COOKIE_VALUE);
		// Jump to destination
		if (isset($_POST['go']) || (!$authenticated &&($result['direct'] || (((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') === ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")))))) {
			// Redirect if direct flag is set
			logVisit($name,$result['url']);
            header('Location: '.$result['url']);
            exit;
        }
        echo render_header();
        if ($authenticated) {
            echo '<a href="/">Go to full list of links</a>';
            // Admin view for editing or deleting the link
            echo '<form method="POST">';
            echo '<input type="hidden" name="name" value="'.htmlspecialchars($name).'">';
            echo '<input type="text" name="newname" placeholder="New Name" value="'.htmlspecialchars($name).'" required>';
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
            echo '<div class="center"><p>'.strip_tags($result['description']).'</p>';
            echo '<p class="URL">'.htmlspecialchars($result['url']).'</p>';
            echo '<form method="POST"><input type="hidden" name="go" value="'.htmlspecialchars($name).'"><input type="hidden" name="referer" value="'.htmlspecialchars((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')).'"><input type="submit" value="Go!" class="go-button"></form></div>';
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
			echo '<h1>Existing Links</h1><div class="tabelle"><table><tr><th></th><th>Name</th><th>URL</th><th>Public</th><th>Direct</th><th>Description</th><th>Creation Date</th></tr>';
			echo "$table</table></div>";
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
		// Login screen
		if (isset($_GET['login'])) {
			// Login form
			
			echo '<form method="POST">';
			echo '<input type="password" name="password" placeholder="Password" required>';
			echo '<button type="submit">Login</button>';
			echo '</form>';
		} else {
			// Public view
			$result = $db->query("SELECT * FROM links WHERE public = 1");
			echo '<table class="center">';
			$empty = true;
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				echo '<tr><td><a href="/'.htmlspecialchars($row['name']).'">'.htmlspecialchars($row['name']).'</a>: '.strip_tags($row['description']).'</td></tr>';
				$empty = false;
			}
			if ($empty) {
				echo "<tr><td>This is a private URL shortening service.</td></tr>";
			}
			echo '</table>';
		}
    }
    echo render_footer();
}
?>

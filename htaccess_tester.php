<?php
/**
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/**
 * @author Daniel Tome <danieltomefer@gmail.com>
 */?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bolt htaccess tester.</title>
    <link rel="stylesheet" src="//normalize-css.googlecode.com/svn/trunk/normalize.css" />
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body id="home">
<?php

#htaccess tester, version 1.0

echo "<h1>Bolt Apache <tt>.htaccess</tt> tester.</h1>";

if (strpos($_SERVER['REQUEST_URI'], 'htaccess_tester.php') === false) {

    echo "<p><tt>mod_rewrite</tt> is working! You used the path <tt>" . $_SERVER['REQUEST_URI'] . "</tt> to request this page.</p>";

} elseif (is_readable(__DIR__.'/.htaccess') ) {

    echo "<p>The file .htaccess exists and is readable to the webserver. These are its contents: </p>\n<textarea style='width: 700px; height: 200px;'>";
    echo file_get_contents(__DIR__.'/.htaccess');
    echo "</textarea>";

} else {

    echo "<p><strong>Error:</strong> The file .htaccess does not exist or it is not readable to the webserver. <br><br>Retieve a new version of the file here, and place it in your webroot. Make sure it is readable to the webserver.</p>";
    die();

}

// echo "<h1>PHPinfo</h1>";

// echo "<p>Below you'll find the specifics of your PHP installation, for debugging purposes.</p>";

// phpinfo();
?>
</body>
</html>

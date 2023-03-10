<?php
session_start();
$id = session_id();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css?<?php echo time(); ?>">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css"> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js" defer></script> -->
    <title>News</title>
</head>

<body>
    <script defer>
        function logOut() {
            document.getElementById("logOutForm").submit();
        }
    </script>

    <header>
        <nav>
            <ul id="nav-items">
                <li class="nav-item pointer">
                    <h1 id="logo" onclick="window.location.replace('./index.php?page=1')">News Site</h1>
                </li>
                <li class="nav-item"><a href="https://science.orf.at/" target="_blank">science.orf.at</a></li>
                <li class="nav-item"><a href="https://sport.orf.at/" target="_blank">sport.orf.at</a></li>
                <li class="nav-item"><a href="https://oesterreich.orf.at/" target="_blank">oesterreich.orf.at</a></li>
                <li class="nav-item pointer">
                    <?php
                    if (isset($_SESSION["loggedIn"])) {
                        if ($_SESSION["loggedIn"]) {
                            if (isset($_POST['username'])) {
                                echo $_POST['username'];
                            }
                        }
                    }
                    ?>
                    <span class="material-symbols-outlined" onclick="logOut()">
                        <?php
                        if (isset($_SESSION["loggedIn"])) {
                            if ($_SESSION["loggedIn"]) {
                                echo 'logout';
                            } else {
                                echo 'login';
                            }
                        } else {
                            echo 'login';
                        }
                        ?>
                    </span>
                    <form id="logOutForm" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>" method="POST">
                        <input type="text" name="auth" value="logOut">
                    </form>
                </li>
            </ul>
        </nav>
    </header>

    <?php
    /* PASSWORDS */
    // Jonesis password_hash("supersecurekey", PASSWORD_DEFAULT);
    // Stephanowitsch password_hash("uuundRuhig", PASSWORD_DEFAULT);
    // Christopher password_hash("AEG!", PASSWORD_DEFAULT);

    $input = array();
    $error = array();

    generateContent();
    function generateContent()
    {
        if (!isset($_GET["page"])) {
            $_GET["page"] = 1;
        }

        if (isset($_SESSION["loggedIn"]) && $_SESSION["loggedIn"]) {
            unlockNewsContent();
        } else if (isset($_SESSION["loggedIn"]) && !$_SESSION["loggedIn"]) {
            showLoginForm("");
            echo "<script>
            document.getElementById('news-articles') ? document.getElementById('news-articles').parentElement.removeChild(
                document.getElementById('news-articles')) : '';
            if (document.getElementsByClassName('pagination')[0]) {
                let elements = document.getElementsByClassName('pagination');
                [...elements].forEach(element => {
                    element ? element.parentElement.removeChild(
                        element) : '';
                });
            }
            </script>";
            // echo "<script> location.reload(); </script>";
        } else {
            showLoginForm("");
        }
    }

    /* Load Content On LogIn */
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['auth']) && $_POST['auth'] != null) {
            if ($_POST['auth'] == "logIn") {
                if (isset($_POST['username']) && $_POST['username'] != "") {
                    $input["username"] = $_POST['username'];
                    if (isset($_POST['password']) && $_POST['password'] != "") {
                        $input["password"] = $_POST['password'];

                        if (!isset($_SESSION["loggedIn"])) {
                            $_SESSION["loggedIn"] = false;
                        }
                        if (!$_SESSION["loggedIn"]) {
                            $username = $_POST['username'];

                            $subsString = file_get_contents("./subscribers.json");
                            $subsObj = json_decode($subsString, true);

                            logIn($subsObj, $username);
                        }
                    } else {
                        showLoginForm("password field is empty");
                    }
                } else {
                    showLoginForm("username field is empty");
                }
            } else {
                $_SESSION["loggedIn"] = false;
                session_destroy(); // $_SESSION = array();
                generateContent();
            }
        }
    }

    function showLoginForm($reason)
    {
        echo "<style>";
        $inputFields = [
            "username",
            "password"
        ];

        for ($i = 0; $i < count($inputFields); $i++) {
            if (isset($input[$inputFields[$i]])) {
                $inputFiled = $inputFields[$i];

                echo " #$inputFiled {border-color: rgba(0, 128, 0, 0.3);} ";
            } else {
                $inputFiled = $inputFields[$i];

                echo " #$inputFiled  {border-color: rgba(255, 0, 0, 0.3);} ";
            }
        }
        echo "</style>";

        echo '<main id="logInForm">';
        echo '<form name="" action="' . htmlentities($_SERVER['PHP_SELF']) . '" method="POST" enctype="text/html">';
        echo '<h1>SignIn</h1>';
        echo '<label for="username">Benutzername:';
        echo '<input type="text" name="username" value="' . (isset($input["username"]) ? $input["username"] : 'Jonesis') . '" placeholder="MaxMuster05" minlength="1" maxlength="50" /></label>';
        echo '<label for="password">Kennwort:';
        echo '<input type="password" name="password" value="' . (isset($input["password"]) ? $input["password"] : 'supersecurekey') . '" placeholder="420Max69?&$187" minlength="1" maxlength="50" /></label>';
        echo '<button type="submit" name="auth" value="logIn" />LogIn</button>';
        echo "<p><em> $reason </em></p>";
        echo '</form>';
        echo '</main>';
    }

    /* LogIn Logic */
    function logIn($subsObj, $username)
    {
        $userInSubscriberList = checkIfUserExists($subsObj, $username);
        $exists = false;
        $index = 0;
        if (is_array($userInSubscriberList)) {
            $exists = $userInSubscriberList["exists"];
            $index = $userInSubscriberList["index"];
        }

        if ($exists) {
            $password = $subsObj[$index]["password"];

            if (password_verify($_POST['password'], $password)) {
                $_SESSION["loggedIn"] = true;
                echo "<script> location.reload(); </script>";
            } else {
                showLoginForm("password for " . $username .  " is invalid");
            }
        } else {
            showLoginForm($username . " doesn't appear in the subscriber list");
        }
    }

    function checkIfUserExists($subsObj, $username)
    {
        for ($i = 0; $i < count($subsObj); $i++) {
            if ($subsObj[$i]["username"] == $username) {
                return ["exists" => true, "index" => $i];
            }
        }
        return false;
    }

    /* Subscriber Content Logic */
    function unlockNewsContent()
    {
        $newsPerPage = 3;
        $currentPage = 1;
        $maxPage = 4;

        if (isset($_SESSION["page"])) {
            if ($_SESSION["page"] >= 1 && $_SESSION["page"] <= $maxPage) {
                if (isset($_GET["page"])) {
                    if ($_GET["page"] >= 1 && $_GET["page"] <= $maxPage) {
                        $_SESSION["page"] = $_GET["page"];
                    } else if ($_GET["page"] > $maxPage) {
                        $_SESSION["page"] = $maxPage;
                    } else {
                        $_SESSION["page"] = $currentPage;
                    }
                }
                $currentPage = $_SESSION["page"];
            } else {
                $currentPage = setPageIfErrOcc($currentPage);
            }
        } else {
            $currentPage = setPageIfErrOcc($currentPage);
        }

        $startNewsIndex = $newsPerPage * ($currentPage - 1);

        $newsStr = file_get_contents("./news.json");
        $newsObj = json_decode($newsStr, true);
        $newsOfPage = array_slice($newsObj, $startNewsIndex, $startNewsIndex + $newsPerPage);

        $newsHTML = "";
        $newsHTML = $newsHTML . '<main id="news-articles">';
        /* <!-- [<category>] url, baseUrl, titleImage, date, headline, tag, shortNewsTease, [news] => title, body, [-image-] => url, -text-, -creator- --> */
        for ($i = 0; $i < $newsPerPage; $i++) { // count($allNews)
            $news = $newsOfPage[$i];

            $newsHTML = $newsHTML . "<article>";

            $newsHTML = $newsHTML . "<div class='article-head'>";
            $newsHTML = $newsHTML . "<h1>" .  $news['headline'] . "</h1>";
            $newsHTML = $newsHTML . "<p class='article-publish-date'><em>" .  $news['date'] . "</em></p>";
            $newsHTML = $newsHTML . "</div>";

            $newsHTML = $newsHTML . "<div class='article-body'>";
            $newsHTML = $newsHTML . "<span class='tag'><strong>" .  $news['tag'] . "</strong></span>";
            $newsHTML = $newsHTML . "<h4>" .  $news['shortNewsTease'] . "</h4>";
            foreach ($news['news'] as $body) {
                $newsHTML = $newsHTML . "<h3>" . $body['title'] . "</h3>";
                $newsHTML = $newsHTML . "<p>" . $body['body'] . "</body>";
            }
            $newsHTML = $newsHTML . "</div>";

            $newsHTML = $newsHTML . "<div class='url'><span class='material-symbols-outlined'>link</span> <a href='" . $news['url'] . "' target='_blank'>" . $news['url'] . "</a></div>";

            $newsHTML = $newsHTML . "</article>";
        }
        $newsHTML = $newsHTML . '</main>';

        echo $newsHTML;

        loadPagination($currentPage, $maxPage);
    }

    function setNextPage($currentPage, $maxPage, $action)
    {
        if ($action == 'prev') {
            $nextPage = 4;
            $pageResult = $currentPage - 1;
            if ($pageResult >= 1) {
                $_SESSION["page"] = $pageResult;
            } else {
                $_SESSION["page"] = $nextPage;
            }
        } else if ($action == 'next') {
            $nextPage = 1;
            $pageResult = $currentPage + 1;
            if ($pageResult <= $maxPage) {
                $_SESSION["page"] = $pageResult;
            } else {
                $_SESSION["page"] = $nextPage;
            }
        }
        return $_SESSION["page"];
    }

    function setPageIfErrOcc($currentPage)
    {
        if (isset($_GET["page"])) {
            $_SESSION["page"] = $_GET["page"];
            $currentPage = $_SESSION["page"];
            return $currentPage;
        } else {
            $_SESSION["page"] = $currentPage;
        }
        return $currentPage;
    }

    function loadPagination($currentPage, $maxPage)
    {
        $currentPage = intval($currentPage);

        echo "<footer>
            <div class='pagination'>
            <a href='./previous.php?page=" . setNextPage($currentPage, $maxPage, 'prev') . "'>&laquo;</a>
            <a href='./index.php?page=1' class='" . checkIfActive($currentPage, 1) .  "'>1</a>
            <a href='./index.php?page=2' class='" . checkIfActive($currentPage, 2) .  "'>2</a>
            <a href='./index.php?page=3' class='" . checkIfActive($currentPage, 3) .  "'>3</a>
            <a href='./index.php?page=4' class='" . checkIfActive($currentPage, 4) .  "'>4</a>
            <a href='./next.php?page=" . setNextPage($currentPage, $maxPage, 'next') . "'>&raquo;</a>
            </div>
            </footer>";
    }

    function checkIfActive($currentPage, $index)
    {
        if ($currentPage == $index) {
            return 'active';
        }
        return "";
    }

    /* Helper Functions: */
    function vardump($debug_object)
    {
        echo "<pre>";
        var_dump($debug_object);
        echo "</pre>";
    }
    ?>
</body>

</html>
<?php
	include_once "html/header.php";
	?>

<div id="main">

<?php
// check settings

$mysql_host = 'localhost';
$mysql_user = 'root';
$mysql_pwd  = 'mysql';
$mysql_db   = 'selenium';

$testname = $_POST['testName'];

if ($_POST['save'] == 'yes') {
    // save to db
    $host = $_POST['host'];
    if (!$host) {
    	header('Location: index.php');
    	exit;
    }

    $searchname = $_POST['searchname'];
    $searchemail = $_POST['searchemail'];
    $spousename1 = $_POST['spousename1'];
    $spousename2 = $_POST['spousename2'];

    $browser = $_POST['browser'];
    $sleep = $_POST['sleep'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $domain = $_POST['domain'];

    if (substr($host, 0, 7)!="http://") {
        $host = "http://" . $host . $domain;
    }

    $time = time();

	$query = "INSERT INTO `test`(`host`,`searchname`,`searchemail`,`spousename1`,`spousename2`,`time`,`browser`,`sleep`,`username`,`password`,`testname`) VALUES ('$host','$searchname','$searchemail','$spousename1','$spousename2','$time','$browser','$sleep','$username','$password','$testname');";
	
	$link = mysql_connect($mysql_host, $mysql_user, $mysql_pwd);
	mysql_select_db($mysql_db);
	mysql_query($query, $link);
	mysql_close($link);
}

if (!$testname) {
  	header('Location: index.php');
  	exit;
}

// Run test

echo "<a href=\"index.php\"><h3>Start over</h3></a><br />";

echo "Starting: <pre>"."c:\wamp\phpunit ".$testname."</pre>";
echo "<div class=\"result\"><pre style=\"width:700px !important;\">";
system("c:\wamp\phpunit ".$testname);
echo "</pre></div>";
?>

<br/>
<a href="index.php"><h3>Start over</h3></a>


</div> <!-- main -->




<?php
	include_once "html/footer.php";
	?>
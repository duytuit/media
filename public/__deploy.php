<?php

if (!isset($_GET['_token']) || $_GET['_token'] != 'cdn.duytuit.media.vn') {
    die('You cannot access!');
}
// The commands
$commands = array(
    'whoami 2>&1',
    'cd '.dirname(__DIR__).' 2>&1',
    'git pull 2>&1',
    'git status 2>&1',
);
// Run the commands for output
$output = '';
foreach ($commands AS $command) {
    // Run it
    $tmp = shell_exec($command);
    // Output
    $output .= "<span style=\"color: #6BE234;\">\$</span> <span style=\"color: #729FCF;\">{$command}\n</span>";
    $output .= htmlentities(trim($tmp)) . "\n";
}
?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>GIT DEPLOYMENT: Ver1</title>
</head>
<body style="background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 0 10px;">
<pre>
<?php echo $output; ?>
</pre>
</body>
</html>

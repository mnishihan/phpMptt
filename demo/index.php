<?php
$files = scandir(dirname(__FILE__));
$demo_files = array();
foreach ($files as $file) {
    if (!is_dir($file) && !in_array($file, array(".", "..", basename(__FILE__)))) {
        $demo_files[] = $file;
    }
}

if (isset($_REQUEST['demo']) && in_array($_REQUEST['demo'], $demo_files) && isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'source') {
    include_once 'thirdparty/geshi/geshi.php';
    $demo_source = file_get_contents($_REQUEST['demo']);
    $geshi = new GeSHi($demo_source, 'php');
    $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
    $geshi->set_line_style('background: #fcfcfc;', 'background: #f0f0f0;');
    
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>phpMptt Demo List</title>
        <style type="text/css">
            #demo-source {
                padding: 5px;
                background-color: #ccccff;
            }

        </style>
    </head>
    <body>
<?php if (isset($_REQUEST['demo']) && in_array($_REQUEST['demo'], $demo_files)): ?>
            
            <div id="demo">
                <iframe id="demo-output" src="<?php echo str_replace(basename(__FILE__), $_REQUEST['demo'], $_SERVER['PHP_SELF']); ?>" width="100%" height="400">
                <p>iframe is not supported</p>
                </iframe>
    <?php if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'source'): ?>
                    <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?demo=<?php echo $_REQUEST['demo']; ?>">Hide source</a></p>
                    <div id="demo-source"><?php echo $geshi->parse_code(); ?></div>
                <?php else: ?>
                    <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?demo=<?php echo $_REQUEST['demo']; ?>&mode=source">Show source</a></p>
    <?php endif; ?>
                <p><a href='<?php echo $_SERVER['PHP_SELF']; ?>'>Back to Demo List</a></p>
            </div>
        <?php else: ?>
            <?php
            if (count($demo_files) == 0):
                ?>
                <p>There are no demo available currently.</p>
                <?php
            else:
                ?>
                <p>phpMptt Demo</p>
                <ul>
                    <?php foreach ($demo_files as $demo_file): ?>
                        <li><a href="<?php echo $_SERVER['PHP_SELF'] ?>?demo=<?php echo $demo_file; ?>"><?php echo ucwords(str_replace("_", " ", basename($demo_file, '.php'))); ?></a></li>
                <?php endforeach; ?>
                </ul>
            <?php
            endif;
            ?>
<?php endif; ?>
    </body>
</html>
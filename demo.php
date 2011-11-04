<?php
require_once 'phpMptt.php';

$helper = new MPTT_Helper(array("table_name" => "categories"));

$all_rows = array(
    1 => array("id" => 1, "title" => "Food", "left_value" => 1, "right_value" => 18),
    2 => array("id" => 2, "title" => "Fruit", "left_value" => 2, "right_value" => 11),
    3 => array("id" => 3, "title" => "Red", "left_value" => 3, "right_value" => 6),
    5 => array("id" => 5, "title" => "Cherry", "left_value" => 4, "right_value" => 5),
    4 => array("id" => 4, "title" => "Yellow", "left_value" => 7, "right_value" => 10),
    6 => array("id" => 6, "title" => "Banana", "left_value" => 8, "right_value" => 9),
    9 => array("id" => 9, "title" => "Meat", "left_value" => 12, "right_value" => 17),
    8 => array("id" => 8, "title" => "Beef", "left_value" => 13, "right_value" => 14),
    7 => array("id" => 7, "title" => "Pork", "left_value" => 15, "right_value" => 16),
);

$cat_id = !empty($_REQUEST["cat_id"]) ? $_REQUEST["cat_id"] : false;

if ($cat_id && isset($all_rows[$cat_id])) {
    $root_row = $all_rows[$cat_id];
    $data_rows = array($root_row);
    foreach ($all_rows as $row) {
        if ($row["left_value"] > $root_row["left_value"] && $row["left_value"] < $root_row["right_value"]) {
            $data_rows[] = $row;
        }
    }
} else {
    $data_rows = $all_rows;
}

$helper->build_tree($data_rows);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>phpMptt Demo</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style type="text/css">
            li.root {
                font-weight: bold;
            }

            li.child {
                font-weight: normal;
                font-style: italic;

            }

        </style>
    </head>
    <body>
        <?php echo $helper->render_tree(MPTT_Renderer::get_instance(), TRUE); ?>
        <?php echo $helper->render_tree(MPTT_Renderer::get_instance("ol"), FALSE); ?>
    </body>
</html>
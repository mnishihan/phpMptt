<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class MPTT_Node {

    private $_id_key;
    private $_title_key;
    private $_left_value_key;
    private $_right_value_key;
    private $_data_row = array();
    private $_children = array();
    private $_parent = NULL;

    public function __construct($data_row, $id_key, $title_key, $left_value_key, $right_value_key) {
        $data_row = is_object($data_row) ? get_object_vars($data_row) : $data_row;
        $this->_data_row = $data_row;
        $this->_id_key = $id_key;
        $this->_title_key = $title_key;
        $this->_left_value_key = $left_value_key;
        $this->_right_value_key = $right_value_key;
    }

    public function __get($name) {
        if (strpos($name, "_") !== 0 && isset($this->$name)) {
            return $this->$name;
        }

        switch ($name) {
            case 'isRoot':
                return is_null($this->_parent);
            case 'parent':
                return $this->_parent;
            case 'children':
                return $this->_children;
            case 'data':
                return $this->_data_row;

            case 'nodeId':
                $name = $this->_id_key;
                break;
            case 'nodeTitle':
                $name = $this->_title_key;
                break;
            case 'leftValue':
                $name = $this->_left_value_key;
                break;
            case 'rightValue':
                $name = $this->_right_value_key;
                break;

            default:
                break;
        }

        if (is_array($this->_data_row) && isset($this->_data_row[$name])) {
            return $this->_data_row[$name];
        }
        trigger_error("undefined property $name", E_USER_NOTICE);
    }

    public function __set($name, $val) {
        if (strpos($name, "_") !== 0 && isset($this->$name)) {
            $this->$name = $val;
            return;
        }

        switch ($name) {
            case 'isRoot':
            case 'parent':
            case 'children':
                trigger_error("$name is a read only property", E_USER_ERROR);
            case 'nodeId':
                $name = $this->_id_key;
                break;
            case 'nodeTitle':
                $name = $this->_title_key;
                break;
            case 'leftValue':
                $name = $this->_left_value_key;
                break;
            case 'rightValue':
                $name = $this->_right_value_key;
                break;
            default:
                break;
        }

        $this->_data_row[$name] = $val;
    }
    
    public function add_child(MPTT_Node $child) {
        $this->_children[$child->nodeId] = $child;
        $child->_parent = $this;
    }

}

class MPTT_Renderer {

    private function __construct($list_tag="ul") {
        $this->list_tag = $list_tag;
    }

    private $list_tag = "ul";
    private static $_instances = array();

    public static function get_instance($list_tag="ul") {
        if (!isset(self::$_instances[$list_tag])) {
            self::$_instances[$list_tag] = new MPTT_Renderer($list_tag);
        }
        return self::$_instances[$list_tag];
    }

    public function start() {
        return "<{$this->list_tag} class='mptt-tree'>\n";
    }

    public function pre_render_children($node) {
        return "<{$this->list_tag} class='mptt-children'>\n";
    }

    public function pre_render_node($node) {
        $css_class = $node->isRoot ? "root" : "child";
        return "<li id='mptt-node-{$node->nodeId}' class='mptt-node $css_class' title='" . htmlentities($node->nodeTitle, ENT_QUOTES) . "'>\n";
    }

    public function render_node($node) {
        $str = "<a href='{$_SERVER['PHP_SELF']}?cat_id={$node->nodeId}' title='" . htmlentities($node->nodeTitle, ENT_QUOTES) . "'>";
        $str .= htmlentities($node->nodeTitle);
        $str .= "</a>\n";
        return $str;
    }

    public function post_render_node($node) {
        return "</li>\n";
    }

    public function post_render_children($node) {
        return "</{$this->list_tag}>\n";
    }

    public function end() {
        return "</{$this->list_tag}>\n";
    }

}

/**
 * Description of MPTT_Helper
 *
 * @author Shihan
 */
class MPTT_Helper {

    private $table_name = null;
    private $id_column = "id";
    private $title_column = "title";
    private $left_value_column = "left_value";
    private $right_value_column = "right_value";

    /**
     * Root node of the tree based on the data rows provided to @see BuildTree method
     * @var MPTT_Node 
     */
    private $root_node = null;
    public $renderer = null;

    public function __construct() {
        $args = func_get_args();
        if (empty($args)) {
            trigger_error("Table name must be specified", E_USER_ERROR);
        }

        if (is_array($args[0])) {
            $args = $args[0];
        }

        if (empty($args)) {
            trigger_error("Table name must be specified", E_USER_ERROR);
        }

        $params = array(
            "table_name",
            "id_column",
            "title_column",
            "left_value_column",
            "right_value_column"
        );

        $arg_count = count($args);
        for ($arg_no = 0; $arg_no < $arg_count; $arg_no++) {
            $param_name = $params[$arg_no];
            $this->$param_name = isset($args[$param_name]) ? $args[$param_name] : $args[$arg_no];
        }
    }

    public function find_children_query($node_or_left_value=FALSE, $right_value=FALSE) {
        if (is_object($node_or_left_value) && is_a($node_or_left_value, 'MPTT_Node')) {
            return $this->find_children_query($node_or_left_value->leftValue, $node_or_left_value->rightValue);
        }
        else {
            $node_or_left_value = intval($node_or_left_value);
            $right_value = intval($right_value);
            
            if(!($node_or_left_value>0 && $right_value> 0)){
                return FALSE;
            }
        }

        $sql = "SELECT * from {$this->table_name}";
        $where = "";
        if ($node_or_left_value && $right_value) {
            $where = "{$this->left_value_column} BETWEEN $node_or_left_value AND $right_value";
        }

        return "$sql $where ORDER BY {$this->left_value_column}";
    }

    public function find_ancestors_query($node_or_left_value, $right_value=FALSE) {
        if (is_object($node_or_left_value) && is_a($node_or_left_value, 'MPTT_Node')) {
            return $this->find_ancestors_query($node_or_left_value->leftValue, $node_or_left_value->rightValue);
        }
        else {
            $node_or_left_value = intval($node_or_left_value);
            $right_value = intval($right_value);
            
            if(!($node_or_left_value>0 && $right_value> 0)){
                return FALSE;
            }
        }

        if ($node_or_left_value && $right_value) {
            return "SELECT * from {$this->table_name} WHERE {$this->left_value_column} < $node_or_left_value AND {$this->right_value_column}> $right_value";
        }
        return false;
    }

    public function insert_child_pre_queries($parent_node_or_right_value, &$child_node_or_data) {

        if (is_object($parent_node_or_right_value) && is_a('MPTT_Node')) {
            $parent_node_or_right_value = $parent_node_or_right_value->rightValue;
        }
        else {
            $parent_node_or_right_value = intval($parent_node_or_right_value);
            
            if($parent_node_or_right_value<=0){
                return FALSE;
            }
        }

        $queries = array();
        $queries[] = "UPDATE {$this->table_name} SET {$this->right_value_column}={$this->right_value_column}+2 WHERE {$this->right_value_column}>" . ($parent_node_or_right_value - 1);
        $queries[] = "UPDATE {$this->table_name} SET {$this->left_value_column}={$this->left_value_column}+2 WHERE {$this->left_value_column}>" . ($parent_node_or_right_value - 1);

        if (is_object($child_node_or_data) && is_a($child_node_or_data, "MPPT_Node")) {
            $child_node_or_data->leftValue = $parent_node_or_right_value;
            $child_node_or_data->rightValue = $parent_node_or_right_value + 1;
            return $queries;
        }

        if (!is_array($child_node_or_data)) {
            $child_node_or_data = array();
        }

        $child_node_or_data[$this->left_value_column] = $parent_node_or_right_value;
        $child_node_or_data[$this->right_value_column] = $parent_node_or_right_value + 1;

        return $queries;
    }

    public function insert_after_pre_queries($target_node_or_right_value, &$node_or_data) {

        if (is_object($target_node_or_right_value) && is_a('MPTT_Node')) {
            $target_node_or_right_value = $target_node_or_right_value->rightValue;
        }
        else {
            $target_node_or_right_value = intval($target_node_or_right_value);
            if($target_node_or_right_value<=0){
                return FALSE;
            }
        }

        $queries = array();
        $queries[] = "UPDATE {$this->table_name} SET {$this->right_value_column}={$this->right_value_column}+2 WHERE {$this->right_value_column}>$target_node_or_right_value";
        $queries[] = "UPDATE {$this->table_name} SET {$this->left_value_column}={$this->left_value_column}+2 WHERE {$this->left_value_column}>$target_node_or_right_value";

        if (is_object($node_or_data) && is_a($node_or_data, "MPPT_Node")) {
            $node_or_data->leftValue = $target_node_or_right_value + 1;
            $node_or_data->rightValue = $target_node_or_right_value + 2;
            return $queries;
        }

        if (!is_array($node_or_data)) {
            $node_or_data = array();
        }

        $node_or_data[$this->left_value_column] = $target_node_or_right_value + 1;
        $node_or_data[$this->right_value_column] = $target_node_or_right_value + 2;

        return $queries;
    }

    public function delete_queries($node_or_left_value, $right_value=FALSE) {
        if (is_object($node_or_left_value) && is_a($node_or_left_value, 'MPTT_Node')) {
            return $this->find_ancestors_query($node_or_left_value->leftValue, $node_or_left_value->rightValue);
        }
        else {
            $node_or_left_value = intval($node_or_left_value);
            $right_value = intval($right_value);
            
            if(!($node_or_left_value>0 && $right_value> 0)){
                return FALSE;
            }
        }

        $queries = array();
        $num_decendants = $this->number_of_descendents($node_or_left_value, $right_value);
        $delta = ($num_decendants + 1) * 2;
        $queries[] = "DELETE FROM {$this->table_name} WHERE {$this->left_value_column} BETWEEN $node_or_left_value AND $right_value";
        $queries[] = "UPDATE {$this->table_name} SET {$this->right_value_column}={$this->right_value_column}-$delta, {$this->left_value_column}={$this->left_value_column}-$delta WHERE {$this->right_value_column}>$right_value";
        return $queries;
    }
    
    
    public function number_of_descendents($node_or_left_value, $right_value=FALSE) {
        if (is_object($node_or_left_value) && is_a($node_or_left_value, 'MPTT_Node')) {
            return $this->number_of_descendents($node_or_left_value->leftValue, $node_or_left_value->rightValue);
        }
        return floor((intval($right_value) - intval($left_value) - 1) / 2);
    }
    
    private static $_node_cache = array();
    
    public function create_node_from_data($data_row) {
        $data_row = is_object($data_row) ? get_object_vars($data_row) : $data_row;
        if(!isset(self::$_node_cache[$data_row[$this->id_column]])){
            self::$_node_cache[$data_row[$this->id_column]] = new MPTT_Node($data_row, $this->id_column, $this->title_column, $this->left_value_column, $this->right_value_column);
        }
        return self::$_node_cache[$data_row[$this->id_column]];
    }

    public function build_tree($data_rows, $use_cache=true) {

        if (empty($data_rows)) {
            return ($this->root_node = NULL);
        }

        if ($use_cache && !is_null($this->root_node)) {
            return $this->root_node;
        }

        $data_row = array_shift($data_rows);

        $this->root_node = $this->create_node_from_data($data_row);
        $stack = array($this->root_node);

        foreach ($data_rows as $data_row) {
            $node = $this->create_node_from_data($data_row);
            if (count($stack) > 0) {
                while (count($stack) > 0 && $stack[count($stack) - 1]->rightValue < $node->rightValue) {
                    array_pop($stack);
                }
            }
            if (count($stack) > 0) {
                $stack[count($stack) - 1]->add_child($node);
                array_push($stack, $node);
            }
        }
        return $this->root_node;
    }

    /**
     *
     * @param array(callable) $callbacks
     * @param MPTT_Node $node
     * @param bool $is_root 
     */
    private $_render_start_node = NULL;

    public function render_tree($renderer=FALSE, $skip_root=FALSE, $node=NULL) {
        if (empty($renderer)) {
            if (!empty($this->renderer)) {
                $renderer = $this->renderer;
            }
            else {
                trigger_error("renderer not provided.", E_USER_ERRORloca);
            }
        }
        if (is_string($renderer) && !class_exists($renderer)) {
            trigger_error("invalid renderer provided.", E_USER_ERROR);
        }

        if (!is_callable(array($renderer, 'render_node'))) {
            trigger_error('invalid renderer instance provided. it must implement render_node($node,$is_root) method', E_USER_ERROR);
        }

        $start = is_callable(array($renderer, 'start')) ? array($renderer, 'start') : FALSE;

        $pre_render_node = is_callable(array($renderer, 'pre_render_node')) ? array($renderer, 'pre_render_node') : FALSE;
        $render_node = array($renderer, 'render_node');
        $pre_render_children = is_callable(array($renderer, 'pre_render_children')) ? array($renderer, 'pre_render_children') : FALSE;
        $post_render_children = is_callable(array($renderer, 'post_render_children')) ? array($renderer, 'post_render_children') : FALSE;
        $post_render_node = is_callable(array($renderer, 'post_render_node')) ? array($renderer, 'post_render_node') : FALSE;

        $end = is_callable(array($renderer, 'end')) ? array($renderer, 'end') : FALSE;

        $rendered_string = "";
        if (is_null($node)) {
            $node = $this->root_node;
        }

        if (is_null($node)) {
            return FALSE;
        }

        if (is_null($this->_render_start_node) && $start) {
            $this->_render_start_node = $node;
            $rendered_string .= call_user_func($start);
        }

        if (!$node->isRoot || !$skip_root) {
            $pre_render_node && ($rendered_string .= call_user_func($pre_render_node, $node));
            $rendered_string .= call_user_func($render_node, $node);
        }

        if (count($node->children) > 0) {
            (($node->isRoot && !$skip_root) || ($this->_render_start_node !== $node)) && $pre_render_children && ($rendered_string .= call_user_func($pre_render_children, $node));
            foreach ($node->children as $child) {
                $rendered_string .= $this->render_tree($renderer, $skip_root, $child);
            }
            (($node->isRoot && !$skip_root) || ($this->_render_start_node !== $node)) && $post_render_children && ($rendered_string .= call_user_func($post_render_children, $node));
        }

        if (!$node->isRoot || !$skip_root) {
            $post_render_node && ($rendered_string .= call_user_func($post_render_node, $node));
        }


        if (!is_null($this->_render_start_node) && $end && $this->_render_start_node === $node) {
            $this->_render_start_node = NULL;
            $rendered_string .= call_user_func($end);
        }

        return $rendered_string;
    }
}

<?php

/**
 * https://github.com/PHPBootstrapTableEdit/PHPBootstrapTableEdit
 *
 * @license MIT
 * @version 0.0.7
 *
 */

namespace PHPBootstrapTableEdit;

class PHPBootstrapTableEdit
{

    private $dbh = null; // pdo database handle, passed in via _construct()
    public $charset = 'UTF-8';

    public $table_name = ''; // database table
    public $identity_name = ''; // the auto increment id field in the table, typically 'id'

    public $index_sql = ''; // sql query to define the opening html table listing all records
    public $index_sql_param = []; // named parameters for index_sql

    public $add_sql = ''; // sql query to define what fields to display on the add/insert page
    public $add_sql_param = []; // named parameters for add_sql:  $o->add_sql_param[':id'] = 0;

    public $edit_sql = ''; // sql query to define what fields to display on the edit/update page
    public $edit_sql_param = []; // named parameters for edit_sql: $o->add_sql_param[':id'] = 0;

    public $add = []; // define fields on add/insert, example: $o->add['create_date']['type'] = 'date';
    public $edit = []; // define fields on edit/update
    public $index = []; // define hooks to render fields on the opening table, example: $o->index['create_date']['function'] = function($data){ return date('d/m/Y', strtotime($data['value'])); };

    public $floating = false; // enable floating labels for add and edit forms https://getbootstrap.com/docs/5.3/forms/floating-labels/

    public $nonce_name = 'nonce'; // csrf field name
    public $nonce_value = ''; // csrf field value

    public $limit = 100; // pagination limit records/page, 0 = off
    public $ellipse_at = 0; // truncate text on index table, 0 = off
    public $query_string_carry = []; // carry data within the query string, useful to carry parent id when editing child records

    // override these values for localization
    public $i18n = ['no_records' => 'No Records', 'not_found' => 'Not Found', 'search' => 'Search', 'edit' => 'Edit', 'add' => 'Add', 'add_2' => 'Add', 'back' => 'Back', 'delete' => 'Delete', 'update' => 'Update', 'delete_file' => 'mark for removal', 'update_success' => 'Updated', 'insert_success' => 'Added', 'delete_success' => 'Deleted'];

    // opinionated class settings
    public $css = ['index_table' => 'table-striped table-hover', 'index_active' => 'table-primary', 'pagination' => 'justify-content-center'];

    // event hooks, called before sql insert/update/delete
    // can be used to alter $_POST data or add validation, return a string to display error
    public $on_insert = null;
    public $on_update = null;
    public $on_delete = null;

    // event hooks, called after sql insert/update/delete
    // can be used to manage related dependencies
    public $after_insert = null; // this hook is different, $o->$after_insert = function($id){ // no other functions get arguments, but I recieve the new id };
    public $after_update = null;
    public $after_delete = null;

    // helpful when debugging, display link after update/insert/delete, allows programmer to view errors/warnings/notices
    public $redirect = true;

    /**
     * save database connection to member var
     *
     * @param object $dbh    pdo handle to SQLite, MariaDB/MySQL, or PostgreSQL
     *
     * @return void
     */
    public function __construct(\PDO $dbh)
    {

        $this->dbh = $dbh;

    }

    /**
     * controller to call requested method
     *
     * @return void
     */
    public function run(): void
    {

        if (!function_exists('mb_strlen')) {
            die('mbstring php extension required');
        }

        $action = $_POST['_action'] ?? $_GET['_action'] ?? '';

        if ($action == 'get_file') {
            $this->get_file();
        } elseif ($action == 'update') {
            $this->update();
        } elseif ($action == 'insert') {
            $this->insert();
        } elseif ($action == 'delete') {
            $this->delete();
        } elseif ($action == 'edit') {
            $this->edit();
        } elseif ($action == 'add') {
            $this->add();
        } else {
            $this->index();
        }

    }

    /**
     * render the opening index view; the table of all records, add link, pagination, and search
     *
     * @return void
     */
    public function index(): void
    {

        $limit = $this->limit;
        $index_sql = $this->index_sql;
        $index_sql_param = $this->index_sql_param;

        $order_by = abs(intval($_GET['_order_by'] ?? 0));
        $desc = abs(intval($_GET['_desc'] ?? 0));
        $page = abs(intval($_GET['_page'] ?? 1));
        $offset = $limit * ($page - 1);
        $search = $this->c($_GET['_search'] ?? '');
        $id = $_GET[$this->identity_name] ?? 0;
        $success = $_GET['_success'] ?? 0;

        // prepare sql for tweaks
        $index_sql = preg_replace('/[\n\r]/', ' ', $index_sql);
        $index_sql = rtrim($index_sql, "; ");

        if ($order_by > 0) {

            // ugly hack - when a sort is requested, remove user's 'order by' clause, if any
            preg_match_all('/order\s+by\s/im', $index_sql, $matches, PREG_OFFSET_CAPTURE);
            if (count($matches[0] ?? array()) > 0) {
                $match = end($matches[0]);
                $index_sql = substr($index_sql, 0, $match[1]);
            }

            // append requested order
            $index_sql .= " order by " . intval($order_by);
            if ($desc == 1) {
                $index_sql .= " desc ";
            }

        }

        // $limit = 0 disables paging
        // $page = 0 browser request to disable paging
        $pagination = '';
        if ($limit != 0 && $page != 0) {

            $index_sql .= " limit " . intval($limit) . " offset " . intval($offset);

            $pagination = $this->get_pagination($this->index_sql, $this->index_sql_param, $page, $limit);
        }

        $result = $this->query($index_sql, $index_sql_param);

        require "views/index.php";

    }

    /**
     * render form to add new records, proceeds to insert() when posted
     *
     * @param mixed $error    on_insert hook can pass in error messages
     *
     * @return void
     */
    public function add(mixed $error = null): void
    {

        $id = intval($_GET[$this->identity_name] ?? $_POST[$this->identity_name] ?? 0);
        $qs = $this->get_query_string();

        if (strlen($this->add_sql) == 0) {
            die("error: add() expects add_sql and add_sql_param to be defined");
        }

        $result = $this->query_meta($this->add_sql, $this->add_sql_param);

        require "views/add.php";
        
    }

    /**
     * render form to edit existing records, proceeds to update() when posted
     *
     * @param mixed $error    on_update hook can pass in error messages
     *
     * @return void
     */
    public function edit(mixed $error = null): void
    {

        $id = intval($_GET[$this->identity_name] ?? $_POST[$this->identity_name] ?? 0);
        $action = $_POST['_action'] ?? '';
        $success = $_GET['_success'] ?? 0;
        $qs = $this->get_query_string();

        if (strlen($this->add_sql) == 0) {
            die("error: edit() expects edit_sql and edit_sql_param to be defined");
        }

        $result = $this->query($this->edit_sql, $this->edit_sql_param);

        require "views/edit.php";

    }

    /**
     * run the sql insert
     *
     * @return void
     */
    public function insert(): void
    {

        $qs = $this->get_query_string();

        // call closure, if defined
        $error = null;
        if (isset($this->on_insert)) {
            $error = call_user_func($this->on_insert);
        }

        // back to add to display validation message
        if (!empty($error)) {
            $this->add($error);
            return;
        }

        // get fields
        $result = $this->query_meta($this->add_sql, $this->add_sql_param);
        $fields = $result[0] ?? array();

        // gather data and build sql
        $str_1 = '';
        foreach ($fields as $field => $x) {

            // no id in set clause
            if ($this->identity_name == $field) {
                continue;
            }

            // no 'disabled', enforcing client rule, these shouldn't get posted anyway
            if (($this->edit[$field]['disabled'] ?? false) === true) {
                continue;
            }

            $value = $_POST[$field] ?? null;
            if (is_array($value)) {
                $value = json_encode($value);
            }

            if (($this->edit[$field]['type'] ?? '') == 'file') {
                $value = $this->get_upload($field, 'edit');
            }

            $str_1 .= "`$field`, ";
            $sql_param[":$field"] = $value;

        }
        $str_1 = trim($str_1, ', ');
        $str_2 = implode(', ', array_keys($sql_param));

        $sql = "insert into `{$this->table_name}` ($str_1) values ($str_2)";
        $id = $this->query($sql, $sql_param);

        // call closure, if defined
        if ($id && isset($this->after_insert)) {
            $error = call_user_func($this->after_insert, $id);
        }

        $url = "?_action=edit&{$this->identity_name}=$id&_success=1&$qs";
        if ($this->redirect) {
            header("Location: $url");
        }

        $this->continue_link($url);
    }

    /**
     * run the sql update
     *
     * @return void
     */
    public function update(): void
    {

        $qs = $this->get_query_string();
        $id = $_POST[$this->identity_name] ?? 0;

        // call closure, if defined
        $error = null;
        if (isset($this->on_update)) {
            $error = call_user_func($this->on_update);
        }

        // back to edit to display validation message
        if (!empty($error)) {
            $this->edit($error);
            return;
        }

        // get fields we are allowing update on, the fields in edit_sql
        $result = $this->query($this->edit_sql, $this->edit_sql_param);
        if (count($result) == 0) {
            echo $this->alert($this->i18n['not_found']);
            return;
        }

        // gather data
        $sql = '';
        $sql_param = array(":{$this->identity_name}" => $id);
        $fields = $result[0] ?? array();
        foreach ($fields as $field => $x) {

            // no id in set clause
            if ($this->identity_name == $field) {
                continue;
            }

            // no 'disabled', enforcing client rule, these shouldn't get posted anyway
            if (($this->edit[$field]['disabled'] ?? false) === true) {
                continue;
            }

            // get posted data
            $value = $_POST[$field] ?? null;
            if (is_array($value)) {
                $value = json_encode($value);
            }

            // deal with files
            if (($this->edit[$field]['type'] ?? '') == 'file') {

                // binary or filename depending on if 'file_path' is defined
                $value = $this->get_upload($field, 'edit');

                // process deletion requests or delete when new file arrives
                if (isset($_POST["{$field}_delete"]) || $value !== null) {
                    $this->delete_file($id, $field);
                }

                // don't change field when there's no upload AND no delete requested
                if ($value === null && !isset($_POST["{$field}_delete"])) {
                    continue;
                }

            }

            // building the sql string and gathering params
            $sql .= " `$field` = :$field, ";
            $sql_param[":$field"] = $value;
        }
        $sql = rtrim($sql, ', ');

        $sql = "update `{$this->table_name}` set $sql where `{$this->identity_name}` = :id";
        $this->query($sql, $sql_param);

        // call closure, if defined
        if (isset($this->after_update)) {
            call_user_func($this->after_update);
        }

        $url = "?_action=edit&{$this->identity_name}=$id&_success=2&$qs";
        if ($this->redirect) {
            header("Location: $url");
        }

        $this->continue_link($url);

    }

    /**
     * run the sql delete
     *
     * @return void
     */
    public function delete(): void
    {

        $qs = $this->get_query_string();
        $id = $_POST[$this->identity_name] ?? 0;

        // call closure, if defined
        $error = null;
        if (isset($this->on_delete)) {
            $error = call_user_func($this->on_delete);
        }

        // back to edit for validation message
        if (!empty($error)) {
            $this->edit($error);
            return;
        }

        $this->delete_file($id);
        $sql = "delete from `{$this->table_name}` where `{$this->identity_name}` = :id";
        $this->query($sql, array(':id' => $id));

        // call closure, if defined
        $error = '';
        if (isset($this->after_delete)) {
            $error = call_user_func($this->after_delete);
        }

        $url = "?&_success=3&$qs";
        if ($this->redirect) {
            header("Location: $url");
        }

        $this->continue_link($url);

    }

    /**
     * wrapper to query the database
     *
     * @param string $sql          the sql string
     * @param array  $sql_param    named parameters for sql
     *
     * @return mixed               'select' returns array result, 'insert' returns new id, 'update'+'delete' return rows affected
     */
    public function query(string $sql, array $sql_param = []): mixed
    {

        $sth = $this->dbh->prepare($sql);
        $sth->execute($sql_param);

        // updates + deletes return number of rows affected
        if (preg_match('/^(update|delete)/i', ltrim($sql))) {
            return $sth->rowCount();
        }

        // inserts return the id
        if (preg_match('/^insert/i', ltrim($sql))) {
            return $this->dbh->lastInsertId();
        }

        // selects return the data
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * wrapper to query the database, only field names are retuned, not the values
     *
     * @param string $sql          the sql string
     * @param array  $sql_param    named parameters for sql
     *
     * @return array               array containing the field names
     */
    public function query_meta(string $sql, array $sql_param = []): array
    {

        $sth = $this->dbh->prepare($sql);
        $sth->execute($sql_param);

        $row = array();
        $cnt = $sth->columnCount();
        for ($i = 0; $i < $cnt; $i++) {
            $col = $sth->getColumnMeta($i);
            $row[$col['name']] = null;
        }

        return array($row);

    }

    /**
     * wrapper for htmlspecialchars, c/clean escape output
     *
     * @param mixed  $str           the string to escape
     * @param int    $ellipse_at    optional, truncate long output and add ellipses
     *
     * @return string
     */
    public function c(mixed $str, int $ellipse_at = 0)
    {

        $str = (string) $str;

        // ellipse long text
        if ($ellipse_at > 0 && mb_strlen($str) > $ellipse_at) {
            $str = mb_substr($str, 0, $ellipse_at, $this->charset) . "...";
        }

        // remove illegal characters
        $str = mb_convert_encoding($str, $this->charset, mb_detect_encoding($str));

        return htmlspecialchars($str, ENT_QUOTES, $this->charset);
    }

    /**
     * Send requested file to client browser, expects: field and id, echos binary
     *
     * @return void
     */
    public function get_file(): void
    {

        $id = intval($_GET[$this->identity_name] ?? 0);
        $field = $_GET['field'] ?? '';
        $options = $this->edit; // files only appear in edit view
        $path = $options[$field]['file_path'] ?? '';

        if ($options[$field]['type'] != 'file') {
            die("requested field not a type=file in add or edit property");
        }

        $ext = trim(strtolower($options[$field]['file_extension'] ?? ''), '. ');
        if (strlen($ext) == 0) {
            die("error cannot locate expected entry in edit[field][file_extension]");
        }

        $sql = "select * from `{$this->table_name}` where `{$this->identity_name}` = :id";
        $result = $this->query($sql, array(':id' => $id));
        if (count($result) != 1) {
            die("record not found");
        }

        if (strlen($path) > 0) {

            // get file from filesystem
            $filename = $result[0][$field] ?? '';
            if (strlen($filename) == 0) {
                die("filename not found in database");
            }

            if (!file_exists("$path/$filename")) {
                die("file not found on filesystem");
            }

            $file = file_get_contents("$path/$filename");

        } else {

            // get file from database blob - default
            $filename = "{$this->table_name}_{$field}_{$id}.{$ext}";
            $file = $result[0][$field] ?? null;
            if (strlen($file ?? '') == 0) {
                die("file not found in database");
            }

        }

        // defaults
        $type = "image/$ext";
        $attachment_or_inline = 'inline';

        // jpg gets 'jpeg' content type
        if ($ext == 'jpg') {
            $type = "image/jpeg";
        }

        // non image
        if (!($ext == 'gif' || $ext == 'png' || $ext == 'jpg')) {
            $type = "application/$ext";
            $attachment_or_inline = 'attachment';
        }

        // deal with output buffering, custom ob_end_clean()
        $this->ob_end_clean();

        header("Cache-Control: maxage=1");
        header("Pragma: public");
        header("Content-Type: $type");
        header("Content-Disposition: $attachment_or_inline; filename=\"$filename\"");
        die($file);

    }

    /**
     * enhanced ob_end_clean(), used before sending binary files in get_file(), can be used for ajax calls
     *
     * @return void
     */
    public function ob_end_clean(): void
    {

        $level = 0;
        $arr = ob_get_status(true);
        if ($arr) {
            $level = count($arr);
        }

        if ($level <= 0) {
            die("error, output buffering required. add ob_start() to the beginning of the script.");
        }

        // erase any existing buffers
        while ($level >= 1) {
            ob_end_clean();
            $level--;
        }
    }

    /**
     * delete file from filesystem, only applies when $edit[field][file_path] is defined
     *
     * @param int $id          id of record in database
     * @param string $field    optional field name, when no field is passed, all files in the record are deleted
     *
     * @return int number of files deleted
     */
    public function delete_file(int $id, string $field = null)
    {

        $options = $this->edit;

        // get filenames, if any
        $sql = "select * from `{$this->table_name}` where `{$this->identity_name}` = :id";
        $result = $this->query($sql, array(':id' => $id));
        if (count($result) != 1) {
            return 0;
        }

        $row = $result[0];

        // single field delete requested, change $row into a single entry
        if (isset($field)) {
            $row = array($field => $row[$field]);
        }

        $cnt = 0;
        $field = '';
        foreach ($row as $field => $val) {

            // type file required
            if (($options[$field]['type'] ?? '') != 'file') {
                continue;
            }

            // path required
            if (strlen($options[$field]['file_path'] ?? '') == 0) {
                continue;
            }

            // filename required
            if (strlen($row[$field] ?? '') == 0) {
                continue;
            }

            // no null byte, might happen if switching between blob storage and filesystem
            if (strpos($row[$field], "\0") !== false) {
                continue;
            }

            $cnt++;
            @unlink("{$options[$field]['file_path']}/{$row[$field]}");
        }

        return $cnt;

    }

    /**
     * wrap string with bootstrap alert to make messages pretty
     *
     * @param mixed  $msg      string or array
     *                         string/text just displayed in an alert, but if html is desired then
     *                         use format $msg = ['text' => '', 'html' => '']
     *                         $msg comes from on_insert/on_update/on_delete hooks 
     * @param string $class    bootstrap class name
     *
     * @return string html
     */
    public function alert(mixed $msg, string $class = 'alert-danger'): string
    {

        // no input, nothing to do
        if (empty($msg)) {
            return '';
        }

        // the html element allows hooks on_insert/on_update/on_delete to pass back html data, probably to assist in validation
        // 2 formats expected, string or array('text'=> '', 'html' => '')
        $text = '';
        $html = '';
        if (is_string($msg)) {
            $text = $msg;
        } elseif (is_array($msg)) {
            $text = $msg['text'] ?? '';
            $html = $msg['html'] ?? '';
        }

        require "views/alert.php";

        return $text . $html;
    }

    /**
     * render pagination
     *
     * @param string $sql          the sql string, used to count total number of records
     * @param array  $sql_param    named parameters for sql
     * @param int    $page         current requested page
     * @param int    $limit        number of records per page
     *
     * @return string html
     */
    public function get_pagination(string $sql, array $sql_param, int $page, int $limit): string
    {

        // count how many records we have
        $sql = "select count(1) as records from ($sql)";
        $result = $this->query($sql, $sql_param);
        $records = intval($result[0]['records'] ?? 0);

        $offset = ($page - 1) * $limit;
        $total_page = ceil($records / $limit);

        // nothing to do
        if ($total_page <= 1) {
            return '';
        }

        // keep these: first, last, current, current + 1, current - 1
        $arr = [];
        for ($p = 1; $p <= $total_page; $p++) {
            if ($p == 1 || $p == $total_page || $p == $page || $p == ($page + 1) || $p == ($page - 1)) {
                $arr[$p] = $p;
            }
        }

        // add 3, nice to have 1, 2, 3 at the beginning
        if ($page == 1 && $total_page >= 3) {
            $arr[3] = 3;
        }

        // same as above, nice to have 3 digits at the end
        if ($page == $total_page && $total_page >= 3) {
            $arr[$page - 2] = $page - 2;
        }

        // if there's a gap at the start, add ellipses
        if (!isset($arr[2])) {
            $arr[2] = '...';
        }

        // if there's a gap at the end, add ellipses at end
        if (!isset($arr[$total_page - 1])) {
            $arr[$total_page - 1] = '...';
        }

        ksort($arr);

        // carry settings in qs
        $qs = $this->get_query_string(['_page']);

        $html_final = '';
        include "views/get_pagination.php";

        return $html_final;
    }

    /**
     * build query string, carry params to maintain state, exclude requested params
     *
     * @param array $exclude    array of parameters to exclude, see $arr below for what params carried
     *
     * @return string html
     */
    public function get_query_string(array $exclude = []): string
    {

        // system parameters to always carry on the querystring
        $arr = ['_page', '_order_by', '_desc', '_search', '_pagination_off'];

        // append requested additions, if any
        $arr = array_merge($arr, $this->query_string_carry);
    
        $qs = '';
        foreach ($arr as $key) {

            $val = $_POST[$key] ?? $_GET[$key] ?? null;

            // no input found
            if ($val === null) {
                continue;
            }

            // skip items in exclude
            if (array_search($key, $exclude) !== false) {
                continue;
            }

            // append strings
            if (is_string($val) && strlen($val) > 0) {
                $qs .= '&' . urlencode($key) . '=' . urlencode($val);
            }

            // append arrays
            if (is_array($val)) {
                foreach ($val as $v) {
                    $qs .= '&' . urlencode($key . '[]') . '=' . urlencode($v);
                }
            }

        }

        return ltrim($qs, '&');

    }

    /**
     * change field titles into something more friendly, reads 'label' setting, eg. $edit[field]['label']
     *
     * @param string $field      database field name
     * @param string $context    context of origin; index/edit/add, what settings to use
     *
     * @return string new name/title
     */
    public function get_label(string $field, string $context): string
    {

        $arr = array();
        if ($context == 'index') {
            $arr = $this->index;
        } elseif ($context == 'edit') {
            $arr = $this->edit;
        } elseif ($context == 'add') {
            $arr = $this->add;
        }

        if (isset($arr[$field]['label'])) {
            $field = $arr[$field]['label'];
        } else {
            $field = str_replace('_', ' ', $field);
            $field = ucwords(strtolower($field));
        }

        return $field;

    }

    /**
     * render requested field into html input/select/textarea etc...
     *
     * @param string $field          the field name
     * @param string $value          the field's current value
     * @param string $add_or_edit    context, are we on the add or edit page
     * @param bool   $input_only     only return the input, no other html, no title/label
     *
     * @return string html
     */
    public function get_input(string $field, string $value, string $add_or_edit, bool $input_only = false): string
    {

        // these are saved into settings[]
        $add_to_settings = array('sql', 'sql_param', 'function', 'name', 'value', 'type', 'multiple', 'class', 'div_class', 'colspan', 'file_extension', 'file_image_width', 'file_image_height', 'file_image_crop_or_resize', 'floating', 'label');

        $options = $this->edit;
        if ($add_or_edit == 'add') {
            $options = $this->add;
        }

        // parse $options into $attr string, and internal $settings[] array
        $attr = '';
        $settings = [];
        foreach (($options[$field] ?? array()) as $key => $val) {

            // these are custom attributes, don't append to $attr string
            if (array_search($key, $add_to_settings) !== false) {
                $settings[$key] = $val;
                continue;
            }

            // skip false properties, for example an uncheck is the absence of the 'checked' property
            if ($val === false) {
                continue;
            }

            if ($val === true) {
                // properties, such as 'disabled'
                $attr .= " $key";
            }
            else {
                // normal key+val attributes
                $attr .= " $key='" . $this->c($val) . "'";
            }
            // html attributes
        }

        // floating layout needs a placeholder to work best
        if ($this->floating && strpos($attr, "placeholder='") === false) {
            $attr .= " placeholder='" . $this->get_label($field, $add_or_edit) . "'";
        }

        // text title for the field, optional set in $add/$edit[field]['rename']
        $label_text = $this->get_label($field, $add_or_edit);

        // only needed for passing to call_user_func, keeping params consistent index()
        $id = $_POST[$this->identity_name] ?? $_GET[$this->identity_name] ?? null;

        $type = $settings['type'] ?? '';

        if (isset($settings['function'])) {
            // call user's closure
            list($label, $html) = call_user_func($settings['function'], ['field' => $field, 'value' => $value, 'id' => $id]);
        } elseif ($type == 'select') {
            list($label, $html) = $this->html_select($field, $value, $label_text, $settings, $attr);
        } elseif ($type == 'datalist') {
            list($label, $html) = $this->html_select($field, $value, $label_text, $settings, $attr, true);
        } elseif ($type == 'checkbox') {
            list($label, $html) = $this->html_checkbox($field, $value, $label_text, $settings, $attr);
        } elseif ($type == 'radio') {
            list($label, $html) = $this->html_checkbox($field, $value, $label_text, $settings, $attr, true);
        } elseif ($type == 'textarea') {
            list($label, $html) = $this->html_textarea($field, $value, $label_text, $settings, $attr);
        } elseif ($type == 'file') {
            list($label, $html) = $this->html_file($field, $value, $label_text, $settings, $attr);
        } else {
            list($label, $html) = $this->html_input($field, $value, $label_text, $settings, $attr);
        }

        $colspan = intval($settings['colspan'] ?? 12);
        $div_class = $settings['div_class'] ?? '';

        // special mode, might be used for ajax requests, only return the input/select
        if ($input_only) {
            return $html;
        }

        // hidden just needs the input, no divs
        if ($type == 'hidden') {
            return $html;
        }

        // these inputs already add $div_class setting
        if ($type == 'checkbox' || $type == 'radio') {
            $div_class = '';
        }

        $floating = $this->floating;

        // no floating layout on these inputs
        if ($type == 'checkbox' || $type == 'radio') {
            $floating = false;
        }

        // allow override
        if (isset($settings['floating'])) {
            $floating = (bool) $settings['floating'];
        }

        $file_class = '';
        if ($type == 'file') {
            $file_class = 'input-group';
        }

        // populate $html_final
        $html_final = '';
        if ($floating) {

           // floating style
           require "views/get_input_0.php";     

        } else {

           // non floating style
           require "views/get_input_1.php";     
        }

        return $html_final;

    }

    /**
     * render <input>
     *
     * @param string $field       the name attribute
     * @param string $value       the value attribute
     * @param string $label       the field's visible title
     * @param array  $settings    non-attribute internal settings
     * @param string $attr        other attribute/properties
     *
     * @return array [label, html]
     */
    public function html_input(string $field, string $value, string $label, array $settings, string $attr): array
    {

        $type = $settings['type'] ?? 'text';
        $class = $settings['class'] ?? '';

        require "views/html_input.php";

        // no label on hidden input
        if ($type == 'hidden') {
            return ['', $html];
        }

        return [$label, $html];
    }

    /**
     * render <textarea>
     *
     * @param string $field       the name attribute
     * @param string $value       the text
     * @param string $label       the field's visible title
     * @param array  $settings    non-attribute internal settings
     * @param string $attr        other attribute/properties
     *
     * @return array [label, html]
     */
    public function html_textarea(string $field, string $value, string $label, array $settings, string $attr): array
    {

        $class = $settings['class'] ?? '';

        require "views/html_textarea.php";

        return [$label, $html];
    }

    /**
     * render <select> or <datalist>
     *
     * @param string $field       the name attribute
     * @param string $value       the value attribute
     * @param string $label       the field's visible title
     * @param array  $settings    non-attribute internal settings
     * @param string $attr        other attribute/properties
     * @param bool   $is_datalist change or <datalist> from <select>
     *
     * @return array [label, html]
     */
    public function html_select(string $field, string $value, string $label, array $settings, string $attr, bool $is_datalist = false): array
    {

        $sql = $settings['sql'] ?? '';
        $sql_param = $settings['sql_param'] ?? [];
        $class = $settings['class'] ?? '';

        // make bool property into string, add brackets to post data as array
        $multiple = '';
        $brackets = '';
        if ($settings['multiple'] ?? false) {
            $multiple = 'multiple';
            $brackets = '[]';
        }

        // keep all comparisons as strings
        if (!is_array($value)) {
            $value = (string) $value;
        }

        // json selections assumed when strings are seen in multiple mode, change to array
        if (strlen($multiple) > 0 && is_string($value) && substr($value, 0, 1) == '[') {
            $value = @json_decode($value, true);
        }

        // again, keep values string
        if (is_array($value)) {
            array_walk_recursive($value, function (&$v) {$v = (string) $v;});
        }

        if (strlen($sql) == 0) {
            die('error: html_select() expects add[field][sql] and or edit[field][sql] required to render dropdown options');
        }

        $result = $this->query($sql, $sql_param);

        require "views/html_select.php";

        return [$label, $html];

    }

    /**
     * render <input type='checkbox'> or <input type='radio'>
     *
     * @param string $field       the name attribute
     * @param string $value       the value attribute
     * @param string $label       the field's visible title
     * @param array  $settings    non-attribute internal settings
     * @param string $attr        other attribute/properties
     * @param bool   $is_radio    change to radio inputs, default is checkbox
     *
     * @return array [label, html]
     */
    public function html_checkbox(string $field, string $value, string $label, array $settings, string $attr, bool $is_radio = false): array
    {

        $sql = $settings['sql'] ?? '';
        $sql_param = $settings['sql_param'] ?? [];
        $class = $settings['class'] ?? '';
        $div_class = $settings['div_class'] ?? '';
        $multiple = (bool) ($settings['multiple'] ?? false);

        // dual purpose
        $checkbox_or_radio = 'checkbox';
        $brackets = '[]'; // brackets make <input> an array, multiple checkboxes
        if ($is_radio) {
            $checkbox_or_radio = 'radio';
            $brackets = ''; // radios are not arrays, they just submit 1 value
        }

        // default to a single on/off checkbox if no sql options are specified
        if (strlen($sql) == 0) {
            $sql = "select 1 as val, '' as opt";
            $brackets = ''; // single checkboxes don't post as an array
        }

        // keep all comparisons as strings
        if (!is_array($value)) {
            $value = (string) $value;
        }

        // json assumed when we have multiple inputs
        if (strlen($brackets) == 2 && is_string($value) && substr($value, 0, 1) == '[') {
            $value = @json_decode($value, true);
        }

        // again, keep values string
        if (is_array($value)) {
            array_walk_recursive($value, function (&$v) {$v = (string) $v;});
        }

        $result = $this->query($sql, $sql_param);

        require "views/html_checkbox.php";

        return ['', $html];

    }

    /**
     * render <input type='file'> includes in link for docs, thumbnail for images, and delete checkbox for removing file
     *
     * @param string $field       the name attribute
     * @param string $value       binary or filename, not displayed, used for trigger removal checkbox option
     * @param string $label       the field's visible title
     * @param array  $settings    non-attribute internal settings
     * @param string $attr        other attribute/properties
     *
     * @return array [label, html]
     */
    public function html_file(string $field, string $value, string $label, array $settings, string $attr): array
    {

        $id = intval($_GET[$this->identity_name] ?? $_POST[$this->identity_name] ?? 0);
        $type = $settings['type'] ?? 'text';
        $class = $settings['class'] ?? '';
        $ext = trim($settings['file_extension'] ?? '', ' .');
        $filename = "{$this->table_name}_{$field}_{$id}.{$ext}";
        $url = "?_action=get_file&field={$field}&id={$id}?ts=" . time();

        $is_image = false;
        if ($ext == 'gif' || $ext == 'png' || $ext == 'jpg') {
            $is_image = true;
        }

        require "views/html_file.php";

        return [$label, $html];

    }


    /**
     * display continue link
     *
     * @param string $url       the url link
     *
     * @return void
     */
    public function continue_link(string $url): void
    {

        require "views/continue_link.php";

    }


    /**
     * get binary file upload
     *
     * @param string $field       the field name
     * @param string $add_or_edit context for what settings to use
     *
     * @return mixed              binary string, string filename, or null 
     */
    public function get_upload(string $field, string $add_or_edit): mixed
    {

        // get settings from user defined $edit or $add property array
        $options = $this->edit;
        if ($add_or_edit == 'add') {
            $options = $this->add;
        }

        $ext = $options[$field]['file_extension'] ?? '';
        $path = rtrim($options[$field]['file_path'] ?? '', '/');
        $crop_or_resize = $options[$field]['file_image_crop_or_resize'] ?? '';

        $size = intval($_FILES[$field]['size'] ?? 0);
        $tmp_name = $_FILES[$field]['tmp_name'] ?? '';
        $filename = $this->get_filename($_FILES[$field]['name'] ?? '', $ext, $path, $field);
        $input_ext = strtolower(pathinfo($_FILES[$field]['name'] ?? '', PATHINFO_EXTENSION));

        if ($size <= 0) {
            return null;
        }

        if ($crop_or_resize == 'crop') {
            $this->image_crop($tmp_name, $input_ext, $options[$field]['file_extension'], $options[$field]['file_image_width'], $options[$field]['file_image_height']);
        }

        if ($crop_or_resize == 'resize') {
            $this->image_resize($tmp_name, $input_ext, $options[$field]['file_extension'], $options[$field]['file_image_width'], $options[$field]['file_image_height']);
        }

        // path specified, storing file in the filesystem, save filename in database
        if (strlen($path) > 0) {

            if (!move_uploaded_file($tmp_name, "$path/$filename")) {
                return null;
            }

            return $filename;
        }

        // no path specified, storing file as blob in database
        return file_get_contents($tmp_name);

    }

    /**
     * cleanup incoming filename from post
     *
     * @param string $filename the incoming filename
     * @param string $ext      the fixed file extension
     * @param string $path     path to the file destination, no path means the binary is going to be stored in the db/blob
     * @param string $field    the field name
     *
     * @return string the safer filename
     */
    public function get_filename(string $filename, string $ext, string $path, string $field): string
    {

        // no path means nothing to do here, file is going into the database as a blob
        if (strlen($path) == 0) {
            return "";
        }

        $id = intval($_POST[$this->identity_name] ?? 0);
        $chars = str_split("\0\r\n\t!?[]/\=<>:;,`'\"^%&$#*()|~{}%+@");

        $filename = substr(@pathinfo($filename)['filename'], 0, 200);  // remove extension
        $filename = str_replace($chars, '', $filename);                // remove offending chars
        $filename = preg_replace('/\s+/', '_', trim($filename, '. ')); // underscore for spaces

        // fallback name
        if (strlen($filename) == 0) {
            $filename = "{$this->table_name}_{$field}";
            if ($id > 0) {
                $filename .= "_$id";
            }
        }

        // return filename if not in use
        if (!file_exists("$path/$filename.$ext")) {
            return "$filename.$ext";
        }

        // make name unique
        $addon = -1;
        while (file_exists("$path/$filename$addon.$ext")) {
            $addon--;
        }

        return "$filename$addon.$ext";

    }

    /**
     * resize image file, maintains aspect ratio, fits image into dimension specified in settings
     *
     * @param string $filename   the filename
     * @param string $input_ext  input format extension
     * @param string $output_ext desired output format extension
     * @param int    $max_width  output max width
     * @param int    $max_height output max height
     *
     * @return bool true = success
     */
    public function image_resize(string $filename, string $input_ext, string $output_ext, int $max_width, int $max_height): bool
    {

        $valid = ['jpg', 'gif', 'png'];
        if (array_search($input_ext, $valid) === false || array_search($output_ext, $valid) === false) {
            return false;
        }

        if (!function_exists('imagecreatetruecolor')) {
            die("error: gd must be installed for image_resize()");
        }

        $this->image_exif_rotate($filename, $input_ext);

        list($orig_width, $orig_height) = getimagesize($filename);

        // something is wrong
        if ($orig_width == 0 || $orig_height == 0 || $max_width == 0 || $max_height == 0) {
            return false;
        }

        // image is already smaller than maximum size
        if ($orig_width <= $max_width && $orig_height <= $max_height) {
            return true;
        }

        $width = $orig_width;
        $height = $orig_height;

        // taller
        if ($height > $max_height) {
            $width = intval(($max_height / $height) * $width);
            $height = $max_height;
        }

        // wider
        if ($width > $max_width) {
            $height = intval(($max_width / $width) * $height);
            $width = $max_width;
        }

        $image_p = imagecreatetruecolor($width, $height);

        if ($input_ext == 'gif') {
            $image = imagecreatefromgif($filename);
        } elseif ($input_ext == 'png') {
            $image = imagecreatefrompng($filename);
        } else {
            $image = imagecreatefromjpeg($filename);
        }

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);

        if ($output_ext == 'gif') {
            imagegif($image_p, $filename);
        } elseif ($output_ext == 'png') {
            imagepng($image_p, $filename);
        } else {
            imagejpeg($image_p, $filename, 100);
        }

        return true;

    }

    /**
     * crop image file into exact requested size, maintains aspect ratio
     *
     * @param string $filename       the filename
     * @param string $input_ext      input format extension
     * @param string $output_ext     desired output format extension
     * @param int    $desired_width  output width
     * @param int    $desired_height output height
     *
     * @return bool true = success
     */
    public function image_crop(string $filename, string $input_ext, string $output_ext, int $desired_width, int $desired_height): bool
    {

        $valid = ['jpg', 'gif', 'png'];
        if (array_search($input_ext, $valid) === false || array_search($output_ext, $valid) === false) {
            return false;
        }

        if (!function_exists('imagecreatetruecolor')) {
            die("error: gd must be installed for image_crop()");
        }

        $this->image_exif_rotate($filename, $input_ext);

        list($width, $height) = getimagesize($filename);

        // something is wrong
        if ($height == 0 || $width == 0 || $desired_height == 0 || $desired_width == 0) {
            return false;
        }

        // already the correct format
        if ($height == $desired_height && $width == $desired_width && $input_ext == $output_ext) {
            return true;
        }

        if ($desired_width / $desired_height >= $width / $height) {
            $new_width = $desired_width;
            $new_height = intval($height * ($desired_width / $width));
        } else {
            $new_width = intval($width * ($desired_height / $height));
            $new_height = $desired_height;
        }

        $image_p = imagecreatetruecolor($new_width, $new_height);
        $image_f = imagecreatetruecolor($desired_width, $desired_height);

        if ($input_ext == 'png') {
            $image = imagecreatefrompng($filename);
        } elseif ($input_ext == 'gif') {
            $image = imagecreatefromgif($filename);
        } else {
            $image = imagecreatefromjpeg($filename);
        }

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // top center cropping
        $x = intval(($new_width - $desired_width) / 2);
        $y = intval(($new_height - $desired_height) / 5);

        imagecopyresampled($image_f, $image_p, 0, 0, $x, $y, $desired_width, $desired_height, $desired_width, $desired_height);

        if ($output_ext == 'gif') {
            imagegif($image_f, $filename);
        } elseif ($output_ext == 'png') {
            imagepng($image_f, $filename);
        } else {
            imagejpeg($image_f, $filename, 100);
        }

        return true;

    }

    /**
     * try to rotate image according to the images exif meta
     *
     * @param string $filename       the filename
     * @param string $ext            the fixed file extension
     *
     * @return void
     */
    public function image_exif_rotate(string $filename, string $ext): void
    {

        if (!function_exists('imagerotate')) {
            return;
        }

        if ($ext == 'png') {
            $image = imagecreatefrompng($filename);
        } elseif ($ext == 'gif') {
            $image = imagecreatefromgif($filename);
        } else {
            $image = imagecreatefromjpeg($filename);
        }

        $arr = @exif_read_data($filename);

        $o = 0;
        if (isset($arr['Orientation'])) {
            $o = intval($arr['Orientation']);
        }

        // nothing to do
        if ($o == 0) {
            return;
        }

        if ($o == 3 || $o == 4) {
            $image = imagerotate($image, 180, 0);
        } elseif ($o == 5 || $o == 6) {
            $image = imagerotate($image, -90, 0);
        } elseif ($o == 7 || $o == 8) {
            $image = imagerotate($image, 90, 0);
        }

        if ($o == 2 || $o == 4 || $o == 5 || $o == 7) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        if ($ext == 'gif') {
            imagegif($image, $filename);
        } elseif ($ext == 'png') {
            imagepng($image, $filename);
        } else {
            imagejpeg($image, $filename, 100);
        }

        unset($image);
    }

}

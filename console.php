<?php
/**
 * Wordpress cli DS 1.0
 * Very simple console wp, helps to change thing on project for example:
 * Change theme
 * Deactive plugins
 * 
 * Its first version so it will be developed, with next version
 * Author: DamianS
 */

include_once 'wp-config.php';

function printText($text){
    echo "\n".$text;
}
if (!class_exists('PDO')) {
    printText( "\nRequired PDO, please install module\n");
    die();
}

/**
 * DB class PDO
 */
class DB {
    private $conn;
    public function __construct()
    {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn = $conn;
          } catch(PDOException $e) {
            printText("Error: " . $e->getMessage());
          }
    }
    public function query($sql){
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt;
    }
}

/**
 * Commands class
 */
class Commands {
        /** Command theme */
        public function themeCommend($params){
            $db     = new DB();
            if (count($params) == 2) {
                printText("Available themes:\n******************* ");
                $result = $db->query(strtr("SELECT option_value from %prefix_options where option_name='_site_transient_theme_roots'", [
                    '%prefix_' => $this->prefix
                ]))->fetchAll();
                if (!empty($result)){
                    $themesName = array_keys(unserialize($result[0]['option_value']));
                    printText(implode("\n", $themesName));
                }
            } else if ($params[2] === 'change' && isset($params[3])) {
                $db->query(strtr("UPDATE %prefix_options SET option_value='%theme_' where 
                option_name='stylesheet' OR option_name='current_theme' OR option_name='template';", [
                    '%prefix_'  => $this->prefix,
                    '%theme_'   => $params[3]
                ]));
                printText("Theme updated!");
            } else {
                printText("Command not exists");
            }
        }
        public function pluginCommand($params){
            $db     = new DB();
            if (count($params) == 2) {
                printText("Active plugins:\n******************* ");
                $result = $db->query(strtr("SELECT option_value from %prefix_options where option_name='active_plugins'", [
                    '%prefix_' => $this->prefix
                ]))->fetchAll();
                if (!empty($result)){
                    $plugins = unserialize($result[0]['option_value']);
                    foreach ($plugins as $i => $v) {
                        printText("nr_plugin: ".$i."  Name: ".$v);
                    }
                }
            } else if ($params[2] === 'deactive' && isset($params[3])) {
                $result = $db->query(strtr("SELECT option_value from %prefix_options where option_name='active_plugins'", [
                    '%prefix_' => $this->prefix
                ]))->fetchAll();
                if (!empty($result)){
                    $plugins = unserialize($result[0]['option_value']);
                }
                unset($plugins[$params[3]]);
                $db->query(strtr("UPDATE %prefix_options SET option_value='%optionValue_' where 
                option_name='active_plugins';", [
                    '%prefix_'  => $this->prefix,
                    '%optionValue_' => serialize(array_values($plugins))
                ]));
                printText("Plugin deactivated!");
            }
        }
}

/**
 * Class Console
 * Wykonywanie polecen konsolowych
 */
class Console extends Commands {
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }
    /**
     * Display command list
     */
    public function showCommandList(){
        printText("Available commands:\n********************");
        $commands = [
            'THEME',
            'theme - show available themes',
            'theme change [name_theme] - change theme',
            '',
            'PLUGIN',
            'plugin - show active plugins',
            'plugin deactive [nr_plugin] - deactive by number plugin',
        ];
        printText("Example use: php console.php theme\n***");
        printText(implode("\n", $commands));
    }
    /**
     * Execute choosen command
     */
    public function initCommand($params){
        if ($params[1] === 'theme') {
            $this->themeCommend($params);
        } else if ($params[1] === 'plugin') {
            $this->pluginCommand($params);
        } else {
            printText("No command");
        }
    }
    /**
     * init console
     */
    public function init($ac, $av) {
        if (isset($ac) && $ac > 1) {
            $this->initCommand($av);
        } else {
            $this->showCommandList();
        }
    }
}


printText("*************************\nWordpress CLI Helper 1.0\n*************************");
printText("Tested on: WP-5.7\n*************************\n");

$console = new Console($table_prefix);
$console->init($argc, $argv);

printText("\n");

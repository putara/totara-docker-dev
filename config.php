<?php  // Totara configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

require_once(__DIR__ . '/_notes');

$CFG->dblibrary   = 'native';
$CFG->dbname      = $DB_NAME;
$CFG->dbhost      = 'localhost';
$CFG->dboptions   = array(
  'dbpersist' => false,
  'dbport' => '',
  'dbsocket' => ''
);

$PHP_VERS = (int)((float)PHP_VERSION * 10);
$SITE_HOST = $PHP_VERS == 73 ? 'totara' : "totara{$PHP_VERS}";
$SERVER_DIR = '';
if (file_exists(__DIR__ . '/server/version.php')) {
  $SERVER_DIR = '/server';
}

if (strncmp($SITE_DB, 'pgsql', 5) === 0) {
  $CFG->dbtype  = 'pgsql';
  $CFG->dbuser  = 'postgres';
  $CFG->dbpass  = '';
  $CFG->dboptions['dbsocket'] = "/run/{$SITE_DB}";
} else if (strncmp($SITE_DB, 'mysql', 5) === 0) {
  $CFG->dbtype  = 'mysqli';
  $CFG->dbuser  = 'root';
  $CFG->dbpass  = 'root';
  $CFG->dboptions['dbsocket'] = "/run/{$SITE_DB}/mysqld.sock";
// } else if (strncmp($SITE_DB, 'mariadb', 7) === 0) {
//   $CFG->dbtype  = 'mariadb';
//   $CFG->dbuser  = 'root';
//   $CFG->dbpass  = 'root';
// } else if ($SITE_DB == 'mssql') {
//   $CFG->dbtype  = $PHP_VERS >= 70 ? 'sqlsrv' : 'mssql';
//   $CFG->dbuser  = 'SA';
//   $CFG->dbpass  = 'Totara.Mssql1';
} else {
  throw new \Exception("Unknown \$SITE_DB : {$SITE_DB}");
}

$CFG->admin = 'admin';
$CFG->directorypermissions = 0777;

$CFG->wwwroot = "http://{$SITE_HOST}/{$SITE_DIR}{$SERVER_DIR}";
$CFG->behat_wwwroot = "http://behat.{$SITE_HOST}/{$SITE_DIR}{$SERVER_DIR}";

$CFG->dataroot = "/var/www/totara/data/site/{$DATA_DIR}";
$CFG->phpunit_dataroot = "/var/www/totara/data/phpunit/{$DATA_DIR}";
$CFG->behat_dataroot = "/var/www/totara/data/behat/{$DATA_DIR}";

$CFG->prefix = 'mdl_';
$CFG->phpunit_prefix = 'phpu_';
$CFG->behat_prefix = 'bht_';

$CFG->noreplyaddress = 'noreply@example.com';

$CFG->session_handler_class = '\core\session\memcached';
$CFG->session_memcached_save_path = '/var/run/memcached/memcached.sock:0';

// define('TOOL_LANGIMPORT_REMOTE_TESTS', 1);
// define('TOTARA_DISTRIBUTION_TEST', 1);

if (isset($BEHAT_SELENIUM)) {
  $CFG->behat_config = array(
    'default' => array(
      'extensions' => array(
        'Behat\MinkExtension\Extension' => array(
          'selenium2' => array(
            'browser' => 'chrome',
            'wd_host' => "http://{$BEHAT_SELENIUM}:4444/wd/hub"
          )
        )
      )
    ),
    'selenium2' => array(
      'extensions' => array(
        'Behat\MinkExtension\Extension' => array(
          'selenium2' => array(
            'browser' => 'chrome',
            'wd_host' => "http://{$BEHAT_SELENIUM}:4444/wd/hub"
          )
        )
      )
    )
  );

  if (!empty($BEHAT_PARALLEL)) {
    $CFG->behat_parallel_run = array();
    for ($i = 0; $i < $BEHAT_PARALLEL; $i++) {
      $CFG->behat_parallel_run[] = array(
        'browser' => 'chrome',
        'wd_host' => "http://{$BEHAT_SELENIUM}:4444/wd/hub",
        'behat_wwwroot' => "http://behat{$i}.{$SITE_HOST}/{$SITE_DIR}{$SERVER_DIR}"
      );
    }
  }
}

if (!empty($SITE_TYPE)) {
  $CFG->sitetype = $SITE_TYPE;
} else {
  $CFG->sitetype = 'development';
}

if ($SERVER_DIR && !empty($SITE_FLAVOUR)) {
  $CFG->forceflavour = $SITE_FLAVOUR;
}

if (!empty($DEBUG_DEV)) {
  $CFG->debug = (E_ALL | E_STRICT);
  $CFG->debugpageinfo = true;
  $CFG->showcrondebugging = true;
  $CFG->cachejs = false;
  $CFG->langstringcache = false;
}

if (!empty($DEBUG_JS)) {
  $CFG->cachejs = false;
  $CFG->cachetemplates = false;
  if ($DEBUG_JS === 'brute') {
    $CFG->themedesignermode = true;
    $CFG->yuicomboloading = true;
    $CFG->yuiloglevel = 'debug';
  }
}

if (!empty($DEBUG_TUI)) {
  $CFG->langstringcache = false;
  $CFG->tuidesignermode = true;
  if (!isset($CFG->forced_plugin_settings)) {
    $CFG->forced_plugin_settings = array();
  }
  if (!isset($CFG->forced_plugin_settings['totara_tui'])) {
    $CFG->forced_plugin_settings['totara_tui'] = array();
  }
  $CFG->forced_plugin_settings['totara_tui']['cache_js'] = false;
  $CFG->forced_plugin_settings['totara_tui']['cache_scss'] = false;
  $CFG->forced_plugin_settings['totara_tui']['development_mode'] = true;
}

if (!empty($DEBUG_GQL)) {
  define('GRAPHQL_DEVELOPMENT_MODE', true);
  if ($DEBUG_GQL === 'brute') {
    $CFG->cache_graphql_schema = false;
  }
}

if (!empty($DEBUG_PERF)) {
  $CFG->perfdebug = 15;
  $CFG->perfdebugpageinfo = true;
}

if (!empty($DEBUG_MOBILE)) {
  $CFG->mobile_device_emulator = true;
}

if (!empty($DEBUG_XSTATE)) {
  $CFG->xstate_inspect = true;
}

if (!empty($DEBUG_MEET)) {
  $CFG->virtual_meeting_poc_plugin = true;
}

if (!empty($SITE_TUNNEL_HOST) &&
  isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
  isset($_SERVER['HTTP_HOST']) &&
  strpos($_SERVER['HTTP_HOST'], $SITE_TUNNEL_HOST) !== false) {
  // rewrite header values for ngrok et al.
  unset($_SERVER['HTTP_X_FORWARDED_FOR']);
  unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
  $_SERVER['HTTPS'] = 'on';
  $CFG->wwwroot = "https://".$_SERVER['HTTP_HOST']."/{$SITE_DIR}{$SERVER_DIR}";
}

function DEBUG_LOG($message) {
  error_log($message);
}

function DEBUG_LOGF($format, ...$args) {
  DEBUG_LOG(sprintf($format, ...$args));
}

function DEBUG_VAR($var) {
  DEBUG_LOG(var_export($var, true));
}

function DEBUG_VARJ($var, $pretty = false) {
  DEBUG_LOG(json_encode($var, JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0)));
}

function DEBUG_TRACE() {
  $e = new Exception();
  DEBUG_LOG($e->getTraceAsString());
}

unset(
  $SITE_DB,
  $SITE_DIR,
  $SITE_TYPE,
  $SITE_FLAVOUR,
  $SITE_TUNNEL_HOST,
  $DB_NAME,
  $DATA_DIR,
  $BEHAT_SELENIUM,
  $BEHAT_PARALLEL,
  $DEBUG_DEV,
  $DEBUG_JS,
  $DEBUG_TUI,
  $DEBUG_GQL,
  $DEBUG_PERF,
  $DEBUG_MOBILE,
  $DEBUG_XSTATE,
  $DEBUG_MEET,
  $PHP_VERS,
  $SITE_HOST,
  $SERVER_DIR,
);

// if (file_exists(__DIR__ . "/lib/setup.php")) {
//   require_once(__DIR__ . "/lib/setup.php");
// }

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!

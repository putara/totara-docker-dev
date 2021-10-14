<?php

if (isset($_SERVER['REMOTE_ADDR'])) {
  exit(1);
}

@error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', '1');

$PWD = getcwd();
$PRJ_ROOT = realpath(__DIR__ . '/../..');
$SRC_ROOT = realpath($PRJ_ROOT . '/../src');

function bye(...$msgs) {
  fwrite(STDERR, implode(PHP_EOL, $msgs) . PHP_EOL);
  die(1);
}

function writeln(...$msgs) {
  fwrite(STDOUT, implode(PHP_EOL, $msgs) . PHP_EOL);
}

function get_dirroot() {
  global $PWD, $SRC_ROOT;
  $dir = realpath($PWD);
  while ($dir !== $SRC_ROOT) {
    $version_php = $dir . '/server/version.php';
    if (file_exists($version_php)) {
      return $dir . '/server';
    }
    $version_php = $dir . '/version.php';
    if (file_exists($version_php)) {
      return $dir;
    }
    $dir = realpath($dir . '/../');
  }
  return $dir;
}

function get_vers($dirroot) {
  $version_php = $dirroot . '/version.php';
  $script = ["define('MOODLE_INTERNAL',1)"];
  foreach (['ALPHA', 'BETA', 'RC', 'EVERGREEN', 'STABLE'] as $mat) {
    $script[] = "define('MATURITY_{$mat}','{$mat}')";
  }
  $script[] = 'require_once(' . var_export($version_php, true) . ')';
  $script[] = 'echo $TOTARA->version;';
  if (empty($strver = shell_exec(PHP_BINARY . ' -r ' . escapeshellarg(implode(';', $script))))) {
    bye('Could not detect totara version');
  }
  $intver = (int)$strver;
  $supvers = [
     2 => ['5.6', ['5.6']],
     9 => ['5.6', ['5.6', '7.0']],
    10 => ['5.6', ['5.6', '7.0', '7.1', '7.2', '7.3']],
    11 => ['7.3', ['7.1', '7.2', '7.3']],
    12 => ['7.3', ['7.1', '7.2', '7.3']],
    13 => ['7.3', ['7.2', '7.3', '7.4']],
    14 => ['7.3', ['7.3', '7.4', '8.0']],
    15 => ['7.3', ['7.3', '7.4', '8.0']],
  ];
  if (!isset($supvers[$intver])) {
    $intver = 14;
  }
  return [$strver, $intver, $supvers[$intver]];
}

function find_service($desired, $others = []) {
  $result = shell_exec('tdocker ps --format json --status running');
  if (empty($result)) {
    return false;
  }
  $json = json_decode($result, true);
  $container = current(array_filter($json, function($c) use ($desired) {
    return $c['Service'] === $desired;
  }));
  if (empty($container)) {
    $container = current(array_filter($json, function($c) use ($others) {
      return in_array($c['Service'], $others);
    }));
  }
  return $container;
}

function start_php_service($php_ver, $php_sup_vers) {
  $php_ver = "php-{$php_ver}";
  $php_sup_vers = array_map(function($v) { return "php-{$v}"; }, $php_sup_vers);
  $check = function () use ($php_ver, $php_sup_vers) {
    $container = find_service($php_ver, $php_sup_vers);
    return $container ? $container['Service'] : false;
  };
  $result = $check();
  if (!$result) {
    passthru("tup {$php_ver}");
    $result = $check();
  }
  if (!$result) {
    bye("Cannot start {$php_ver}");
  }
  return $result;
}

function run_php_script($dirroot, $php_svc, $script) {
  global $SRC_ROOT;
  if (strncmp($dirroot, $SRC_ROOT, strlen($SRC_ROOT)) !== 0) {
    bye('dirroot must be in src directory');
  }
  $workdir = '/var/www/totara/src' . substr($dirroot, strlen($SRC_ROOT));
  $cmd = 'tdocker exec -w ' . escapeshellarg($workdir) . ' ' . escapeshellarg($php_svc) . ' php -r ' . escapeshellarg(implode(';', $script) . ';');
  if (empty($out = shell_exec($cmd))) {
    bye('Could not run php');
  }
  return $out;
}

function docker_cmd($action, ...$args) {
  $cmd = 'tdocker ' . escapeshellarg($action) . ' ' . array_reduce($args, function($carry, $arg) {
    return $carry . ' ' . escapeshellarg($arg);
  });
  // echo "$cmd\n";
  exec($cmd, $out, $ret);
  return $ret;
}

function get_db_config($dirroot, $php_svc) {
  $result = run_php_script($dirroot, $php_svc, [
    "define('CLI_SCRIPT',1)",
    "define('ABORT_AFTER_CONFIG',1)",
    "require('config.php')",
    'echo json_encode([$CFG->dataroot,$CFG->prefix,$CFG->dbtype,$CFG->dbhost,$CFG->dbname,$CFG->dbuser,$CFG->dbpass,$CFG->dboptions])'
  ]);
  $json = @json_decode($result, true);
  if (empty($json)) {
    bye('Could not get database config: ' . $result);
  }
  return $json;
}

function str_truncate($str, $len) {
  if (function_exists('mb_strimwidth')) {
    return mb_strimwidth($str, 0, $len, '...');
  }
  if (strlen($str) > $len) {
    return substr($str, 0, $len - 3) . '...';
  }
  return $str;
}

define('MOODLE_INTERNAL', 'wazzup?');

if ($argc < 2) {
  bye('Usage: ' . $argv[0] . ' <action>');
}
$action = $argv[1];
if (!in_array($action, ['create', 'recreate', 'drop', 'backup', 'restore', 'shell', 'save', 'load'])) {
  bye('Unknown action: ' . $action);
}
if ($action === 'save') {
  $action = 'backup';
} else if ($action === 'load') {
  $action = 'restore';
}
$more = $argc >= 3 ? $argv[2] : '';

$dirroot = get_dirroot();

[$totara_ver_str, $totara_ver, [$php_ver, $php_sup_vers]] = get_vers($dirroot);
if ($totara_ver < 14) {
  bye('Sorry legacy Totara is unsupported');
}

$php_svc = start_php_service($php_ver, $php_sup_vers);
[$dataroot, $prefix, $dbtype, $dbhost, $dbname, $dbuser, $dbpass, $dboptions] = get_db_config($dirroot, $php_svc);
if ($dbtype === 'mysqli') {
  $dbtype = 'mysql';
}

$db_svc = false;
if ($dbtype === 'pgsql') {
  $db_svc = 'pgsql12';
} else if ($dbtype === 'mysql') {
  $db_svc = 'mysql8';
} else {
  bye('Unsupported database type: ' . $dbtype);
}

if (!find_service($db_svc)) {
  passthru('tup ' . escapeshellarg($db_svc));
}

$backup_dir = $PRJ_ROOT . "/../backup/{$db_svc}";
@mkdir($backup_dir, 0700, true);
$backup_dir = realpath($backup_dir);

$shell = "/scripts/{$action}.sh";
if (in_array($action, ['create', 'drop', 'recreate'])) {
  $message = [
    'create' => 'Creating',
    'drop' => 'Dropping',
    'recreate' => 'Recreating',
  ];
  echo "{$message[$action]}... ";
  $ret = docker_cmd('exec', $db_svc, $shell, $dbname);
  if (!$ret) {
    echo " done\n";
  } else {
    bye('failed with error ' . $ret);
  }
} else if ($action === 'backup') {
  echo "Backing up... ";
  $ret = docker_cmd('exec', $db_svc, $shell, $dbname, $prefix);
  if (!$ret) {
    echo "done\n";
  } else {
    bye('failed with error ' . $ret);
  }
  if ($more !== '') {
    @unlink($more);
    $file = $more;
    $memo = false;
  } else {
    $time = time();
    $file = $backup_dir . '/' . date('YmdHis', $time) . '.dump';
    $i = 0;
    while (file_exists($file)) {
      $file = $backup_dir . '/' . date('YmdHis', $time) . '-' . (++$i) . '.dump';
    }
    $memo = substr($file, 0, -5) . '.json';
  }
  if (!docker_cmd('cp', $db_svc . ':/tmp/backup.dump', $file)) {
    if ($memo) {
      $notes = [
        'dbtype' => $dbtype,
        'dbname' => $dbname,
        'time' => date(DATE_ISO8601, $time),
        'version' => $totara_ver_str,
        'dirroot' => $dirroot,
      ];
      $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD') ?: '');
      $commit = trim(shell_exec('git rev-parse HEAD') ?: '');
      $message = trim(shell_exec('git log -1 --pretty=%B') ?: '');
      if ($branch || $commit || $message) {
        $notes['git'] = ['branch' => $branch, 'commit' => $commit, 'message' => $message];
      }
      file_put_contents($memo, json_encode($notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    echo "Saved backup to '{$file}'\n";
  } else {
    bye('Could not transfer backup');
  }
} else if ($action === 'restore') {
  if ($more !== '') {
    if (!file_exists($more)) {
      bye('File not found: ' . $more);
    }
    $file = $more;
  } else {
    $files = array_map(function($f) use ($backup_dir) {
      $path = $backup_dir . '/' . $f;
      $info = @json_decode(@file_get_contents(substr($path, 0, -5) . '.json'), true) ?: [];
      $version = $info['version'] ?? '';
      $dbname = $info['dbname'] ?? '';
      $time = '';
      if (isset($info['time'])) {
        $time = date('Y/m/d H:i:s', strtotime($info['time']));
      }
      $branch = $info['git']['branch'] ?? '';
      $commit = $info['git']['commit'] ?? '';
      $message = $info['git']['message'] ?? '';
      return [
        'name' => $f,
        'path' => $path,
        'version' => $version,
        'dbname' => $dbname,
        'time' => $time,
        'branch' => $branch,
        'commit' => $commit,
        'message' => $message,
      ];
    }, array_slice(array_filter(scandir($backup_dir, SCANDIR_SORT_DESCENDING), function($f) {
      return substr($f, -5) === '.dump';
    }), 0, 10));
    if (empty($files)) {
      bye('No backups found');
    }
    $lens = array_reduce($files, function ($lens, $f) {
      return (object)[
        'name' => min(max($lens->name, strlen($f['name'])), 20),
        'dbname' => min(max($lens->dbname, strlen($f['dbname'])), 16),
        'time' => max($lens->time, strlen($f['time'])),
        'branch' => min(max($lens->branch, strlen($f['branch'])), 16),
        'message' => min(max($lens->message, strlen($f['message'])), 64),
      ];
    }, (object)['name' => 4, 'dbname' => 6, 'time' => 4, 'branch' => 6, 'message' => 7]);
    $format = "%-{$lens->name}s  %-{$lens->dbname}s  %-{$lens->time}s  %-{$lens->branch}s  %-{$lens->message}s";
    printf("#  {$format}\n", 'file', 'dbname', 'time', 'branch', 'message');
    printf("-- {$format}\n",
      str_repeat('-', $lens->name),
      str_repeat('-', $lens->dbname),
      str_repeat('-', $lens->time),
      str_repeat('-', $lens->branch),
      str_repeat('-', $lens->message)
    );
    foreach ($files as $i => $f) {
      printf(
        "%d. {$format}\n",
        ($i + 1) % 10,
        str_truncate($f['name'], $lens->name),
        str_truncate($f['dbname'], $lens->dbname),
        $f['time'],
        str_truncate($f['branch'], $lens->branch),
        str_truncate($f['message'], $lens->message),
      );
    }
    printf('Select file number? (%d-%d) [1]: ', 1, count($files));
    $input = rtrim(fgets(STDIN), "\n");
    if ($input === '') {
      $input = 1;
    } else if ($input === '0' && count($files) === 10) {
      $input = 10;
    } else if ($input < 1 || $input > count($files)) {
      bye('Invalid selection');
    }
    $file = $files[$input - 1]['path'];
    if (empty($file)) {
      bye('No backup file found');
    }
  }
  echo "Restoring from '{$file}'... ";
  $ret = docker_cmd('cp', $file, $db_svc . ':/tmp/backup.dump');
  if ($ret) {
    bye('Could not transfer backup');
  }
  $ret = docker_cmd('exec', $db_svc, $shell, $dbname);
  if (!$ret) {
    echo "done\n";
  } else {
    bye('failed with error ' . $ret);
  }
} else if ($action === 'shell') {
  $container = find_service($db_svc);
  if (!$container) {
    passthru('tup ' . escapeshellarg($db_svc));
    $container = find_service($db_svc);
  }
  if (!$container) {
    bye("Cannot start {$db_svc}");
  }
  passthru('docker exec -it ' . escapeshellarg($container['ID']) . ' ' . escapeshellarg($shell) . ' ' . escapeshellarg($dbname));
}

// var_dump([$argc, $argv]);
// var_dump([$totara_ver, $php_ver, $php_sup_vers, $php_svc, $dataroot, $prefix, $dbtype, $dbhost, $dbname, $dbuser, $dbpass, $dboptions]);

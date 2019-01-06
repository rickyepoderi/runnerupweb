<?php

require __DIR__ . '/../bootstrap.php';

use runnerupweb\common\Client;

class WorkoutCommand {

    private $operation;

    public function __construct() { 
    }

    public function usage($error) {
        fwrite(STDERR, "$error\n");
        fwrite(STDERR, "USAGE: workout.php OPERATION OPTIONS\n");
        fwrite(STDERR, "OPERATION:\n");
        fwrite(STDERR, " - upload: uploads a file\n");
        fwrite(STDERR, " - search: seaches for workouts\n");
        fwrite(STDERR, " - help: show help for the command or operation\n");
        fwrite(STDERR, "Use help OPERATION for specific help of the operation\n");
        die(1);
    }

    public function usageUpload($error) {
        fwrite(STDERR, "$error\n");
        fwrite(STDERR, "USAGE: workout.php upload OPTIONS FILE...\n");
        fwrite(STDERR, "OPTIONS:\n");
        fwrite(STDERR, " -l --login LOGIN: username to login\n");
        fwrite(STDERR, " -p --password PASSWORD: password for the login\n");
        fwrite(STDERR, " -u --url URL: url to connect (default 'http://localhost/runnerupweb')\n");
        fwrite(STDERR, " -d --delete: delete the files after successful upload\n");
        fwrite(STDERR, " -m --move DIR: move the files to that directory after upload\n");
        die(1);
    }
    
    public function usageSearch($error) {
        fwrite(STDERR, "$error\n");
        fwrite(STDERR, "USAGE: workout.php upload OPTIONS FILE...\n");
        fwrite(STDERR, "OPTIONS:\n");
        fwrite(STDERR, " -l --login LOGIN: username to login\n");
        fwrite(STDERR, " -p --password PASSWORD: password for the login\n");
        fwrite(STDERR, " -u --url URL: url to connect (default 'http://localhost/runnerupweb')\n");
        fwrite(STDERR, " -s --start STARTTIME: start time in format YYYYMMDDHHmmssZ (compulsory parameter)\n");
        fwrite(STDERR, " -e --end ENDTIME: end time in format YYYYMMDDHHmmssZ\n");
        fwrite(STDERR, " -o --offset OFFSET: offset of the search \n");
        fwrite(STDERR, " -n --number NUMBER: number of results\n");
        die(1);
    }

    public function usageHelp($error) {
        fwrite(STDERR, "$error\n");
        fwrite(STDERR, "USAGE: workout.php help OPERATION...\n");
        die(1);
    }

    public function parseAndExecuteHelp($argv) {
        if (count($argv) != 3) {
            $this->usage('Invalid number of arguments for help');
        }
        switch ($argv[2]) {
            case 'help':
                $this->usageHelp('');
                break;
            case 'upload':
                $this->usageUpload('');
                break;
            default:
                $this->usage("Invalid operation for help $argv[2]");
        }
    }

    public function parseStringArgument($argv, $i) {
        if ($i + 1 < count($argv)) {
            return $argv[$i + 1];
        } else {
            $this->usageUpload("Invalid value for option " . $argv[i]);
        }
    }
    
    public function parseDateArgument($argv, $i) {
        if ($i + 1 < count($argv)) {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $argv[$i+1], new \DateTimeZone('UTC'));
            if ($date) {
                return $date;
            } else {
                $this->usageUpload("Invalid date value for option " . $argv[$i] . ": " . $argv[$i+1]);
            }
        } else {
            $this->usageUpload("Invalid value for option " . $argv[$i]);
        }
    }
    
    public function parseIntArgument($argv, $i) {
        if ($i + 1 < count($argv)) {
            if (is_numeric($argv[$i + 1])) {
                return intval($argv[$i + 1]);
            } else {
                $this->usageUpload("Invalid integer value for option " . $argv[$i] . ": " . $argv[$i+1]);
            }
        } else {
            $this->usageUpload("Invalid value for option " . $argv[$i]);
        }
    }

    function promptSilent($prompt = "Password: ") {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                    $vbscript, 'wscript.echo(InputBox("'
                    . addslashes($prompt)
                    . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
            return $password;
        } else {
            echo "Password: ";
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');
            return $password;
        }
    }

    public function executeUpload($url, $login, $password, $delete, $move, $files) {
        $c = new Client($url);
        $r = $c->login($login, $password);
        if (!$r->isSuccess()) {
            die($r->getErrorMessage() . "\n");
        }
        try {
            foreach($files as $file) {
                $realpath = realpath($file);
                $c->uploadWorkout($realpath);
                if ($delete) {
                   unlink($realpath);
                } else if ($move) {
                   rename($realpath, realpath($move) . '/' . pathinfo($realpath, PATHINFO_FILENAME) . '.' . pathinfo($realpath, PATHINFO_EXTENSION));
                }
            }
        } finally {
          $c->logout();  
        }
    }
    
    public function executeSearch($url, $login, $password, $start, $end, $offset, $limit) {
        $c = new Client($url);
        $r = $c->login($login, $password);
        if (!$r->isSuccess()) {
            die($r->getErrorMessage() . "\n");
        }
        try {
            $r = $c->searchWorkouts($start? $start->format('Y-m-d\TH:i:s\Z'):false, 
                    $end? $end->format('Y-m-d\TH:i:s\Z'):false, 
                    $offset, $limit);
            if (!$r->isSuccess()) {
                die($r->getErrorMessage() . "\n");
            }
            foreach ($r->getActivities() as $activity) {
               print(json_encode($activity->jsonSerialize(), JSON_PRETTY_PRINT) . "\n");
            }
        } finally {
          $c->logout();  
        }
    }

    public function parseAndExecuteUpload($argv) {
        $login = false;
        $password = false;
        $url = 'http://localhost/runnerupweb';
        $delete = false;
        $move = false;
        $files = array();
        $finish = false;

        for ($i = 2; $i < count($argv) && !$finish; $i++) {
            switch ($argv[$i]) {
                case "-l":
                case "--login":
                    $login = $this->parseStringArgument($argv, $i++);
                    break;
                case "-p":
                case "--password":
                    $password = $this->parseStringArgument($argv, $i++);
                    break;
                case "-u":
                case "--url":
                    $url = $this->parseStringArgument($argv, $i++);
                    if (!parse_url($url)) {
                        $this->usageUpload("Invalid url parameter: $url");
                    }
                    break;
                case "-d":
                case "--delete":
                    $delete = true;
                    break;
                case "-m":
                case "--move":
                    $move = $this->parseStringArgument($argv, $i++);
                    if (!is_dir($move) || !is_writable($move)) {
                        $this->usageUpload("Invalid move directory: $move");
                    }
                    break;
                default:
                    $i--;
                    $finish = true;
            }
        }
        for ($j = $i; $j < count($argv); $j++) {
            if (!is_file($argv[$j]) || !is_readable($argv[$j])) {
                $this->usageUpload("Invalid file: " . $argv[$j]);
            }
            $mime = mime_content_type($argv[$j]);
            if ($mime != 'text/xml' && $mime != 'application/xml') {
                $this->usageUpload("Invalid mime for file: " . $argv[$j]);
            }
            array_push($files, $argv[$j]);
        }
        if (count($files) == 0) {
            $this->usageUpload("Some TCX files should be passed");
        }
        if (!$login) {
            $login = readline("Login: ");
        }
        if (!$password) {
            $password = $this->promptSilent();
        }
        $this->executeUpload($url, $login, $password, $delete, $move, $files);
    }

    public function parseAndExecuteSearch($argv) {
        $login = false;
        $password = false;
        $url = 'http://localhost/runnerupweb';
        $start = false;
        $end = false;
        $offset = false;
        $limit = false;
        $finish = false;

        for ($i = 2; $i < count($argv) && !$finish; $i++) {
            switch ($argv[$i]) {
                case "-l":
                case "--login":
                    $login = $this->parseStringArgument($argv, $i++);
                    break;
                case "-p":
                case "--password":
                    $password = $this->parseStringArgument($argv, $i++);
                    break;
                case "-u":
                case "--url":
                    $url = $this->parseStringArgument($argv, $i++);
                    if (!parse_url($url)) {
                        $this->usageUpload("Invalid url parameter: $url");
                    }
                    break;
                case "-s":
                case "--start":
                    $start = $this->parseDateArgument($argv, $i++);
                    break;
                case "-e":
                case "--end":
                    $end = $this->parseDateArgument($argv, $i++);
                    break;
                case "-o":
                case "--offset":
                    $offset = $this->parseIntArgument($argv, $i++);
                    break;
                case "-n":
                case "--number":
                    $limit = $this->parseIntArgument($argv, $i++);
                    break;
                default:
                    $i--;
                    $finish = true;
            }
        }
        if (!$start) {
            $this->usageSearch('The start parameter is compulsory for search');
        }
        $this->executeSearch($url, $login, $password, $start, $end, $offset, $limit);
    }
    
    public function parseAndExecute($argv) {
        if (count($argv) == 1) {
            $this->usage('Operation argument not provided');
        }
        switch ($argv[1]) {
            case 'help':
                $this->operation = 'help';
                $this->parseAndExecuteHelp($argv);
                break;
            case 'upload':
                $this->operation = 'help';
                $this->parseAndExecuteUpload($argv);
                break;
            case 'search':
                $this->operation = 'search';
                $this->parseAndExecuteSearch($argv);
                break;
            default:
                $this->usage("Invalid operation $argv[1]");
        }
    }

    public function execute($argv) {
        $this->parseAndExecute($argv);
    }
}

$workout = new WorkoutCommand();
$workout->execute($argv);

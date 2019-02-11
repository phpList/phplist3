<?php
/**
 * Automatic updater for phpList 3
 * @author Xheni Myrtaj <xheni@phplist.com>
 *
 */

class UpdateException extends \Exception {}

class updater
{
    /** @var bool */
    private $availableUpdate = false;

    const DOWNLOAD_PATH = '../tmp_uploaded_update';
    const ELIGIBLE_SESSION_KEY = 'phplist_updater_eligible';

    private $excludedFiles = array(
        'dl.php',
        'index.php',
        'index.html',
        'lt.php',
        'ut.php',
    );

    public function isAuthenticated() {
        session_start();
        if(isset($_SESSION[self::ELIGIBLE_SESSION_KEY]) && $_SESSION[self::ELIGIBLE_SESSION_KEY] === true) {
            return true;
        }

        return false;
    }

    public function deauthUpdaterSession() {
        unset($_SESSION[self::ELIGIBLE_SESSION_KEY]);
        unlink(__DIR__ . '/../config/actions.txt');
    }

    /**
     * Return true if there is an update available
     * @return bool
     */
    public function availableUpdate()
    {
        return $this->availableUpdate;
    }

    /**
     * Returns current version of phpList.
     *
     * @return string
     * @throws UpdateException
     */
    public function getCurrentVersion()
    {
        $version = file_get_contents('../admin/init.php');
        $matches = array();
        preg_match_all('/define\(\"VERSION\",\"(.*)\"\);/', $version,$matches);

        if(isset($matches[1][0])) {
            return $matches[1][0];
        }

        throw new UpdateException('No production version found.');

    }

    /**
     * Checks if there is an Update Available
     * @return string
     * @throws \Exception
     */
    function checkIfThereIsAnUpdate()
    {
        $serverResponse = $this->getResponseFromServer();
        $version = isset($serverResponse['version']) ? $serverResponse['version'] : '';

        $versionString = isset($serverResponse['versionstring']) ? $serverResponse['versionstring'] : '';
        if ($version !== '' && $version !== $this->getCurrentVersion() && version_compare($this->getCurrentVersion(), $version)) {
            $this->availableUpdate = true;
            $updateMessage = 'Update to ' . htmlentities($versionString) . ' is available.  ';
        } else {
            $updateMessage = 'phpList is up-to-date.';
        }
        if ($this->availableUpdate && isset($serverResponse['autoupdater']) && !($serverResponse['autoupdater'] === 1 || $serverResponse['autoupdater'] === '1')) {
            $this->availableUpdate = false;
            $updateMessage .= '<br />The automatic updater is disabled for this update.';
        }

        return $updateMessage;

    }

    /**
     * Return version data from server
     * @return array
     * @throws \Exception
     */
    private function getResponseFromServer()
    {
        $serverUrl = "https://download.phplist.org/version.json";
        $updateUrl = $serverUrl.'?version='.$this->getCurrentVersion();

        // create a new cURL resource
        $ch = curl_init();
        // set URL and other appropriate options
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
        curl_setopt($ch, CURLOPT_URL, $updateUrl);
        // Execute
        $responseFromServer = curl_exec($ch);
        // Closing
        curl_close($ch);

        // decode json
        $responseFromServer = json_decode($responseFromServer, true);
        return $responseFromServer;
    }

    private function getDownloadUrl() {
        // todo: error handling
        $response = $this->getResponseFromServer();
        if(isset($response['url'])) {
            return $response['url'];
        }
        // todo error handling
    }


    /**
     * Checks write permissions and returns files that are not writable
     * @return array
     */
    function checkWritePermissions()
    {

        $directory = new \RecursiveDirectoryIterator(__DIR__ . '/../', \RecursiveDirectoryIterator::SKIP_DOTS); // Exclude dot files
        /** @var SplFileInfo[] $iterator */
        $iterator = new \RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        $files = array();
        foreach ($iterator as $info) {
            if (!is_writable($info->getRealPath())) {
                $files[] = $info->getRealPath();
            }
        }

        return $files;

    }

    /**
     * @return array
     */
    function checkRequiredFiles()
    {
        $expectedFiles = array(
            '.' => 1,
            '..' => 1,
            'admin' => 1,
            'config' => 1,
            'images' => 1,
            'js' => 1,
            'styles' => 1,
            'texts' => 1,
            '.htaccess' => 1,
            'dl.php' => 1,
            'index.html' => 1,
            'index.php' => 1,
            'lt.php' => 1,
            'ut.php' => 1,
            'updater'=>1,
        );

        $existingFiles = scandir(__DIR__ . '/../');

        foreach ($existingFiles as $fileName) {

            if (isset($expectedFiles[$fileName])) {
                unset($expectedFiles[$fileName]);
            } else {
                $expectedFiles[$fileName] = 1;
            }
        }

        return $expectedFiles;

    }

    /**
     *
     * Recursively delete a directory and all of it's contents
     *
     * @param string $dir absolute path to directory to delete
     * @return bool
     * @throws UpdateException
     */

    private function rmdir_recursive($dir)
    {

        if (false === file_exists($dir)) {
            throw new \UpdateException("$dir doesn't exist.");
        }

        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                if (false === rmdir($fileinfo->getRealPath())) {
                    throw new \UpdateException("Could not delete $fileinfo");
                }
            } else {
                if (false === unlink($fileinfo->getRealPath())) {
                    throw new \UpdateException("Could not delete $fileinfo");
                }
            }
        }
        return rmdir($dir);
    }

    /**
     * Delete dirs/files except config and other files that we want to keep
     * @throws UpdateException
     */

    function deleteFiles()
    {

        $excludedFolders = array(
            'config',
            'tmp_uploaded_update',
            'updater',
            '.',
            '..',
        );

        $filesTodelete = scandir(__DIR__ . '/../');

        foreach ($filesTodelete as $fileName) {
            $absolutePath = __DIR__ . '/../' . $fileName;
            $is_dir = false;
            if (is_dir($absolutePath)) {
                $is_dir = true;
                if (in_array($fileName, $excludedFolders)) {
                    continue;
                }

            } else if (is_file($absolutePath)) {
                if (in_array($fileName, $this->excludedFiles)) {
                    continue;
                }

            }


            if ($is_dir) {
                $this->rmdir_recursive($absolutePath);
            } else {
                unlink($absolutePath);
            }
        }

    }

    /**
     * Get a PDO connection
     * @return PDO
     * @throws UpdateException
     */
    function getConnection()
    {
        $standardConfig = __DIR__ . '/../config/config.php';

        if (isset($_SERVER['ConfigFile']) && is_file($_SERVER['ConfigFile'])) {
            include $_SERVER['ConfigFile'];

        } elseif (file_exists($standardConfig)) {
            include $standardConfig;
        } else {
            throw new \UpdateException("Cannot find config file");
        }

        $charset = 'utf8mb4';

        /** @var string $database_host
         * @var string $database_name
         * @var string $database_user
         * @var string $database_password
         */

        $dsn = "mysql:host=$database_host;dbname=$database_name;charset=$charset";
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        );
        try {
            $pdo = new PDO($dsn, $database_user, $database_password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
        return $pdo;
    }

    /**
     *Set the maintenance mode
     * @return bool true - maintenance mode is set; false - maintenance mode could not be set because an update is already running
     */
    function addMaintenanceMode()
    {
        $prepStmt = $this->getConnection()->prepare("SELECT * FROM phplist_config WHERE item=?");
        $prepStmt->execute(array('update_in_progress'));
        $result = $prepStmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            // the row does not exist => no update running
            $this->getConnection()
                ->prepare('INSERT INTO phplist_config(`item`,`editable`,`value`) VALUES (?,0,?)')
                ->execute(array('update_in_progress', 1));
        }
        if ($result['update_in_progress'] == 0) {
            $this->getConnection()
                ->prepare('UPDATE phplist_config SET `value`=? WHERE `item`=?')
                ->execute(array(1, 'update_in_progress'));

        } else {
            // the row exists and is not 0 => there is an update running
            return false;
        }
        $name = 'maintenancemode';
        $value = "Update process";
        $sql = "UPDATE phplist_config SET value =?, editable =? where item =? ";
        $this->getConnection()->prepare($sql)->execute(array($value, 0, $name));

    }

    /**
     *Clear the maintenance mode and remove the update_in_progress lock
     */
    function removeMaintenanceMode()
    {
        $name = 'maintenancemode';
        $value = '';
        $sql = "UPDATE phplist_config SET value =?, editable =? where item =? ";
        $this->getConnection()->prepare($sql)->execute(array($value, 0, $name));

        $this->getConnection()
            ->prepare('UPDATE phplist_config SET `value`=? WHERE `item`=?')
            ->execute(array(0, "update_in_progress"));

    }

    /**
     * Download and unzip phpList from remote server
     *
     * @throws UpdateException
     */
    function downloadUpdate()
    {
        /** @var string $url */
        $url = $this->getDownloadUrl();
        $zipFile = tempnam(sys_get_temp_dir(), 'phplist-update');
        if ($zipFile === false) {
            throw new UpdateException("Temporary file cannot be created");
        }
        // Get The Zip File From Server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FILE, fopen($zipFile, 'w+'));
        $page = curl_exec($ch);
        if (!$page) {
            echo "Error :- " . curl_error($ch);
        }
        curl_close($ch);

        // extract files
        $this->unZipFiles($zipFile, self::DOWNLOAD_PATH);

    }

    /**
     * Creates temporary dir
     * @throws UpdateException
     */
    function temp_dir()
    {

        $tempdir = mkdir(self::DOWNLOAD_PATH, 0700);
        if ($tempdir === false) {
            throw new UpdateException("Could not create temporary file");
        }
    }


    function cleanUp()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * @throws UpdateException
     */
    function replacePHPEntryPoints()
    {
        $entryPoints = array(
            'dl.php',
            'index.html',
            'index.php',
            'lt.php',
            'ut.php',
        );

        foreach ($entryPoints as $key => $fileName) {
            $current = "Update in progress \n";
            $content = file_put_contents(__DIR__.'/../'.$fileName, $current);
            if ($content === FALSE) {
                throw new UpdateException("Could not write to the $fileName");
            }
        }

    }

    /**
     * Returns true if the file/dir is excluded otherwise false.
     * @param $file
     * @return bool
     */
    function isExcluded($file)
    {

        $excludedFolders = array(
            'config',
            'tmp_uploaded_update',
            'updater',
            '.',
            '..',
        );


        if (in_array($file, $excludedFolders)) {
            return true;
        } else if (in_array($file, $this->excludedFiles)) {
            return true;
        }
        return false;
    }

    /**
     * Move new files in place.
     * @throws UpdateException
     */
    function moveNewFiles()
    {
        $rootDir = __DIR__ . '/../tmp_uploaded_update/phplist/public_html/lists';
        $downloadedFiles = scandir($rootDir);
        if (count($downloadedFiles) <= 2) {
            throw new UpdateException("Download folder is empty!");
        }

        foreach ($downloadedFiles as $fileName) {
            if ($this->isExcluded($fileName)) {
                continue;
            }
            $oldFile = $rootDir . '/' . $fileName;
            $newFile = __DIR__ . '/../' . $fileName;
            $state = rename($oldFile, $newFile);
            if ($state === false) {
                throw new UpdateException("Could not move new files");
            }
        }
    }

    /**
     * Move entry points in place.
     */
    function moveEntryPHPpoints()
    {
        $rootDir = __DIR__ . '/../tmp_uploaded_update/phplist/public_html/lists';
        $downloadedFiles = scandir($rootDir);

        foreach ($downloadedFiles as $filename) {
            $oldFile = $rootDir . '/' . $filename;
            $newFile = __DIR__ . '/../' . $filename;
            if (in_array($filename, $this->excludedFiles)) {
                rename($oldFile, $newFile);
            }
        }

    }


    /**
     *  Back up old files to the location specified by the user.
     * @param $destination 'path' to backup zip
     * @throws UpdateException
     */
    function backUpFiles($destination) {
        $iterator = new \RecursiveDirectoryIterator(realpath(__DIR__ . '/../'), FilesystemIterator::SKIP_DOTS);
        /** @var SplFileInfo[] $iterator */
        /** @var  $iterator */
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

        $zip = new ZipArchive();
        $resZip= $zip->open($destination, ZipArchive::CREATE);
        if($resZip === false){
            throw new \UpdateException("Error: Could not create back up of phpList directory. Please make sure that the argument is valid or writable and try again without reloading the page.");
        }
        $zip->addEmptyDir('lists');

        foreach ($iterator as $file)  {
            $prefix = realpath( __DIR__ . '/../');
            $name = 'lists/'.substr($file->getRealPath(), strlen($prefix) + 1);
            if($file->isDir()) {
                $zip->addEmptyDir($name);
                continue;
            }
            if($file->isFile()) {
                $zip->addFromString($name, file_get_contents($file->getRealPath()));
                continue;
            }
        }
        $state = $zip->close();
        if($state === false) {
            throw new UpdateException('Error: Could not create back up of phpList directory. Please make sure that the argument is valid or is writable and try again without reloading the page.');
        }

    }

    /**
     * Extract Zip Files
     * @param string $toBeExtracted
     * @param string $extractPath
     * @throws UpdateException
     */
    function unZipFiles($toBeExtracted, $extractPath)
    {
        $zip = new ZipArchive;

        /* Open the Zip file */
        if ($zip->open($toBeExtracted) !== true) {
            throw new \UpdateException("Unable to open the Zip File");
        }
        /* Extract Zip File */
        $zip->extractTo($extractPath);
        $zip->close();

    }

    /**
     * Delete temporary downloaded files
     * @throws UpdateException
     */
    function deleteTemporaryFiles() {
        $isTempDirDeleted = $this->rmdir_recursive(self::DOWNLOAD_PATH);
        if($isTempDirDeleted===false){
            throw new \UpdateException("Could not delete temporary files!");
        }

    }

    /**
     * @throws UpdateException
     */
    function recoverFiles()
    {
        $this->unZipFiles('backup.zip', self::DOWNLOAD_PATH);
    }

    /**
     * @param int $action
     * @throws UpdateException
     */
    function writeActions($action)
    {
        $actionsdir = __DIR__ . '/../config/actions.txt';
        if (!file_exists($actionsdir)) {
            $actionsFile = fopen($actionsdir, "w+");
            if ($actionsFile === false) {
                throw new \UpdateException("Could not create actions file in the config directory, please change permissions");
            }
        }
        $written = file_put_contents($actionsdir, json_encode(array('continue'=>false, 'step'=>$action)));
        if($written === false){
            throw new \UpdateException("Could not write on $actionsdir");
        }
    }

    /**
     * Return the current step
     * @return mixed array of json data
     * @throws UpdateException
     */
    function currentUpdateStep(){
        $actionsdir = __DIR__ . '/../config/actions.txt';
        if (file_exists($actionsdir)) {
            $status= file_get_contents($actionsdir);
            if($status===false){
                throw new \UpdateException( "Cannot read content from $actionsdir");
            }
            $decodedJson = json_decode($status,true);
            if (!is_array($decodedJson)) {
                throw new \UpdateException('JSON data cannot be decoded!');
            }

        } else {
            return array('step'=>0,'continue'=>true);
        }
        return $decodedJson;

    }

    /**
     * Check if config folder is writable. Required to be writable in order to write steps.
     */
    function checkConfig(){
        $configdir = __DIR__ . '/../config/';
        if (!is_dir($configdir) || !is_writable($configdir)){
            die("Cannot update because config directory is not writable.");
        }
    }

    /**
     * Check if required php modules are installed.
     */
    function checkphpmodules()
    {

        $phpmodules = array('curl', 'pdo', 'zip');
        $notinstalled = array();

        foreach ($phpmodules as $value){
            if (!extension_loaded($value)) {
                array_push($notinstalled, $value);
            }
        }
        if (count($notinstalled)>0){
            $message = "The following php modules are required. Please install them to continue.".'<br>';
            foreach ($notinstalled as $value){
                $message .= $value.'<br>';
            }
            die($message);
        }
    }

    /**
     * Update updater to a new location before temp folder is deleted!
     * @throws UpdateException
     */
    function moveUpdater(){
        $rootDir = __DIR__ . '/../tmp_uploaded_update/phplist/public_html/lists';
        $oldFile = $rootDir . '/updater';
        $newFile = __DIR__ . '/../tempupdater';
        $state = rename($oldFile, $newFile);
        if ($state === false) {
            throw new UpdateException("Could not move updater");
        }
    }

    /**
     * Replace new updater as the final step
     * @throws UpdateException
     */
    function replaceNewUpdater() {
        $newUpdater = realpath(__DIR__ . '/../tempupdater');
        $oldUpdater = realpath(__DIR__ . '/../updater');

        $this->rmdir_recursive($oldUpdater);
        $state = rename($newUpdater, $oldUpdater);
        if ($state === false) {
            throw new UpdateException("Could not move the new updater in place");
        }
    }
}

try {
    $update = new updater();
    if(!$update->isAuthenticated()) {
        die('No permission to access updater.');
    }
    $update->checkConfig();
    $update->checkphpmodules();

} catch (\UpdateException $e) {
    throw $e;
}

/**
 *
 *
 *
 */
if(isset($_POST['action'])) {
    set_time_limit(0);

    //ensure that $action is integer

    $action = (int)$_POST['action'];

    header('Content-Type: application/json');
    $writeStep = true;
    switch ($action) {
        case 0:
            $statusJson= $update->currentUpdateStep();
            echo json_encode(array('status' => $statusJson,'autocontinue'=>true ));
            break;
        case 1:
            $currentVersion = $update->getCurrentVersion();
            $updateMessage= $update->checkIfThereIsAnUpdate();
            $isThereAnUpdate = $update->availableUpdate();
            if($isThereAnUpdate === false){
                echo(json_encode(array('continue' => false, 'response' => $updateMessage)));
            } else {
                echo(json_encode(array('continue' => true, 'response' => $updateMessage)));
            }
            break;
        case 2:
            echo(json_encode(array('continue' => true, 'autocontinue' => true, 'response' => 'Starting integrity check')));
            break;
        case 3:
            $unexpectedFiles = $update->checkRequiredFiles();
            if(count($unexpectedFiles) !== 0) {
                $elements = "The following files are not expected or required. To continue please move or delete them. \n";;
                foreach ($unexpectedFiles as $key=>$fileName){
                    $elements.=$key."\n";
                }
                echo(json_encode(array('retry' => true, 'continue' => false, 'response' => $elements)));
            } else {
                echo(json_encode(array('continue' => true, 'response'=>'Integrity check successful', 'autocontinue'=>true)));
            }
            break;
        case 4:
            $notWriteableFiles = $update->checkWritePermissions();
            if(count($notWriteableFiles) !== 0) {
                $notWriteableElements = "No write permission for the following files: \n";;
                foreach ($notWriteableFiles as $key=>$fileName){
                    $notWriteableElements.=$fileName."\n";
                }
                echo(json_encode(array('retry' => true, 'continue' => false, 'response' => $notWriteableElements)));
            } else {
                echo(json_encode(array('continue' => true,'response' => 'Write check successful.', 'autocontinue'=>true)));
            }
            break;
        case 5:
            echo(json_encode(array('continue' => true, 'response' => 'Do you want a backup? <form><input type="radio" name="create_backup" value="true">Yes<br><input type="radio" name="create_backup" value="false" checked>No</form>')));
            break;
        case 6:
            $createBackup = $_POST['create_backup'];
            if($createBackup === 'true') {
                echo(json_encode(array('continue' => true, 'response' => 'Choose location where to backup the /lists directory. Please make sure to choose a location outside the web root:<br> <form onsubmit="return false;"><input type="text" id="backuplocation" size="55" name="backup_location" placeholder="/var/backup.zip" /></form>')));
            } else {
                echo(json_encode(array('continue' => true, 'response' => '', 'autocontinue'=>true)));
            }
            break;
        case 7:
            $createBackup = $_POST['create_backup'];
            if($createBackup === 'true') {
                $backupLocation = realpath(dirname($_POST['backup_location']));
                $phplistRootFolder = realpath(__DIR__ . '/../../');
                if(strpos($backupLocation, $phplistRootFolder) === 0) {
                    echo(json_encode(array('retry' => true, 'continue' => false, 'response' => 'Please choose a folder outside of your phpList installation.')));
                    break;
                }
                if (!preg_match("/^.*\.(zip)$/i", $_POST['backup_location'])) {
                    echo(json_encode(array('retry' => true, 'continue' => false, 'response' => 'Please add .zip extension.')));
                    break;
                }
                try {
                    $update->backUpFiles($_POST['backup_location']);
                    echo(json_encode(array('continue' => true, 'response' => 'Backup has been created')));
                } catch (\Exception $e) {
                    echo(json_encode(array('retry' => true, 'continue' => false, 'response' => $e->getMessage())));
                    break;
                }
            } else {
                echo(json_encode(array('continue' => true,'response'=>'No back up created', 'autocontinue'=>true)));
            }

            break;
        case 8:
            echo(json_encode(array('continue' => true, 'autocontinue' => true, 'response' =>'Download in progress')));
            break;
        case 9:
            try {
                $update->downloadUpdate();
                echo(json_encode(array('continue' => true, 'response' =>'The update has been downloaded!')));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 10:
            $on = $update->addMaintenanceMode();
            if($on===false){
                echo(json_encode(array('continue' => false, 'response' => 'Cannot set the maintenance mode on!')));
            } else {
                echo(json_encode(array('continue' => true,'response'=> 'Set maintenance mode on', 'autocontinue'=>true)));
            }
            break;
        case 11:
            try {
                $update->replacePHPEntryPoints();
                echo(json_encode(array('continue' => true,'response'=>'Replaced entry points', 'autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 12:
            try {
                $update->deleteFiles();
                echo(json_encode(array('continue' => true,'response'=>'Old files have been deleted!','autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 13:
            try {
                $update->moveNewFiles();
                echo(json_encode(array('continue' => true, 'response' =>'Moved new files in place!','autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 14:
            try {
                $update->moveEntryPHPpoints();
                echo(json_encode(array('continue' => true,'response'=>'Moved new entry points in place!','autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 15:
            try {
                $update->moveUpdater();
                echo(json_encode(array('continue' => true,'response'=>'Moved new entry points in place!','autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 16:
            try {
                $update->deleteTemporaryFiles();
                echo(json_encode(array('continue' => true, 'response'=>'Deleted temporary files!','autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 17:
            try {
                $update->removeMaintenanceMode();
                echo(json_encode(array('continue' => true, 'response'=>'Removed maintenance mode', 'autocontinue'=>true)));
            } catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
        case 18:
            $writeStep = false;
            try {
                $update->replaceNewUpdater();
                $update->deauthUpdaterSession();
                echo(json_encode(array('continue' => true, 'nextUrl' => '../admin/', 'response' => 'Updated successfully.')));
            }catch (\Exception $e) {
                echo(json_encode(array('continue' => false, 'response' => $e->getMessage())));
            }
            break;
    };

    if($writeStep) {
        try {
            $update->writeActions($action - 1);
        } catch(\Exception $e){

        }
    }
}else{
    ?>

    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">

        <style>
            /* http://meyerweb.com/eric/tools/css/reset/
               v2.0 | 20110126
               License: none (public domain)
            */
            html, body, div, span, applet, object, iframe,
            h1, h2, h3, h4, h5, h6, p, blockquote, pre,
            a, abbr, acronym, address, big, cite, code,
            del, dfn, em, img, ins, kbd, q, s, samp,
            small, strike, strong, sub, sup, tt, var,
            b, u, i, center,
            dl, dt, dd, ol, ul, li,
            fieldset, form, label, legend,
            table, caption, tbody, tfoot, thead, tr, th, td,
            article, aside, canvas, details, embed,
            figure, figcaption, footer, header, hgroup,
            menu, nav, output, ruby, section, summary,
            time, mark, audio, video {
                margin: 0;
                padding: 0;
                border: 0;
                font-size: 100%;
                font: inherit;
                vertical-align: baseline;
            }
            /* HTML5 display-role reset for older browsers */
            article, aside, details, figcaption, figure,
            footer, header, hgroup, menu, nav, section {
                display: block;
            }
            body {
                line-height: 1;
            }
            ol, ul {
                list-style: none;
            }
            blockquote, q {
                quotes: none;
            }
            blockquote:before, blockquote:after,
            q:before, q:after {
                content: '';
                content: none;
            }
            table {
                border-collapse: collapse;
                border-spacing: 0;
            }
            /** phpList CSS **/
            body {
                background-color: #eeeeee45;
                font-family: 'Source Sans Pro', sans-serif;
                margin-top: 50px;
            }
            button {
                background-color: #F29D71;
                color: white;
                border-radius: 55px;
                height: 40px;
                padding-left: 30px;
                padding-right: 30px;
                font-size: 15px;
                text-transform: uppercase;
                margin-top: 20px;
                border: none;
            }
            button:disabled {
                background-color: lightgrey !important;
            }
            .right {
                float: right;
            }
            @media only screen and (min-width: 1200px) {
                #center {
                    margin: auto;
                    width: 70%;
                }
            }
            @media only screen and (max-width: 350px) {
                #steps {
                    width: 100% !important;
                }
            }
            @media only screen and (max-width: 800px) {
                #center {
                    width: 100%;
                }
                .divider {
                    visibility: hidden;
                }
            }
            @media only screen and (min-width: 800px) and (max-width: 1200px) {
                #center {
                    margin: auto;
                    width: 90%;
                }
            }
			@media only screen and (min-width: 1200px) and (max-width: 1400px) {

				 #center {
				 margin: auto;
                 max-width: 75%;
				 }

				 #display {
                 max-width: 70%;
                 margin: 0 auto;
				 }
            }
			
            #display {
                background-color: white;
                padding-left: 20px;
                padding-top: 20px;
                padding-bottom: 20px;
                border-radius: 20px;
            }
            #logo {
                color: #8C8C8C;
                font-size: 20px;
                text-align: center;
                padding-bottom: 50px;
                cursor: pointer;
            }
            #logo img {
                margin-bottom: 20px;
            }
            #logo h1 {
                padding-top: 15px;
            }
            #steps h2 {
                font-size: 15px;
                color: #8C8C8C;
                width: 50px;
                text-align: center;
                padding-left: 10px;
            }
            #steps {
                width: 80%;
                margin: auto;
                padding-bottom: 30px;
            }
            #first-step {
                width: calc((25% - 70px)/2) !important;
                float: left;
                height: 1px;
            }
            .step {
                width: 25%;
                float: left;
            }
            .last-step {
                width: 70px;
            }
            .step-image {
                width: 64px;
                height: 64px;
                border-radius: 100px;
                border: 1px solid #8C8C8C;
                margin-bottom: 20px;
                float: left;
            }
            .step-image svg {
                width: 50%;
                padding-top: 25%;
                padding-left: 25%;
            }
            .active {
                background-color: #56A3D2;
                border: 0;
            }
            .active svg path {
                fill: white;
            }
            .clear {
                clear: both;
            }
            .divider {
                border: 0.3px solid #8C8C8C;
                width: inherit;
                margin-top: 30px;
            }
            .hidden {
                display: none;
            }
        </style>
    </head>
    <body>

    <div id="center">
        <div id="logo" title="Go back to phpList dashboard" onclick="location.href='../admin';">
            <svg width="47.055mm" height="14.361mm" version="1.1" viewBox="0 0 166.73201 50.884" xmlns="http://www.w3.org/2000/svg" >
                <g transform="translate(-199.83 -209.59)" fill="#8C8C8C">
                    <path transform="matrix(.9375 0 0 .9375 199.83 209.59)" d="m27.139 0a27.138 27.138 0 0 0 -22.072 11.385l17.771 17.951 6.543-6.5176-3.7148-3.7109-0.064454-0.083984c-0.83947-1.1541-1.2461-2.4403-1.2461-3.9336 0-3.7963 3.0896-6.8848 6.8848-6.8848s6.8828 3.0885 6.8828 6.8848c0 1.6395-0.55599 3.1158-1.6504 4.3926l-0.070312 0.076172-3.2715 3.2617 17.648 17.611a27.138 27.138 0 0 0 3.4961 -13.293 27.138 27.138 0 0 0 -27.137 -27.139zm4.1035 10.855c-2.3371 0-4.2383 1.9003-4.2383 4.2363 0.001067 0.89067 0.21941 1.6238 0.68555 2.2969l3.5684 3.5625 3.2383-3.2285c0.66027-0.784 0.98047-1.6442 0.98047-2.6309 0-2.336-1.8973-4.2363-4.2344-4.2363zm-27.658 2.8438a27.138 27.138 0 0 0 -3.584 13.439 27.138 27.138 0 0 0 27.139 27.137 27.138 27.138 0 0 0 22.23 -11.594l-18.113-17.992-6.5527 6.5273 3.5117 3.5547c0.94187 1.232 1.4395 2.6647 1.4395 4.1484-0.001067 3.7952-3.0896 6.8848-6.8848 6.8848-3.7963 0-6.8848-3.0885-6.8848-6.8848 0-1.2864 0.34507-2.5299 1-3.5977l0.082031-0.13477 3.998-3.9824-17.381-17.506zm19.248 19.385l-3.7637 3.748c-0.35093 0.62293-0.53516 1.3402-0.53516 2.0879 0 2.3339 1.9003 4.2363 4.2363 4.2363s4.2402-1.9003 4.2402-4.2363c0-0.88533-0.28766-1.7151-0.84766-2.4746l-3.3301-3.3613z" stroke-width="1.0667"/>
                    <path d="m263.24 229.86c1.53-1.693 2.958-2.438 4.997-2.438 5.236 0 7.921 4.556 7.921 9.043s-2.754 9.315-7.921 9.281c-2.144 0-3.671-0.714-4.997-2.176v7.955h-3.06v-23.627h3.06zm4.997 13.132c2.992 0 4.726-3.06 4.726-6.459 0-3.332-1.698-6.323-4.726-6.323-6.969 0-6.969 12.782 0 12.782z"/>
                    <path d="m282.11 229.86c1.122-2.057 2.89-2.71 4.896-2.71 4.861 0 6.527 3.468 6.527 7.445v10.403h-3.06v-10.403c0-2.55-0.852-4.691-3.47-4.691-2.992 0-4.896 1.802-4.896 4.691v10.403h-3.062v-24.546h3.062z"/>
                    <path d="m300.24 229.86c1.527-1.693 2.957-2.438 4.997-2.438 5.233 0 7.922 4.556 7.922 9.043s-2.754 9.315-7.922 9.281c-2.144 0-3.672-0.714-4.997-2.176v7.955h-3.062v-23.627h3.062zm4.997 13.132c2.99 0 4.726-3.06 4.726-6.459 0-3.332-1.7-6.323-4.726-6.323-6.969 0-6.969 12.782 0 12.782z"/>
                    <path d="m316.81 245v-24.546h3.229v21.622h12.341v2.924z"/>
                    <path d="m334.68 223.88v-3.434h3.4v3.434zm0.17 21.112v-17.372h3.061v17.372z"/>
                    <path d="m340.85 239.01h3.195c0.17 2.584 1.77 3.773 3.738 3.773 2.006 0 3.943-0.918 3.943-2.754 0-0.884-0.512-1.428-1.395-1.835-0.477-0.204-1.02-0.374-1.633-0.545-3.16-0.781-7.785-1.121-7.785-5.371 0-3.808 3.469-4.861 6.562-4.861 3.363 0 6.936 1.462 6.936 6.392h-3.229c0-2.958-1.904-3.672-3.705-3.672-1.734 0-3.332 0.646-3.332 2.142 0 0.714 0.439 1.156 1.395 1.53 0.477 0.204 1.055 0.374 1.664 0.51 0.613 0.136 1.293 0.306 1.975 0.441 2.686 0.646 5.812 1.666 5.812 5.27 0 3.944-3.807 5.474-7.139 5.474-3.5-1e-3 -6.934-2.074-7.002-6.494z"/>
                    <path d="m359.39 240.17v-9.689h-3.398v-2.55h3.398v-4.521h3.062v4.521h4.113v2.55h-4.113v9.689c0 1.224-0.035 2.753 1.562 2.753 0.309 0 0.613-0.067 0.953-0.102 0.34-0.068 0.682-0.136 1.6-0.238v2.516c-1.09 0.272-1.805 0.408-2.584 0.408-4.253 0-4.593-2.21-4.593-5.337z"/>
                </g>
            </svg>
            <h1>Updating phpList to the latest version</h1>
        </div>
        <div id="steps">
            <div id="first-step"> </div>
            <div class="step">
                <div class="step-image active">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27.482 25.403">
                        <defs>
                            <style>
                                .cls-1 {
                                    fill: #8a9798;
                                }
                            </style>
                        </defs>
                        <g id="initialize" data-name="Integrity check" transform="translate(0 -7.524)">
                            <g id="Group_211" data-name="Group 211" transform="translate(0 7.524)">
                                <path id="Path_218" data-name="Path 218" class="cls-1" d="M23.808,16.226v-.994l3.674-.171V13.218l-3.674-.171v-.765H20.935V8.911A1.388,1.388,0,0,0,19.55,7.524H7.932A1.388,1.388,0,0,0,6.546,8.911v3.372H3.674v.765L0,13.218v1.844l3.674.171v.994H6.546v2.142H3.674v.763L0,19.3v1.843l3.674.172v.994H6.546v2.372H3.674v.765L0,25.619v1.843l3.674.171v.994H6.546v2.914a1.387,1.387,0,0,0,1.386,1.385H19.55a1.386,1.386,0,0,0,1.385-1.385V28.627h2.873v-.994l3.674-.171V25.619l-3.674-.171v-.765H20.935V22.311h2.873v-.994l3.674-.172V19.3l-3.674-.171v-.763H20.935V16.226Zm2.946,10.087v.452l-3.674.171v.96H21.05V25.412h2.03v.732ZM23.08,20.625v.959H21.05V19.1h2.03v.732L26.754,20v.454Zm3.674-6.71v.453l-3.674.171v.96H21.05V13.011h2.03v.732ZM19.55,32.2H7.932a.658.658,0,0,1-.657-.656V8.911a.658.658,0,0,1,.657-.658H19.55a.658.658,0,0,1,.656.658v22.63A.657.657,0,0,1,19.55,32.2ZM.728,26.766v-.452L4.4,26.143v-.732h2.03V27.9H4.4v-.96Zm0-6.313V20L4.4,19.828V19.1h2.03v2.486H4.4v-.959Zm0-6.086v-.453L4.4,13.743v-.732h2.03V15.5H4.4v-.96Z" transform="translate(0 -7.524)"/>
                                <circle id="Ellipse_53" data-name="Ellipse 53" class="cls-1" cx="0.9" cy="0.9" r="0.9" transform="translate(8.193 1.813)"/>
                                <circle id="Ellipse_54" data-name="Ellipse 54" class="cls-1" cx="0.9" cy="0.9" r="0.9" transform="translate(8.193 5.813)"/>
                                <circle id="Ellipse_55" data-name="Ellipse 55" class="cls-1" cx="0.9" cy="0.9" r="0.9" transform="translate(17.193 21.813)"/>
                            </g>
                        </g>
                    </svg>
                </div>
                <hr class="divider" />
                <div class="clear"></div>
                <h2>Initialize</h2>
            </div>
            <div class="step">
                <div class="step-image ">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27.337 27.637">
                        <defs>
                            <style>
                                .cls-1 {
                                    fill: #8a9798;
                                    fill-rule: evenodd;
                                }
                            </style>
                        </defs>
                        <g id="back-up" data-name="Back up files" transform="translate(0 0.001)">
                            <path id="Path_214" data-name="Path 214" class="cls-1" d="M48.3,15.4c.01-.012.019-.025.028-.038v0c.008-.013.016-.026.023-.039l.006-.012.015-.032.005-.012c.006-.015.011-.03.016-.045v0c0-.014.008-.029.011-.044l0-.013c0-.012,0-.024.005-.035s0-.009,0-.014,0-.032,0-.048V8.615c0-.016,0-.032,0-.048s0-.009,0-.014,0-.024-.005-.036l0-.013c0-.015-.007-.03-.011-.044v0c0-.015-.01-.03-.016-.045L48.37,8.4l-.015-.032-.006-.012c-.007-.013-.015-.026-.023-.039v0c-.009-.013-.018-.026-.028-.038l-.009-.011-.024-.027-.006-.006L40.181.157a.54.54,0,0,0-.763,0L29.946,9.629H26.669a.54.54,0,1,0,0,1.08h2.2l-2.983,2.983H22.821a.54.54,0,1,0,0,1.08H24.8l-.642.642a.54.54,0,0,0,0,.763l1.578,1.578H23.777a.54.54,0,1,0,0,1.08h3.042L29.8,21.817H26.669a.54.54,0,1,0,0,1.08h4.212l4.581,4.581a.54.54,0,0,0,.763,0L48.257,15.447l.005-.006.025-.027L48.3,15.4ZM45.413,11.84l1.922-1.922v3.845ZM35.844,26.333,32.408,22.9h1.643a.54.54,0,0,0,0-1.08H31.329l-2.983-2.983h1.371a.54.54,0,1,0,0-1.08H27.266L25.306,15.8l1.024-1.024h5.791a.54.54,0,1,0,0-1.08H27.41l2.983-2.983h6.459a.54.54,0,0,0,0-1.08h-5.38L39.8,1.3l7.312,7.312-2.844,2.844a.54.54,0,0,0,0,.763l2.844,2.844Zm0,0" transform="translate(-21.078)"/>
                            <path id="Path_215" data-name="Path 215" class="cls-1" d="M133.485,104.217h2.646a.54.54,0,1,0,0-1.08h-2.646a.54.54,0,1,0,0,1.08Zm0,0" transform="translate(-125.769 -97.571)"/>
                            <path id="Path_216" data-name="Path 216" class="cls-1" d="M54.122,179.482a.54.54,0,1,0-.54-.54A.541.541,0,0,0,54.122,179.482Zm0,0" transform="translate(-50.69 -168.773)"/>
                            <path id="Path_217" data-name="Path 217" class="cls-1" d="M.54,328.934a.54.54,0,1,0,.54.54A.541.541,0,0,0,.54,328.934Zm0,0" transform="translate(0 -311.179)"/>
                        </g>
                    </svg>

                </div>
                <hr class="divider" />
                <div class="clear"></div>
                <h2>Back Up</h2>
            </div>
            <div class="step">
                <div class="step-image">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23.015 21.33">
                        <defs>
                            <style>
                                .path {
                                    fill: #8a9798;
                                }
                            </style>
                        </defs>
                        <g id="foo" transform="translate(0 -17.25)">
                            <g class="path" transform="translate(0 17.25)">
                                <path class="cls-1" d="M22.356,228.248a.657.657,0,0,0-.659.659v6a2.959,2.959,0,0,1-2.955,2.955H4.274a2.959,2.959,0,0,1-2.955-2.955v-6.1a.659.659,0,0,0-1.319,0v6.1a4.278,4.278,0,0,0,4.274,4.274H18.741a4.278,4.278,0,0,0,4.274-4.274v-6A.66.66,0,0,0,22.356,228.248Z" transform="translate(0 -217.849)"/>
                                <path class="path" d="M140.615,33.344a.664.664,0,0,0,.464.2.643.643,0,0,0,.464-.2l4.191-4.191a.66.66,0,1,0-.933-.933l-3.062,3.067V17.909a.659.659,0,1,0-1.319,0V31.288l-3.067-3.067a.66.66,0,0,0-.933.933Z" transform="translate(-129.571 -17.25)"/>
                            </g>
                        </g>
                    </svg>
                </div>
                <hr class="divider" />
                <div class="clear"></div>
                <h2>Download</h2>
            </div>
            <div class="step last-step">
                <div class="step-image">
                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                         viewBox="0 0 22.5 21.1" style="enable-background:new 0 0 22.5 21.1;" xml:space="preserve">
                        <style type="text/css">
                            .st0{
                                fill:#8A9798;
                            }
                        </style>
                        <path class="st0" d="M22,5.5c-0.3-0.3-0.7-0.3-1,0C18.5,8,9.8,16.6,7.5,18.8l-6-6c-0.3-0.3-0.7-0.3-1,0s-0.3,0.7,0,1l7,7L8,20.4
	c0,0,11.1-11,13.9-13.8C22.3,6.3,22.3,5.8,22,5.5z"/>
                </svg>
                </div>
                <div class="clear"></div>
                <h2>Perform update</h2>
            </div>
            <div class="clear"></div>
        </div>
        <div id="display">
            <span id="current-step" class="hidden"> </span>
            <span id="success-message">Updater is loading.</span><br>
            <span id="error-message"></span><br>
        </div>
        <button id="next-step" class="right">Next</button>
        <button id="database-upgrade" class="right" style="visibility:hidden;">Upgrade database</button>
    </div>



    <script>
        let previousFormActions = null;
        function takeAction(action, formValues, callback) {
            let req = new XMLHttpRequest();
            let url = "<?php echo htmlentities( $_SERVER['REQUEST_URI'] )?>";
            req.open('POST', url, true);
            req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            req.onload  = callback;

            let body = "action=" + action;

            if (previousFormActions !== null) {
                body = body + "&" + previousFormActions;
            }
            if(formValues) {
                body = body + "&" + formValues;
                previousFormActions = previousFormActions + "&" + formValues;
            }

            req.send(body);
        }

        takeAction(0, null, function () {
            setCurrentStep(JSON.parse(this.responseText).status.step);
            executeNextStep();
        });

        function setCurrentStep(action){
            document.getElementById("current-step").innerText=action;
        }
        function showErrorMessage(error){
            document.getElementById("error-message").innerText=error;
        }
        function showSuccessMessage(success){
            document.getElementById("error-message").innerText='';
            document.getElementById("success-message").innerHTML=success;
        }

        function setCurrentActionItem(step) {
            const stepActionMap = {
                1: 0,
                2: 0,
                3: 0,
                4: 0,
                5: 1,
                6: 1,
                7: 1,
                8: 2,
                9: 2,
                10: 3,
                11: 3,
                12: 3,
                13: 3,
                14: 3,
                15: 3,
                16: 3,
                17: 3,
                18: 3,
            };

            let steps = document.querySelectorAll('.step-image');
            steps.forEach(function(element) {
                element.classList.remove('active');
            });
            steps[stepActionMap[step]].classList.add('active');

            return stepActionMap[step];
        }

        function executeNextStep(formParams) {
            let nextStep = parseInt(document.getElementById("current-step").innerText) + 1;
            setCurrentActionItem(nextStep);
            document.getElementById('next-step').disabled = true;
            takeAction(nextStep, formParams, function () {
                let continueResponse = JSON.parse(this.responseText).continue;
                let responseMessage = JSON.parse(this.responseText).response;
                let retryResponse = JSON.parse(this.responseText).retry;
                let autocontinue = JSON.parse(this.responseText).autocontinue;
                let nextUrl = JSON.parse(this.responseText).nextUrl;
                if (continueResponse === true) {
                    showSuccessMessage(responseMessage);
                    setCurrentStep(nextStep);
                    document.getElementById('next-step').disabled = false;
                    if (autocontinue === true) {
                        executeNextStep();
                    }
                    if(nextUrl) {
                        document.getElementById("next-step").addEventListener("click",function () {
                            window.location = nextUrl;
                        });
                    }
                } else {
                    showErrorMessage(responseMessage);
                    if(retryResponse === true) {
                        setCurrentStep(nextStep-1);
                        document.getElementById('next-step').disabled = false;
                    }
                }
            });
        }

        document.getElementById("next-step").addEventListener("click",function () {
            let backupform = document.querySelector('form');
            if (backupform !== null) {
                let formParams = new URLSearchParams(new FormData(backupform)).toString();
                executeNextStep(formParams);
            }else {
                executeNextStep(null);
            }

        });
    </script>
    </body>
    </html>
<?php } ?>
<?php

if (USE_PHPMAILER6) {
    spl_autoload_register(
        function ($classname)
        {
            $prefix = 'PHPMailer\PHPMailer\\';
            $prefixLength = strlen($prefix);

            if (substr($classname, 0, $prefixLength) == $prefix) {
                $phpmailerPath = defined('PHPMAILER_PATH') && PHPMAILER_PATH != ''
                    ? rtrim(PHPMAILER_PATH, '/') . '/'
                    : 'PHPMailer6/src/';
                $filename = $phpmailerPath . substr($classname, $prefixLength) . '.php';
                require $filename;
            }
        }
    );

    /**
     * Intermediate class to use PHPMailer 6.
     */
    abstract class phplistMailerBase extends PHPMailer\PHPMailer\PHPMailer
    {
        // Additional properties
        public $lineEnding;

        public function __construct($exceptions)
        {
            parent::__construct($exceptions);
            parent::SetLanguage('en', __DIR__.'/PHPMailer6/language/');
            $this->lineEnding = static::$LE;
        }
    }
} else {
    if (defined('PHPMAILER_PATH') and PHPMAILER_PATH != '') {
        require_once PHPMAILER_PATH;
    } else {
        require_once __DIR__.'/PHPMailer/PHPMailerAutoload.php';
    }

    /**
     * Intermediate class to use PHPMailer 5.
     */
    abstract class phplistMailerBase extends PHPMailer
    {
        // Inherited properties
        public $LE = "\n";

        // Additional properties
        public $lineEnding;

        public function __construct($exceptions)
        {
            parent::__construct($exceptions);
            parent::SetLanguage('en', __DIR__.'/PHPMailer/language/');
            $this->lineEnding = $this->LE;
        }
    }
}

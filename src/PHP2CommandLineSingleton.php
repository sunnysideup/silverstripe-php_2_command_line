<?php

/**
 * This class provides a way to run a command line script
 * When opened through the browser (http(s)) it shows the script for copying and editing
 * When run through the command line (CLI) it will execute immediately.
 */

namespace Sunnysideup\PHP2CommandLine;

class PHP2CommandLineSingleton
{
    /**
     * Where will the log file be stored.
     * The log file takes all output that is printed to console and saves it to a file
     * @var string
     */
    protected $logFileLocation = '';

    /**
     * Where will the key notes file be stored.
     * @var string
     */
    protected $keyNotesFileLocation = '';

    /**
     * @var bool
     */
    protected $makeKeyNotes = false;

    /**
     * If false then will output HTML version of a batch file for running this module
     * If true runs the module immediately
     * If null, it will not override other settings.
     * @var bool|null
     */
    protected $runImmediately = null;

    /**
     * should the script stop if any error occurs?
     * @var bool
     */
    protected $breakOnAllErrors = false;

    /**
     * should the script stop if any error occurs?
     * @var string
     */
    protected $lastError = '';

    /**
     * message that should show on error
     * @var string
     */
    protected $errorMessage = '';

    /**
     * should we output at all?
     * @var bool
     */
    protected $verbose = true;

    /**
     * @var bool
     */
    protected $hasError = false;

    private static $_singleton;

    /**
     * Where to save the logfile to
     * @param string $logFileLocation
     */
    public function __construct(?string $logFileLocation = '')
    {
        $this->logFileLocation = $logFileLocation;
        $this->startOutput();
    }

    /**
     * When program finishes execution end the output
     */
    public function __destruct()
    {
        $this->endOutput();
    }

    public static function create(?string $logFileLocation = ''): self
    {
        if (self::$_singleton === null) {
            $className = static::class;
            self::$_singleton = new $className($logFileLocation);
        }

        return self::$_singleton;
    }

    public static function commandExists(string $command) : bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($command)));

        return ! empty($return);
    }

    /**
     * Deletes the current singleton by setting it null
     */
    public static function delete()
    {
        self::$_singleton = null;

        return null;
    }

    /**
     * Determine the location of the log file for writing all the printed output to
     * @param string $s file location
     * @return PHP2CommandLineSingleton
     */
    public function setLogFileLocation(string $s): self
    {
        $this->logFileLocation = $s;

        return $this;
    }

    /**
     * See where the location of the log file for writing all the printed output to is
     * @return string
     */
    public function getLogFileLocation(): string
    {
        return $this->logFileLocation;
    }

    /**
     * set key notes files location
     * @param string $s file location
     * @return PHP2CommandLineSingleton
     */
    public function setKeyNotesFileLocation(string $s): self
    {
        $this->keyNotesFileLocation = $s;

        return $this;
    }

    /**
     * set key notes files location
     * @return string file location
     */
    public function getKeyNotesFileLocation(): string
    {
        return $this->keyNotesFileLocation;
    }

    /**
     * set key notes files location
     * @param bool $b
     * @return PHP2CommandLineSingleton
     */
    public function setMakeKeyNotes(bool $b): self
    {
        $this->makeKeyNotes = $b;

        return $this;
    }

    /**
     * set key notes files location
     * @return bool
     */
    public function getHasError(): bool
    {
        return $this->hasError;
    }

    /**
     * set key notes files location
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * set key notes files location
     * @return self
     */
    public function setErrorMessage(string $s): self
    {
        $this->errorMessage = $s;

        return $this;
    }

    /**
     * @param bool $b
     * @return PHP2CommandLineSingleton
     */
    public function setRunImmediately(bool $b): self
    {
        $this->runImmediately = $b;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRunImmediately(): bool
    {
        return $this->runImmediately;
    }

    /**
     * @return bool
     */
    public function getBreakOnAllErrors(): bool
    {
        return $this->breakOnAllErrors;
    }

    /**
     * @param bool $b
     *
     * @return PHP2CommandLineSingleton
     */
    public function setBreakOnAllErrors($b): self
    {
        $this->breakOnAllErrors = $b;

        return $this;
    }

    /**
     * @param  string  $currentDir what directory is the command line currently in
     * @param  string  $command
     * @param  string  $comment
     * @param  bool $alwaysRun
     * @param  bool $verbose
     *
     * @return array
     */
    public function execMe(string $currentDir, string $command, string $comment, ?bool $alwaysRun = false, ?bool $verbose = true): array
    {
        $this->verbose = $verbose;
        $this->hasError = false;
        if ($this->runImmediately === null) {
            if ($this->isCommandLine()) {
                $this->runImmediately = true;
            } else {
                $this->runImmediately = false;
            }
        }

        $this->newLine(1);
        //show comment ...
        $this->colourPrint('# ' . $comment, 'dark_gray');
        if ($this->isHTML()) {
            if ($this->runImmediately || $alwaysRun) {
                //do nothing
            } else {
                //show comment as print out when actually running it!!!
                echo '<div style="color: transparent">tput setaf 33; echo " _____ : ' . addslashes($comment) . '" ____ </div>';
            }
        }

        //show command ...
        //we use && here because this means that the second part only runs
        //if the changedir works.
        $this->colourPrint('cd ' . $currentDir, 'run');
        $commandsExploded = explode('&&', $command);
        foreach ($commandsExploded as $commandInner) {
            $commandsExplodedInner = explode(';', $commandInner);
            foreach ($commandsExplodedInner as $commandInnerInner) {
                $this->colourPrint(trim($commandInnerInner), 'run');
            }
        }

        //run command ...
        $returnDetails = '';
        if ($this->runImmediately || $alwaysRun) {
            $beforeDir = getcwd();
            if(! file_exists($currentDir)) {
                debug_backtrace();
                user_error('Could not find '.$currentDir, E_USER_ERROR);
            }
            chdir($currentDir);
            $outcome = exec($command . '  2>&1 ', $returnDetails, $return);
            if ($return) {
                $errorString = (string) print_r($returnDetails, 1);
                $this->lastError = $errorString;
                $this->colourPrint($returnDetails, 'red');
                $this->colourPrint($this->errorMessage, 'red');
                if ($this->breakOnAllErrors) {
                    $this->endOutput();
                    $this->newLine(10);
                    die('------ STOPPED -----');
                    $this->newLine(10);
                }
            } else {
                if ($outcome) {
                    $this->colourPrint($outcome, 'green');
                }
                if (is_array($returnDetails)) {
                    foreach ($returnDetails as $line) {
                        $this->colourPrint($line, 'blue');
                    }
                } else {
                    $this->colourPrint($returnDetails, 'blue');
                }
                $this->colourPrint('✔✔✔', 'green', 1);
                $this->newLine(2);
            }
            chdir($beforeDir);
        }
        if ($this->isHTML()) {
            ob_flush();
            flush();
        }
        if (! is_array($returnDetails)) {
            $returnDetails = [$returnDetails];
        }

        return $returnDetails;
    }

    /**
     * echos out the resulting string after applying all parameters
     * @todo add the ability to use colours like "warning", "notice", "error"
     * @param  mixed   $mixedVar
     * @param  string  $colour       that you wish the output to displayed as
     * @param  integer $newLineCount amount of empty lines you want to appear before the next text is printed
     */
    public function colourPrint($mixedVar, $colour = 'dark_gray', $newLineCount = 1)
    {
        $alwaysPrint = $this->isError($colour);
        $mixedVarAsString = print_r($mixedVar, 1);
        if ($this->isCommandLine()) {
            $colour = $this->getColour($colour, false);
            $outputString = "\033[" . $colour . 'm' . $mixedVarAsString . "\033[0m";
        } else {
            $colour = $this->getColour($colour, true);
            $colourString = 'style="color: ' . $colour . '"';
            if ($newLineCount === 0) {
                $el = 'span';
            } else {
                //div is a new line in its own right ...
                $newLineCount--;
                $el = 'div';
            }
            $outputString = '<' . $el . ' ' . $colourString . '>' . $mixedVarAsString . '</' . $el . '>';
        }
        if ($newLineCount) {
            $this->newLine($newLineCount);
        }
        if ($this->verbose || $alwaysPrint) {
            $this->writeToLog($mixedVarAsString, $newLineCount);
            echo $outputString;
        }
    }

    protected function writeToLog($mixedVarAsString, int $newLineCount)
    {
        $logFileLocation = $this->getLogFileLocation();
        //write to log
        if ($logFileLocation) {
            $this->writeToFile($logFileLocation, $mixedVarAsString, $newLineCount);
        }
        $keyNotesFileLocation = $this->getKeyNotesFileLocation();
        if ($keyNotesFileLocation && $this->makeKeyNotes) {
            $this->writeToFile($keyNotesFileLocation, $mixedVarAsString, $newLineCount);
        }
    }

    protected function writeToFile($fileName, $data, $newLineCount)
    {
        if (! file_exists($fileName)) {
            $folder = dirname($fileName);
            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            file_put_contents($fileName, date('Y-m-d h:i'));
            file_put_contents($fileName, PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            //add new lines
            for ($i = 0; $i < $newLineCount; $i++) {
                file_put_contents($fileName, PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }
        //add data ...
        file_put_contents($fileName, $data, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return bool is output being printed to command line or to website
     */
    protected function isCommandLine(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }
        return false;
    }

    /**
     * @return bool is output being displayed as html rather than on the command line
     */
    protected function isHTML(): bool
    {
        return $this->isCommandLine() ? false : true;
    }

    /**
     * For printing some fixed data to output at beginning of output before the dynamic data is printed to console or html
     */
    protected function startOutput()
    {
        if ($this->isHTML()) {
            // Turn off output buffering
            // ini_set('output_buffering', 'off');
            // // Turn off PHP output compression
            // ini_set('zlib.output_compression', false);
            //
            // //Flush (send) the output buffer and turn off output buffering
            // //ob_end_flush();
            // while (@ob_end_flush());
            //
            // // Implicitly flush the buffer(s)
            // ini_set('implicit_flush', true);
            // ob_implicit_flush(true);
            //
            // //prevent apache from buffering it for deflate/gzip
            // header("Content-type: text/plain");
            // header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

            echo '
            <!DOCTYPE html>
            <html lang="en-US">
            <head>
            <meta charset="UTF-8">
            <title>Title of the document</title>
            </head>

            <body>
                <pre><code class="sh">#!/bin/bash<br />';
            ob_flush();
            flush();
        }
    }

    /**
     * For finishing off the programs output with some fixed output. Currently only used for closing off html at end of file.
     */
    protected function endOutput()
    {
        if ($this->isHTML()) {
            // $dir = dirname(dirname(__FILE__));
            // $css = file_get_contents($dir.'/javascript/styles/default.css');
            // $js = file_get_contents($dir.'/javascript/highlight.pack.js');
            echo '
            <style>
                html, body {padding: 0; margin: 0; min-height: 100%; height: 100%; background-color: #300a24;color: #fff;}
                pre {
                    font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace;
                }
                strong {display: block; color: teal;}
                i {color: green; font-style: normal;}
                .hljs-string {color: yellow;}
                .hljs-built_in {color: #ccc;}
            </style>
            </body>
            </html>

            ';
            ob_flush();
            flush();
        } else {
            $this->newLine(3);
        }
    }

    /**
     * Depending on if writing to command line or to browser as html, echo a line break or EOL (End of line)
     *
     * @param  integer $numberOfLines number of line breaks or end of lines to echo
     */
    protected function newLine(?int $numberOfLines = 1)
    {
        for ($i = 0; $i < $numberOfLines; $i++) {
            if ($this->isCommandLine()) {
                echo PHP_EOL;
            } else {
                echo '<br />';
            }
        }
    }

    protected function getColour(string $colour, ?bool $usehtmlColour = false)
    {
        $htmlColour = str_replace('_', '', $colour);
        $htmlColour = str_replace('-', '', $htmlColour);
        switch ($colour) {
            case 'black':
                $colour = '0;30';
                break;
            case 'blue':
                $colour = '0;34';
                break;
            case 'light_blue':
                $colour = '1;34';
                break;
            case 'green':
                $colour = '0;32';
                break;
            case 'dark_gray':
                $colour = '1;30';
                $htmlColour = '#555';
            case 'cyan':
                $colour = '0;36';
                break;
            case 'light_cyan':
                $colour = '1;36';
                break;
            case 'red':
            case 'error':
                $colour = '0;31';
                $htmlColour = 'red';
                break;
            case 'light_red':
                $colour = '1;31';
                $htmlColour = 'pink';
                break;
            case 'purple':
                $colour = '0;35';
                break;
            case 'run':
            case 'light_purple':
                $colour = '1;35';
                $htmlColour = 'violet';
                break;
            case 'brown':
                $colour = '0;33';
                break;
            case 'yellow':
            case 'warning':
                $colour = '1;33';
                $htmlColour = 'yellow';
                break;
            case 'light_gray':
                $colour = '0;37';
                $htmlColour = '#999';
                break;
            case 'white':
                $colour = '1;37';
                break;
            case 'light_green':
            case 'notice':
            default:
                $colour = '1;32';
                $htmlColour = 'light-green';
        }
        if ($usehtmlColour) {
            return $htmlColour;
        }
        return $colour;
    }

    protected function isError(string $colour): bool
    {
        switch ($colour) {
            case 'red':
            case 'error':
                return true;
            default:
                return false;
        }
    }
}

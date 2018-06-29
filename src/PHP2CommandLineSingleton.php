<?php

/**
 * This class provides a way to run a command line script
 * When opened through the browser (http(s)) it shows the script for copying and editing
 * When run through the command line (CLI) it will execute immediately.
 */

namespace Sunnysideup\PHP2CommandLine;

class PHP2CommandLineSingleton
{
    private static $_singleton;

    public static function create($logFileLocation = '')
    {
        if (self::$_singleton === null) {
            $className = get_called_class();
            self::$_singleton = new $className($logFileLocation);
        }

        return self::$_singleton;
    }

    /**
     * Deletes the current singleton by setting it null
     * @return null
     */
    public static function delete()
    {
        self::$_singleton = null;

        return null;
    }

    /**
     * Where will the log file be stored.
     * The log file takes all output that is printed to console and saves it to a file
     * @var string
     */
    protected $logFileLocation = '';

    /**
     * Determine the location of the log file for writing all the printed output to
     * @param string file location
     * @return PHP2CommandLineSingleton
     */
    public function setLogFileLocation($s)
    {
        $this->logFileLocation = $s;

        return $this;
    }

    /**
     * See where the location of the log file for writing all the printed output to is
     * @return PHP2CommandLineSingleton
     */
    public function getLogFileLocation()
    {
        return $this->logFileLocation;
    }

    /**
     *
     * If false then will output HTML version of a batch file for running this module
     * If true runs the module immediately
     * @var null|bool
     *
     */
    protected $runImmediately = null;

    /**
     * @param bool
     * @return PHP2CommandLineSingleton
     */
    public function setRunImmediately($b)
    {
        $this->runImmediately = $b;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRunImmediately()
    {
        return $this->runImmediately;
    }


    /**
     * should the script stop if any error occurs?
     * @var bool
     */
    protected $breakOnAllErrors = false;

    /**
     * @return bool
     */
    public function getBreakOnAllErrors()
    {
        return $this->breakOnAllErrors;
    }

    /**
     * @param bool
     */
    public function setBreakOnAllErrors($b)
    {
        $this->breakOnAllErrors = $b;

        return $this;
    }

    /**
     * Where to save the logfile to
     * @param string $logFileLocation
     */
    public function __construct($logFileLocation = '')
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

    /**
     * NICOLAAS?
     * @param  string  $currentDir what directory is the command line currently in
     * @param  [type]  $command    [description]
     * @param  [type]  $comment    [description]
     * @param  boolean $alwaysRun  [description]
     * @return [type]              [description]
     */
    public function execMe($currentDir, $command, $comment, $alwaysRun = false)
    {
        if ($this->runImmediately === null) {
            if ($this->isCommandLine()) {
                $this->runImmediately = true;
            } else {
                $this->runImmediately = false;
            }
        }

        //we use && here because this means that the second part only runs
        //if the changedir works.
        $command = 'cd '.$currentDir.' && '.$command;
        if ($this->isHTML()) {
            $this->newLine();
            echo '<strong># '.$comment .'</strong><br />';
            if ($this->runImmediately || $alwaysRun) {
                //do nothing
            } else {
                echo '<div style="color: transparent">tput setaf 33; echo " _____ : '.addslashes($comment) .'" ____ </div>';
            }
        } else {
            $this->colourPrint('# '.$comment, 'dark_gray');
        }
        $commandsExploded = explode('&&', $command);
        foreach ($commandsExploded as $commandInner) {
            $commandsExplodedInner = explode(';', $commandInner);
            foreach ($commandsExplodedInner as $commandInnerInner) {
                $this->colourPrint(trim($commandInnerInner), 'white');
            }
        }
        if ($this->runImmediately || $alwaysRun) {
            $outcome = exec($command.'  2>&1 ', $error, $return);
            if ($return) {
                $this->colourPrint($error, 'red');
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
                if (is_array($error)) {
                    foreach ($error as $line) {
                        $this->colourPrint($line, 'blue');
                    }
                } else {
                    $this->colourPrint($error, 'blue');
                }
                if ($this->isHTML()) {
                    $this->newLine(1);
                    echo ' <i>✔</i>';
                } else {
                    $this->colourPrint('✔✔✔', 'green', 1);
                }
                $this->newLine(2);
            }
        }
        if ($this->isHTML()) {
            ob_flush();
            flush();
        }
    }


    /**
     * echos out the resulting string after applying all parameters
     * @todo add the ability to use colours like "warning", "notice", "error"
     * @param  [type]  $mixedVar     [description]
     * @param  string  $colour       that you wish the output to displayed as
     * @param  integer $newLineCount amount of empty lines you want to appear before the next text is printed
     * @return null
     */
    public function colourPrint($mixedVar, $colour = '', $newLineCount = 1)
    {
        $mixedVarAsString = print_r($mixedVar, 1);
        $logFileLocation = $this->getLogFileLocation();
        //write to log
        if ($logFileLocation) {
            if (! file_exists($this->getLogFileLocation())) {
                file_put_contents($this->getLogFileLocation(), date('Y-m-d h:i'));
                file_put_contents($this->getLogFileLocation(), PHP_EOL.PHP_EOL, FILE_APPEND | LOCK_EX);
            } else {
                for ($i = 0; $i < $newLineCount; $i++) {
                    file_put_contents($this->getLogFileLocation(), PHP_EOL, FILE_APPEND | LOCK_EX);
                }
            }
            file_put_contents($this->getLogFileLocation(), $mixedVarAsString, FILE_APPEND | LOCK_EX);
        }

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
            case 'light_green':
                $colour = '1;32';
                break;
            case 'cyan':
                $colour = '0;36';
                break;
            case 'light_cyan':
                $colour = '1;36';
                break;
            case 'red':
            case 'error':
                $colour = '0;31';
                break;
            case 'light_red':
                $colour = '1;31';
                break;
            case 'purple':
                $colour = '0;35';
                break;
            case 'light_purple':
                $colour = '1;35';
                break;
            case 'brown':
                $colour = '0;33';
                break;
            case 'yellow':
            case 'warning':
                $colour = '1;33';
                break;
            case 'light_gray':
                $colour = '0;37';
                break;
            case 'white':
            case 'run':
                $colour = '1;37';
                break;
            case 'dark_gray':
            case 'notice':
            default:
                $colour = '1;30';
        }
        $outputString = "\033[" . $colour . "m".$mixedVarAsString."\033[0m";
        if ($newLineCount) {
            $this->newLine($newLineCount);
        }
        echo $outputString;
    }


    /**
     * @return bool is output being printed to command line or to website
     */
    protected function isCommandLine() : bool
    {
        if (php_sapi_name() == "cli") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool is output being displayed as html rather than on the command line
     */
    protected function isHTML() : bool
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
            $dir = dirname(dirname(__FILE__));
            // $css = file_get_contents($dir.'/javascript/styles/default.css');
            // $js = file_get_contents($dir.'/javascript/highlight.pack.js');
            // echo '</code></pre>
            // <script>
            //     '.$js.'
            //     hljs.initHighlightingOnLoad();
            // </script>
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
     * @return null only echos data doesnt return anything
     */
    protected function newLine($numberOfLines = 1)
    {
        for ($i = 0; $i < $numberOfLines; $i++) {
            if ($this->isCommandLine()) {
                echo PHP_EOL;
            } else {
                echo '<br />';
            }
        }
    }
}

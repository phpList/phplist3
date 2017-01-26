<?php

/* Copyright 2002-2003 Edward Swindelles (ed@readinged.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (!defined('ONYX_RSS_VERS')) {
    define('ONYX_RSS_VERS', '1.0');
    define('ONYX_ERR_NO_PARSER', '<a href="http://www.php.net/manual/en/ref.xml.php">PHP\'s XML Extension</a> is not loaded or available.');
    define('ONYX_ERR_NOT_WRITEABLE', 'The specified cache directory is not writeable.');
    define('ONYX_ERR_INVALID_URI', 'The specified file could not be opened.');
    define('ONYX_ERR_INVALID_ITEM', 'Invalid item index specified.');
    define('ONYX_ERR_NO_STREAM', 'Could not open the specified file.  Check the path, and make sure that you have write permissions to this file.');
    define('ONYX_META', 'meta');
    define('ONYX_ITEMS', 'items');
    define('ONYX_IMAGE', 'image');
    define('ONYX_TEXTINPUT', 'textinput');
    define('ONYX_NAMESPACES', 'namespaces');
    define('ONYX_CACHE_AGE', 'cache_age');
    define('ONYX_FETCH_ASSOC', 1);
    define('ONYX_FETCH_OBJECT', 2);
}

class ONYX_RSS
{
    public $parser;
    public $conf;
    public $rss;
    public $data;
    public $type;
    public $lasterror;
   /* For when PHP v.5 is released
    * http://www.phpvolcano.com/eide/php5.php?page=variables
    * private $parser;
    * private $conf;
    * private $rss;
    * private $data;
    * private $type;
   */

   // Forward compatibility with PHP v.5
   // http://www.phpvolcano.com/eide/php5.php?page=start

    public function __construct()
    {
        $this->conf = array();
        $this->conf['error'] = '<br /><strong>Error on line %s of '.__FILE__.'</strong>: %s<br />';
        $this->conf['cache_path'] = dirname(__FILE__);
        $this->conf['cache_time'] = 180;
        $this->conf['debug_mode'] = true;
        $this->conf['fetch_mode'] = ONYX_FETCH_ASSOC;
        $this->lasterror = '';
        $this->context = stream_context_create(array(
            'http' => array(
                'timeout' => 5.0,
            ),
        ));

        if (!function_exists('xml_parser_create')) {
            $this->raiseError((__LINE__ - 2), ONYX_ERR_NO_PARSER);

            return false;
        }

        $this->parser = @xml_parser_create();
        if (!is_resource($this->parser)) {
            $this->raiseError((__LINE__ - 3), ONYX_ERR_NO_PARSER);

            return false;
        }
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($this->parser, 'tag_open', 'tag_close');
        xml_set_character_data_handler($this->parser, 'cdata');
    }

    public function parse($uri, $file = false, $time = false, $local = false)
    {
        $this->rss = array();
        $this->rss['cache_age'] = 0;
        $this->rss['current_tag'] = '';
        $this->rss['index'] = 0;
        $this->rss['output_index'] = -1;
        $this->data = array();
        $mod = 0;

        if ($file) {
            if (!is_writable($this->conf['cache_path'])) {
                $this->raiseError((__LINE__ - 2), ONYX_ERR_NOT_WRITEABLE);

                return false;
            }
            $file = str_replace('//', '/', $this->conf['cache_path'].'/'.$file);
            if (!$time) {
                $time = $this->conf['cache_time'];
            }
            $this->rss['cache_age'] = file_exists($file) ? ceil((time() - filemtime($file)) / 60) : 0;
            $cacheHasExpired = $time <= $this->rss['cache_age'];

            clearstatcache();
            if (!$local && file_exists($file) && $cacheHasExpired) {
                if (($mod = $this->mod_time($uri)) === false) {
                    $this->raiseError((__LINE__ - 2), ONYX_ERR_INVALID_URI);

                    return false;
                } else {
                    $mod = ($mod !== 0) ? strtotime($mod) : (time() + 3600);
                }
            } elseif ($local) {
                $mod = (file_exists($file) && ($m = filemtime($uri))) ? $m : time() + 3600;
            }
            $feedHasNewContent = $mod >= (time() - ($this->rss['cache_age'] * 60));
        }
        if (!$file ||
           ($file && !file_exists($file)) ||
           ($file && file_exists($file) && $cacheHasExpired && $feedHasNewContent)) {
            clearstatcache();
            if (!($fp = @fopen($uri, 'r', false, $this->context))) {
                $this->raiseError((__LINE__ - 2), ONYX_ERR_INVALID_URI);

                return false;
            }
            while ($chunk = fread($fp, 4096)) {
                $parsedOkay = xml_parse($this->parser, $chunk, feof($fp));
                if (!$parsedOkay && xml_get_error_code($this->parser) != XML_ERROR_NONE) {
                    $this->raiseError((__LINE__ - 3), 'File has an XML error (<em>'.xml_error_string(xml_get_error_code($this->parser)).'</em> at line <em>'.xml_get_current_line_number($this->parser).'</em>).');

                    return false;
                }
            }
            fclose($fp);
            clearstatcache();
            if ($file) {
                if (!($cache = @fopen($file, 'w'))) {
                    $this->raiseError((__LINE__ - 2), 'Could not write to cache file (<em>'.$file.'</em>).  The path may be invalid or you may not have write permissions.');

                    return false;
                }
                fwrite($cache, serialize($this->data));
                fclose($cache);
                $this->rss['cache_age'] = 0;
            }
        } else {
            if ($cacheHasExpired) {
                touch($file);
                $this->rss['cache_age'] = 0;
            }
            clearstatcache();
            if (!($fp = @fopen($file, 'r'))) {
                $this->raiseError((__LINE__ - 2), 'Could not read contents of cache file (<em>'.$cache_file.'</em>).');

                return false;
            }
            $this->data = unserialize(fread($fp, filesize($file)));
            fclose($fp);
        }

        return true;
    }

    public function parseLocal($uri, $file = false, $time = false)
    {
        return $this->parse($uri, $file, $time, true);
    }

   //private function tag_open($parser, $tag, $attrs)

   public function tag_open($parser, $tag, $attrs)
   {
       $this->rss['current_tag'] = $tag = strtolower($tag);
       switch ($tag) {
         case 'channel':
         case 'image':
         case 'textinput':
            $this->type = $tag;
            break;
         case 'item':
            $this->type = $tag;
            ++$this->rss['index'];
            break;
         default:
            break;
      }
       if (count($attrs)) {
           foreach ($attrs as $k => $v) {
               if (strpos($k, 'xmlns') !== false) {
                   $this->data['namespaces'][$k] = $v;
               }
           }
       }
   }

   //private function tag_close($parser, $tag){}

   public function tag_close($parser, $tag)
   {
   }

   //private function cdata($parser, $cdata)

   public function cdata($parser, $cdata)
   {
       if (strlen(trim($cdata)) && $cdata != "\n") {
           switch ($this->type) {
            case 'channel':
            case 'image':
            case 'textinput':
               (!isset($this->data[$this->type][$this->rss['current_tag']]) ||
                !strlen($this->data[$this->type][$this->rss['current_tag']])) ?
                  $this->data[$this->type][$this->rss['current_tag']] = $cdata :
                  $this->data[$this->type][$this->rss['current_tag']] .= $cdata;
               break;
            case 'item':
               (!isset($this->data['items'][$this->rss['index'] - 1][$this->rss['current_tag']]) ||
                !strlen($this->data['items'][$this->rss['index'] - 1][$this->rss['current_tag']])) ?
                  $this->data['items'][$this->rss['index'] - 1][$this->rss['current_tag']] = $cdata :
                  $this->data['items'][$this->rss['index'] - 1][$this->rss['current_tag']] .= $cdata;
               break;
         }
       }
   }

    public function getData($type)
    {
        if ($type == ONYX_META) {
            return $this->conf['fetch_mode'] == 1 ? $this->data['channel'] : (object) $this->data['channel'];
        }
        if ($type == ONYX_IMAGE) {
            return $this->conf['fetch_mode'] == 1 ? $this->data['image'] : (object) $this->data['image'];
        }
        if ($type == ONYX_TEXTINPUT) {
            return $this->conf['fetch_mode'] == 1 ? $this->data['textinput'] : (object) $this->data['textinput'];
        }
        if ($type == ONYX_ITEMS) {
            if ($this->conf['fetch_mode'] == 1) {
                return $this->data['items'];
            }

            $temp = array();
            for ($i = 0; $i < count($this->data['items']); ++$i) {
                $temp[] = (object) $this->data['items'][$i];
            }

            return $temp;
        }
        if ($type == ONYX_NAMESPACES) {
            return $this->conf['fetch_mode'] == 1 ? $this->data['namespaces'] : (object) $this->data['namespaces'];
        }
        if ($type == ONYX_CACHE_AGE) {
            return $this->rss['cache_age'];
        }

        return false;
    }

    public function numItems()
    {
        return count($this->data['items']);
    }

    public function getNextItem($max = false)
    {
        $type = $this->conf['fetch_mode'];
        ++$this->rss['output_index'];
        if (($max && $this->rss['output_index'] > $max) || !isset($this->data['items'][$this->rss['output_index']])) {
            return false;
        }

        return ($type == ONYX_FETCH_ASSOC) ? $this->data['items'][$this->rss['output_index']] :
             (($type == ONYX_FETCH_OBJECT) ? (object) $this->data['items'][$this->rss['output_index']] : false);
    }

    public function itemAt($num)
    {
        if (!isset($this->data['items'][$num])) {
            $this->raiseError((__LINE__ - 3), ONYX_ERR_INVALID_ITEM);

            return false;
        }

        $type = $this->conf['fetch_mode'];

        return ($type == ONYX_FETCH_ASSOC) ? $this->data['items'][$num] :
             (($type == ONYX_FETCH_OBJECT) ? (object) $this->data['items'][$num] : false);
    }

    public function startBuffer($file = false)
    {
        $this->conf['output_file'] = $file;
        ob_start();
    }

    public function endBuffer()
    {
        if (!$this->conf['output_file']) {
            ob_end_flush();
        } else {
            if (!($fp = @fopen($this->conf['output_file'], 'w'))) {
                $this->raiseError((__LINE__ - 2), ONYX_ERR_NO_STREAM);
                ob_end_flush();

                return;
            }
            fwrite($fp, ob_get_contents());
            fclose($fp);
            chmod($this->conf['output_file'], 0666);
            ob_end_clean();
        }
    }

   //private function raiseError($line, $err)

   public function raiseError($line, $err)
   {
       if ($this->conf['debug_mode']) {
           printf($this->conf['error'], $line, $err);
       } else {
           $this->lasterror = $err;
       }
   }

    public function setCachePath($path)
    {
        $this->conf['cache_path'] = $path;
    }

    public function setExpiryTime($time)
    {
        $this->conf['cache_time'] = $time;
    }

    public function setDebugMode($state)
    {
        $this->conf['debug_mode'] = (bool) $state;
    }

    public function setFetchMode($mode)
    {
        $this->conf['fetch_mode'] = $mode;
    }

   //private function mod_time($uri)

   public function mod_time($uri)
   {
       if (function_exists('version_compare') && version_compare(phpversion(), '4.3.0') >= 0) {
           if (!($fp = fopen($uri, 'r', false, $this->context))) {
               return false;
           }

           $meta = stream_get_meta_data($fp);
           for ($j = 0; isset($meta['wrapper_data'][$j]); ++$j) {
               if (strpos(strtolower($meta['wrapper_data'][$j]), 'last-modified') !== false) {
                   $modtime = substr($meta['wrapper_data'][$j], 15);
                   break;
               }
           }
           fclose($fp);
       } else {
           $parts = parse_url($uri);
           $host = $parts['host'];
           $path = $parts['path'];

           if (!($fp = @fsockopen($host, 80))) {
               return false;
           }

           $req = "HEAD $path HTTP/1.1\r\nUser-Agent: PHP/".phpversion();
           $req .= "\r\nHost: $host\r\nAccept: */*\r\n\r\n";
           fwrite($fp, $req);

           while (!feof($fp)) {
               $str = fgets($fp, 4096);
               if (strpos(strtolower($str), 'last-modified') !== false) {
                   $modtime = substr($str, 15);
                   break;
               }
           }
           fclose($fp);
       }

       return (isset($modtime)) ? $modtime : 0;
   }
}

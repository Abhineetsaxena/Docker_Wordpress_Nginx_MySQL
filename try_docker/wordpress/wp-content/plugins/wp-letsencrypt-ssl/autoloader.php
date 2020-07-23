<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 4.3.0
 *
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace WPEncryption;

defined('ABSPATH') || exit;

/**
 * Autoloader.
 * Dynamic class loading.
 *
 * @since 4.3.0
 */
if (!class_exists('\WPEncryption\Autoloader')) :

  class Autoloader
  {

    /**
     * Run autoloader.
     *
     * @since 4.3.0
     * @access public
     */
    public static function run()
    {
      spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload.
     * For a given class, check if it exist and load it.
     *
     * @since 4.3.0
     * @access private
     * @param string $class Class name.
     */
    private static function autoload($class_name)
    {

      // If the class being requested does not start with our prefix
      // we know it's not one in our project.
      if (false === strpos($class_name, 'WPEncryption') && false === strpos($class_name, 'LEClient')) {
        return;
      }

      // Split the class name into an array to read the namespace and class.
      $file_parts = explode('\\', $class_name);

      ///print_r($file_parts);

      // Do a reverse loop through $file_parts to build the path to the file.
      $namespace = '';
      for ($i = count($file_parts) - 1; $i > 0; $i--) {
        // Read the current component of the file part.
        $current = $file_parts[$i];

        if (count($file_parts) - 1 === $i) {
          $file_name = "$current.php";
        } else {
          if ($i == 1) {
            $namespace = $current . $namespace;
          } else {
            $namespace = '/' . $current . $namespace;
          }
        }
      }

      $filepath  = plugin_dir_path(__FILE__) . 'lib/' . trailingslashit($namespace);
      $filepath .= $file_name;

      if (file_exists($filepath)) {
        require_once($filepath);
      }
    }
  }

endif;

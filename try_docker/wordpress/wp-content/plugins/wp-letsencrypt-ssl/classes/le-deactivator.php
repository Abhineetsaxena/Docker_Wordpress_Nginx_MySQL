<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 1.0.0
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

class WPLE_Deactivator
{

  public static function deactivate()
  {
    $opts = get_option('wple_opts') === FALSE ? array('expiry' => '') : get_option('wple_opts');

    //disable ssl forcing
    $opts['force_ssl'] = 0;
    update_option('wple_opts', $opts);

    if (FALSE != get_option('wple_gdaddy')) {
      delete_option('wple_gdaddy');
    }

    if (FALSE != get_option('wple_error')) {
      delete_option('wple_error');
    }

    if (FALSE != get_option('wple_show_review')) {
      delete_option('wple_show_review');
    }

    if (FALSE != get_option('wple_go_plan')) {
      delete_option('wple_go_plan');
    }

    if (FALSE != get_option('wple_choose_plan')) {
      delete_option('wple_choose_plan');
    }

    if (file_exists(WPLE_DEBUGGER . 'debug.log')) {
      @unlink(WPLE_DEBUGGER . 'debug.log');
    }
  }
}

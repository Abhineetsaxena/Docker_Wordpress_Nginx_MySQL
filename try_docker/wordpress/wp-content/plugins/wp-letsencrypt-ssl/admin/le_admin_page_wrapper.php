<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 5.0.0
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

class WPLE_Admin_Page
{

  public function __construct()
  {
    add_action('admin_enqueue_scripts', array($this, 'wple_admin_page_styles'));
  }

  public function wple_admin_page_styles()
  {
    wp_enqueue_style(WPLE_NAME, WPLE_URL . 'admin/css/le-admin.min.css', FALSE, WPLE_VERSION, 'all');

    wp_enqueue_script(WPLE_NAME . '-popper', WPLE_URL . 'admin/js/popper.min.js', array('jquery'), WPLE_VERSION, true);
    wp_enqueue_script(WPLE_NAME . '-tippy', WPLE_URL . 'admin/js/tippy-bundle.iife.min.js', array('jquery'), WPLE_VERSION, true);
    wp_enqueue_script(WPLE_NAME, WPLE_URL . 'admin/js/le-admin.js', array('jquery'), WPLE_VERSION, true);
  }

  public function generate_page($pagecontent = '')
  {
    $html = '
    <div class="wple-header">
      <img src="' . WPLE_URL . 'admin/assets/logo.png" class="wple-logo"/> <span class="wple-version">v' . WPLE_VERSION . '</span>
    </div>';

    $html .= '<div id="wple-sslgen">' . $pagecontent . '</div>';
    echo $html;
  }

  /**
   * Escape html but retain bold
   *
   * @since 3.3.3
   * @source le_admin.php since 5.0.0
   * @param string $translated
   * @param string $additional Additional allowed html tags
   * @return void
   */
  protected function wple_kses($translated, $additional = '')
  {

    $allowed = array(
      'strong' => array(),
      'b' => array()
    );

    if ($additional == 'a') {
      $allowed['a'] = array(
        'href' => array(),
        'rel' => array(),
        'target' => array(),
        'title' => array()
      );
    }

    return wp_kses($translated, $allowed);
  }

  /**
   * Ability to revert back to HTTP
   *
   * @since 3.3.0
   * @source le_admin.php since 5.0.0
   * @param string $revertcode
   * @return void
   */
  protected function wple_send_reverter_secret($revertcode)
  {

    $to = get_bloginfo('admin_email');

    $sub = esc_html__('You have successfully forced HTTPS on your site', 'wp-letsencrypt-ssl');

    $header = array('Content-Type: text/html; charset=UTF-8');

    $rcode = sanitize_text_field($revertcode);
    $body = $this->wple_kses(__("HTTPS have been strictly forced on your site now!. In rare cases, this may cause issue / make the site un-accessible <b>IF</b> you dont have valid SSL certificate installed for your WordPress site. Kindly save the below <b>Secret code</b> to revert back to HTTP in such a case.", 'wp-letsencrypt-ssl')) . "
      <br><br>
      <strong>$rcode</strong><br><br>" .
      $this->wple_kses(__("Opening the revert url will <b>IMMEDIATELY</b> turn back your site to HTTP protocol & revert back all the force SSL changes made by WP Encryption in one go!. Please follow instructions given at https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/", 'wp-letsencrypt-ssl')) . "<br>
      <br>
      " . esc_html__("Revert url format", 'wp-letsencrypt-ssl') . ": http://yourdomainname.com/?reverthttps=SECRETCODE<br>
      " . esc_html__("Example:", 'wp-letsencrypt-ssl') . " http://gowebsmarty.in/?reverthttps=wple43643sg5qaw<br>
      <br>
      " . esc_html__("We have spent several hours to craft this plugin to perfectness. Please take a moment to rate us with 5 stars", 'wp-letsencrypt-ssl') . " - https://wordpress.org/support/plugin/wp-letsencrypt-ssl/reviews/#new-post
      <br />";


    wp_mail($to, $sub, $body, $header);
  }
}

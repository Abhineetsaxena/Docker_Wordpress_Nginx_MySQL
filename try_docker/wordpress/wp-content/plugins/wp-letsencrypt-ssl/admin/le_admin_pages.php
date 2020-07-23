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

require_once WPLE_DIR . 'admin/le_admin_page_wrapper.php';

class WPLE_SubAdmin extends WPLE_Admin_Page
{

  public function __construct()
  {
    add_action('admin_menu', [$this, 'wple_register_admin_pages']);
    add_action('admin_init', [$this, 'wple_force_https_handler']);
  }

  /**
   * Register sub pages
   *
   * @since 5.0.0
   * @return void
   */
  public function wple_register_admin_pages()
  {
    add_submenu_page('wp_encryption', 'Force HTTPS', __('Force HTTPS', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_force_https', [$this, 'wple_force_https_page']);

    if (!wple_fs()->is_plan('firewall', true)) {
      add_submenu_page('wp_encryption', 'FAQ', __('FAQ', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_faq', [$this, 'wple_faq_page']);
      add_submenu_page('wp_encryption', 'How-To Videos', __('How-To Videos', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_howto_videos', [$this, 'wple_howto_page']);
    }
  }

  /**
   * Force HTTPS page
   *
   * @since 5.0.0
   * @source le_admin.php moved
   * @return void
   */
  public function wple_force_https_page()
  {
    $leopts = get_option('wple_opts');
    $checked = (isset($leopts['force_ssl']) && $leopts['force_ssl']) ? 'checked' : '';

    $page = "<h2>" . __('Force HTTPS', 'wp-letsencrypt-ssl') . "</h2>
    <div class=\"wple-force\">
      <p>" . $this->wple_kses(__("If you still don't see a green padlock or notice <b>mixed content</b> warning in your browser console - please enable the below option to force HTTPS on all resources of site.", 'wp-letsencrypt-ssl')) . " " . $this->wple_kses(__("Along with this step, please have a look at <strong>'Images not loading on HTTPS site'</strong> question in FAQ tab to make sure 100% HTTPS is enforced.", "wp-letsencrypt-ssl")) . "</p>
      <p><strong>" . esc_html__('WARNING', 'wp-letsencrypt-ssl') . ":</strong> " . esc_html__("Use this feature with CAUTION. Enabling this will immediately force https and might cause issues accessing the site IF you have NOT installed the SSL certificate properly. If you face difficulty accessing site - You will need to manually revert back to http:// as explained here -", 'wp-letsencrypt-ssl') . " <b>https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/</b></p>";

    $page .= '<form method="post">
      <label class="checkbox-label" style="float:left">
      <input type="checkbox" name="wple_forcessl" value="1" ' . $checked . '>
        <span class="checkbox-custom rectangular"></span>
      </label>

      <label>' . esc_html__('Force SSL throughout the entire site', 'wp-letsencrypt-ssl') . '</label><br />
      ' . wp_nonce_field('wpleforcessl', 'site-force-ssl', false, false) . '
      <button type="submit" name="wple_ssl">' . esc_html__('Save', 'wp-letsencrypt-ssl') . '</button>
      </form>
    </div>';

    $this->generate_page($page);
  }

  /**
   * Force HTTPS Handler
   *
   * @since 5.0.0
   * @source le_admin.php moved
   * @return void
   */
  public function wple_force_https_handler()
  {

    //force ssl
    if (isset($_POST['site-force-ssl'])) {
      if (!wp_verify_nonce($_POST['site-force-ssl'], 'wpleforcessl')) {
        die('Unauthorized request');
      }

      $basedomain = str_ireplace(array('http://', 'https://'), array('', ''), site_url());

      //4.7
      if (FALSE != stripos($basedomain, '/')) {
        $basedomain = substr($basedomain, 0, stripos($basedomain, '/'));
      }

      //4.7.2
      $streamContext = stream_context_create([
        'ssl' => [
          'verify_peer' => true,
        ],
      ]);

      $errorDescription = $errorNumber = '';

      $client = @stream_socket_client(
        "ssl://$basedomain:443",
        $errorNumber,
        $errorDescription,
        30,
        STREAM_CLIENT_CONNECT,
        $streamContext
      );

      $reverter = uniqid('wple');

      $leopts = get_option('wple_opts');
      $leopts['force_ssl'] = isset($_POST['wple_forcessl']) ? 1 : 0;

      if (!$client && $leopts['force_ssl'] == 1) {
        $nossl = '<p>' . esc_html__('We could not detect valid SSL on your site!. Please double check SSL certificate is properly installed on your cPanel / Server.', 'wp-letsencrypt-ssl') . ' - ' . esc_html($errorDescription) . '</p>';

        $nossl .= '<p>' . esc_html__('Switching to HTTPS without properly installing the SSL certificate might break your site.', 'wp-letsencrypt-ssl') . '</p>';

        $nossl .= '<a href="?page=wp_encryption&forceenablehttps=' . wp_create_nonce('hardforcessl') . '" style="background: #f55656; color: #fff; padding: 10px; text-decoration: none; border-radius: 5px;        display: inline-block; margin:0 0 10px;"><strong>' . esc_html__('CLICK TO FORCE ENABLE HTTPS (Do it at your own risk)', 'wp-letsencrypt-ssl') . '</strong></a><br />
        <small>' . sprintf(esc_html__('In case you break the site, here is revert back to HTTP:// instructions - %s', 'wp-letsencrypt-ssl'), 'https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/') . '</small>';

        wp_die($nossl);
        exit();
      }

      if ($leopts['force_ssl'] == 1) {
        $leopts['revertnonce'] = $reverter;
      }

      update_option('wple_opts', $leopts);

      if ($leopts['force_ssl'] == 1) {

        //since 5.0.0
        if (wple_fs()->is_plan('firewall', true)) {

          update_option('siteurl', str_ireplace('https:', 'http:', get_option('siteurl')));
          update_option('home', str_ireplace('https:', 'http:', get_option('home')));
        } else {
          $this->wple_send_reverter_secret($reverter);

          update_option('siteurl', str_ireplace('http:', 'https:', get_option('siteurl')));
          update_option('home', str_ireplace('http:', 'https:', get_option('home')));
        }
      } else {
        update_option('siteurl', str_ireplace('https:', 'http:', get_option('siteurl')));
        update_option('home', str_ireplace('https:', 'http:', get_option('home')));
      }

      wp_redirect(admin_url('admin.php?page=wp_encryption_force_https&successnotice=1'));
      exit();
    }

    //HARD force ssl since 4.7.2
    if (isset($_GET['forceenablehttps'])) {
      if (!wp_verify_nonce($_GET['forceenablehttps'], 'hardforcessl')) {
        die('Unauthorized request');
      }

      $reverter = uniqid('wple');

      $leopts = get_option('wple_opts');
      $leopts['force_ssl'] = 1;
      $leopts['revertnonce'] = $reverter;

      update_option('wple_opts', $leopts);

      $this->wple_send_reverter_secret($reverter);

      update_option('siteurl', str_ireplace('http:', 'https:', get_option('siteurl')));
      update_option('home', str_ireplace('http:', 'https:', get_option('home')));

      wp_redirect(admin_url('admin.php?page=wp_encryption_force_https&successnotice=1'));
      exit();
    }
  }

  /**
   * FAQ
   * 
   * @since 5.0.0   
   * @source le_admin.php moved
   * @return void
   */
  public function wple_faq_page()
  {
    $page = '<h2>' . esc_html__('FREQUENTLY ASKED QUESTIONS', 'wp-letsencrypt-ssl') . '</h2>
    <h4>' . esc_html__('Does installing the plugin will instantly turn my site https?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . esc_html__('Installing SSL certificate is a server side process and not as simple as installing a ready widget and using it instantly. You will have to follow some simple steps to install SSL for your WordPress site. Our plugin acts like a tool to generate and install SSL for your WordPress site. On FREE version of plugin - You should manually go through the SSL certificate installation process following the simple video tutorial. Whereas, the SSL certificates are easily generated by our plugin by running a simple SSL generation form.', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('How to install SSL for both www & non-www version of my domain?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses('First of all, Please make sure you can access your site with and without www. Otherwise you will be not able to complete domain verification for both www & non-www together. Open <strong>WP Encryption</strong> page with <strong>&includewww=1</strong> appended to end of the URL (Ex: <strong>/wp-admin/admin.php?page=wp_encryption&includewww=1</strong>) and run the SSL form with <strong>"Generate SSL for both www & non-www"</strong> option checked.', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('Images not loading on HTTPS site', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . esc_html__('Images on your site might be loading over http:// protocol, please enable "Force HTTPS" feature via WP Encryption page. If you have Elementor page builder installed, please go to Elementor > Tools > Replace URL and replace your http:// site url with https://. Make sure you have SSL certificates installed and browser padlock shows certificate(valid) before forcing these https measures.', 'wp-letsencrypt-ssl') . '</p>
      <p>' . esc_html__('If you are still not seeing padlock, We recommend testing your site at whynopadlock.com to determine the exact issue. If you have any image sliders, background images might be loading over http:// url instead of https:// and causing mixed content issues thus making padlock to not show.', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('How do I renew my SSL certificate before expiry date?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses(__('Your SSL certificate will be auto renewed if you have <b>WP Encryption PRO</b> plugin purchased (SSL certs will be auto renewed in background just before the expiry date). If you have free version of plugin installed, You can use the same process of "Generate free SSL" to get new certs.', 'wp-letsencrypt-ssl')) . '</p>
      <hr>
      <h4>' . esc_html__('How do I install Wildcard SSL?', 'wp-letsencrypt-ssl') . '</h4>      
      <p>' . $this->wple_kses(__('If you have purchased the <b>WP Encryption PRO</b> version, You can notice a new tab for One click Wildcard SSL generation and installation.', 'wp-letsencrypt-ssl')) . '</p>
      <hr>      
      <h4>' . esc_html__('How to test if my SSL installation is good?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses(sprintf(
      __('You can run a SSL test by entering your website url in <a href="%s" rel="%s">SSL Labs</a> site.', 'wp-letsencrypt-ssl'),
      'https://www.ssllabs.com/ssltest/',
      'nofollow'
    ), 'a') . '</p>
      <hr>
      <h4>' . esc_html__('How to revert back to HTTP in case of force HTTPS failure?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . esc_html__('Please follow the revert back instructions given in [support forum](https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/).', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('Have a different question?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses(sprintf(
      __('Please use our <a href="%s" target="%s">Plugin support forum</a>. <b>PRO</b> users can register free account & use priority support at gowebsmarty.in', 'wp-letsencrypt-ssl'),
      'https://wordpress.org/support/plugin/wp-letsencrypt-ssl/',
      '_blank'
    ), 'a') . '</p>';

    $this->generate_page($page);
  }

  /**
   * How-To Videos
   * 
   * @since 5.0.0
   * @source le_admin.php moved
   * @return void
   */
  public function wple_howto_page()
  {
    $page = '<h2>' . __('How-To Videos', 'wp-letsencrypt-ssl') . '</h2>
    <h3>' . esc_html__("How to complete domain verification via DNS challenge?", 'wp-letsencrypt-ssl') . '</h3>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/BBQL69PDDrk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    
    <h3 style="margin-top: 20px;">' . esc_html__("How to install SSL Certificate on cPanel?", 'wp-letsencrypt-ssl') . '</h3>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/KQ2HYtplPEk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>  
       
    <h3 style="margin-top: 20px;">' . esc_html__("How to install SSL Certificate on Non-cPanel site via SSH access?", 'wp-letsencrypt-ssl') . '</h3>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/PANs_C2SI5Q" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>  
      
    <h3 style="margin-top: 20px;">' . esc_html__("PRO - Automate DNS verification for Godaddy", 'wp-letsencrypt-ssl') . '</h3>  
    <iframe width="560" height="315" src="https://www.youtube.com/embed/7Dztj-02Ebg" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

    <div class="le-other-plugins">
      <a href="https://wordpress.org/plugins/modern-addons-elementor/" target="_blank">
        <img src="' . WPLE_URL . 'admin/assets/modern-addons.png"/>
      </a>
    </div>
      
    <h2 style="margin:30px 0 20px">Try our social sharing plugin with share analytics!</h2>  
    <a href="https://wordpress.org/plugins/go-viral/" target="_blank"><img src="' . WPLE_URL . 'admin/assets/go-viral.jpg"/></a>';

    $this->generate_page($page);
  }
}

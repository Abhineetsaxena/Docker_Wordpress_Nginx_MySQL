<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 4.7.0
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

/**
 * Sub-directory http challenge
 *
 * @since 4.7.0
 */
class WPLE_Subdir_Challenge_Helper
{
  public static function show_challenges($opts)
  {
    if (!isset($opts['challenge_files']) || !isset($opts['dns_challenges'])) {
      return 'Could not retrieve domain verification challenges. Please go back and try again.';
    }

    return '<h2>Please verify your domain ownership by completing one of the below challenges:</h2>
    <h3 style="margin: 0 0 20px;color: #666;">PRO version offers automated domain verification + auto renewal of SSL certificate</h3>
    <div class="subdir-challenges-block">    
    <div class="subdir-http-challenge manualchallenge">' . SELF::HTTP_challenges_block($opts['challenge_files']) . '</div>
    <div class="subdir-dns-challenge manualchallenge">' . SELF::DNS_challenges_block($opts['dns_challenges']) . '</div>
    </div>';
  }

  public static function HTTP_challenges_block($challenges)
  {
    $list = '<h3>HTTP Challenges</h3>
    <p><b>Step 1:</b> Download HTTP challenge files below</p>';

    $nc = wp_create_nonce('subdir_ch');
    for ($i = 0; $i < count($challenges); $i++) {
      $j = $i + 1;
      $list .= '<a href="?page=wp_encryption&subdir_chfile=' . $j . '&nc=' . $nc . '"><span class="dashicons dashicons-download"></span>&nbsp;Download File ' . $j . '</a><br />';
    }

    $list .= '
    <p><b>Step 2:</b> Open FTP or File Manager on your hosting panel</p>
    <p><b>Step 3:</b> Navigate to your <b>primary domain</b> folder. Create <b>.well-known</b> folder and create <b>acme-challenge</b> folder inside .well-known folder.</p>
    <p><b>Step 4:</b> Upload the above downloaded challenge files into acme-challenge folder</p>
    
    ' . wp_nonce_field('verifyhttprecords', 'checkhttp', false, false) . '
    <button id="verify-subhttp" class="subdir_verify"><span class="dashicons dashicons-update"></span>&nbsp;Verify HTTP Challenges</button>

    <div class="http-notvalid">' . esc_html__('Could not verify HTTP challenges. Please check whether HTTP challenge files uploaded to acme-challenge folder is publicly accessible.', 'wp-letsencrypt-ssl') . '</div>';

    return $list;
  }

  public static function DNS_challenges_block($challenges)
  {
    $list = '<h3>DNS Challenges</h3>
    <p><b>Step 1:</b> Go to your domain DNS manager. Add below TXT records using add TXT record option.</p>';

    $dmn = str_ireplace(array('https://', 'http://', 'www.'), '', site_url());
    for ($i = 0; $i < count($challenges); $i++) {

      $pdomain = substr($dmn, 0, stripos($dmn, '/'));
      $list .= '<div class="subdns-item">
      Name: <b>_acme-challenge.' . esc_html($pdomain) . '</b> or <b>_acme-challenge</b><br>
      TTL: <b>60</b> or <b>Lowest</b> possible value<br>
      Value: <b>' . esc_html($challenges[$i]) . '</b>
      </div>';
    }

    $list .= '
    <p><b>Step 2:</b> Please wait 5-10Mins for newly added DNS to propagate and then verify DNS using below button.</p>

    ' . wp_nonce_field('verifydnsrecords', 'checkdns', false, false) . '
    <button id="verify-subdns" class="subdir_verify"><span class="dashicons dashicons-update"></span>&nbsp;Verify DNS Challenges</button>

    <div class="dns-notvalid">' . esc_html__('Could not verify DNS records. Please check whether you have added above DNS records perfectly or try again after 5 minutes if you added DNS records just now.', 'wp-letsencrypt-ssl') . '</div>';

    return $list;
  }

  public static function download_challenge_files()
  {
    if (isset($_GET['subdir_chfile'])) {

      if (!wp_verify_nonce($_GET['nc'], 'subdir_ch')) {
        die('Unauthorized request. Please try again.');
      }

      $opts = get_option('wple_opts');

      if (isset($opts['challenge_files']) && !empty($opts['challenge_files'])) {
        $req = intval($_GET['subdir_chfile']) - 1;
        $ch = $opts['challenge_files'][$req];

        if (!isset($ch)) {
          wp_die('Requested challenge file not exists. Please go back and try again.');
        }

        SELF::compose_challenge_files($ch['file'], $ch['value']);
      } else {
        wp_die('HTTP challenge files not ready. Please go back and try again.');
      }
    }
  }

  private static function compose_challenge_files($name, $content)
  {
    $file = sanitize_file_name($name);
    file_put_contents($file, sanitize_text_field($content));

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename=' . basename($file));

    readfile($file);
    exit();
  }
}

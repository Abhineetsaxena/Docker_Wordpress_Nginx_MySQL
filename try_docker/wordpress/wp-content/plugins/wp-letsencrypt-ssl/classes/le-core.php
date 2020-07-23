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
/**
 * require all the lib files for generating certs
 */
require_once WPLE_DIR . 'lib/LEFunctions.php';
require_once WPLE_DIR . 'lib/LEConnector.php';
require_once WPLE_DIR . 'lib/LEAccount.php';
require_once WPLE_DIR . 'lib/LEAuthorization.php';
require_once WPLE_DIR . 'lib/LEClient.php';
require_once WPLE_DIR . 'lib/LEOrder.php';
use  LEClient\LEClient ;
use  LEClient\LEOrder ;
/**
 * WPLE_Core class
 * Responsible for handling account registration, certificate generation & install certs on cPanel
 * 
 * @since 1.0.0  
 */
class WPLE_Core
{
    protected  $email ;
    protected  $date ;
    protected  $basedomain ;
    protected  $domains ;
    protected  $mdomain = false ;
    protected  $client ;
    protected  $order ;
    protected  $pendings ;
    protected  $wcard = false ;
    protected  $dnss = false ;
    protected  $iscron = false ;
    protected  $noscriptresponse = false ;
    /**
     * construct all params & proceed with cert generation
     *
     * @since 1.0.0
     * @param array $opts
     * @param boolean $gen
     */
    public function __construct(
        $opts = array(),
        $gen = true,
        $wc = false,
        $dnsverify = false
    )
    {
        
        if ( !empty($opts) ) {
            $this->email = sanitize_email( $opts['email'] );
            $this->date = $opts['date'];
            $optss = $opts;
        } else {
            $optss = get_option( 'wple_opts' );
            $this->email = ( isset( $optss['email'] ) ? sanitize_email( $optss['email'] ) : '' );
            $this->date = ( isset( $optss['date'] ) ? $optss['date'] : '' );
        }
        
        $siteurl = site_url();
        if ( isset( $optss['subdir'] ) ) {
            $siteurl = sanitize_text_field( $optss['domain'] );
        }
        $this->basedomain = str_ireplace( array( 'http://', 'https://' ), array( '', '' ), $siteurl );
        $this->domains = array( $this->basedomain );
        //include both www & non-www
        
        if ( isset( $optss['include_www'] ) && $optss['include_www'] == 1 ) {
            $this->basedomain = str_ireplace( 'www.', '', $this->basedomain );
            $this->domains = array( $this->basedomain, 'www.' . $this->basedomain );
        }
        
        if ( $dnsverify ) {
            //manual dns verify
            $this->dnss = true;
        }
        if ( $gen ) {
            $this->wple_generate_verify_ssl( $optss );
        }
    }
    
    /**
     * group all different steps into one function & clear debug.log intially.
     *
     * @since 1.0.0
     * @return void
     */
    public function wple_generate_verify_ssl( $opts = array() )
    {
        update_option( 'wple_progress', 0 );
        $init = (int) get_option( 'wple_go_plan' );
        //since 4.7
        
        if ( !isset( $_GET['wpleauto'] ) ) {
            $PRO = ( wple_fs()->can_use_premium_code__premium_only() ? 'PRO' : '' );
            $PRO .= ( $this->wcard ? ' WILDCARD SSL ' : ' SINGLE DOMAIN SSL ' );
            $PRO .= $init;
            $this->wple_log( '<b>' . WPLE_VERSION . ' ' . $PRO . '</b>', 'success', 'w' );
        }
        
        $this->wple_create_client();
        $this->wple_generate_order();
        $this->wple_verify_pending_orders();
        $this->wple_generate_certs();
        if ( isset( $_POST['wple_send_usage'] ) ) {
            $this->wple_send_usage_data();
        }
    }
    
    /**
     * create ACMEv2 client
     *
     * @since 1.0.0
     * @return void
     */
    protected function wple_create_client()
    {
        try {
            $keydir = ABSPATH . 'keys/';
            $this->client = new LEClient(
                $this->email,
                false,
                LEClient::LOG_STATUS,
                $keydir
            );
        } catch ( Exception $e ) {
            update_option( 'wple_error', 1 );
            $this->wple_log(
                "CREATE_CLIENT:" . $e,
                'error',
                'w',
                true
            );
        }
        ///echo '<pre>'; print_r( $client->getAccount() ); echo '</pre>';
    }
    
    /**
     * Generate order with ACMEv2 client for given domain
     *
     * @since 1.0.0
     * @return void
     */
    protected function wple_generate_order()
    {
        ///$this->wple_log($this->basedomain . json_encode($this->domains), 'success', 'a');
        try {
            $this->order = $this->client->getOrCreateOrder( $this->basedomain, $this->domains );
        } catch ( Exception $e ) {
            update_option( 'wple_error', 1 );
            $this->wple_log(
                "CREATE_ORDER:" . $e,
                'error',
                'w',
                true
            );
        }
    }
    
    /**
     * Get all pendings orders which need domain verification
     *
     * @since 1.0.0
     * @return void
     */
    protected function wple_get_pendings( $dns = false )
    {
        $chtype = LEOrder::CHALLENGE_TYPE_HTTP;
        $http = 1;
        
        if ( $this->dnss || $dns ) {
            $chtype = LEOrder::CHALLENGE_TYPE_DNS;
            $http = 0;
        }
        
        try {
            $this->pendings = $this->order->getPendingAuthorizations( $chtype );
            
            if ( !empty($this->pendings) && $http == 1 ) {
                $opts = get_option( 'wple_opts' );
                $opts['challenge_files'] = array();
                foreach ( $this->pendings as $chlng ) {
                    $opts['challenge_files'][] = array(
                        'file'  => sanitize_text_field( trim( $chlng['filename'] ) ),
                        'value' => sanitize_text_field( trim( $chlng['content'] ) ),
                    );
                }
                update_option( 'wple_opts', $opts );
            }
        
        } catch ( Exception $e ) {
            $this->wple_log(
                'GET_PENDING_AUTHS:' . $e,
                'error',
                'w',
                true
            );
        }
    }
    
    /**
     * verify all the challenges via HTTP
     *
     * @since 1.0.0
     * @return void
     */
    protected function wple_verify_pending_orders( $forcehttpverify = false, $forcednsverify = false, $is_cron = false )
    {
        $this->iscron = $is_cron;
        ///$this->order->deactivateOrderAuthorization($this->basedomain);
        ///$this->order->revokeCertificate();
        ///exit();
        
        if ( !$this->order->allAuthorizationsValid() ) {
            //since 4.7
            $this->wple_override_subdir_logic();
            $this->wple_save_dns_challenges();
            
            if ( $forcednsverify || $this->wcard ) {
                //dns verify
                $this->wple_get_pendings( true );
            } else {
                $this->wple_get_pendings();
            }
            
            
            if ( !empty($this->pendings) ) {
                $site = str_ireplace( 'www.', '', $this->basedomain );
                $vrfy = '';
                
                if ( $this->dnss ) {
                    $this->wple_log( esc_html__( "Verify your domain by adding the below TXT records to your domain DNS records (Refer FAQ for video tutorial on how to add these DNS records)", 'wp-letsencrypt-ssl' ) . "\n", 'success', 'a' );
                    $this->reloop_get_dns();
                } else {
                    ///$this->wple_log(json_encode($this->pendings), 'success', 'a');
                }
                
                foreach ( $this->pendings as $challenge ) {
                    
                    if ( $challenge['type'] == 'dns-01' && stripos( $challenge['identifier'], $site ) !== FALSE ) {
                        if ( $this->wcard && !$forcednsverify && !$this->dnss ) {
                        }
                        if ( $this->dnss ) {
                            //manual dns verify
                            $this->order->verifyPendingOrderAuthorization( $challenge['identifier'], LEOrder::CHALLENGE_TYPE_DNS );
                        }
                    } else {
                        if ( $challenge['type'] == 'http-01' && stripos( $challenge['identifier'], $site ) >= 0 ) {
                            
                            if ( !$this->dnss && !$forcednsverify ) {
                                ///$acmefile = site_url('/.well-known/acme-challenge/' . $challenge['filename'], 'http');
                                $acmefile = "http://" . $challenge['identifier'] . "/.well-known/acme-challenge/" . $challenge['filename'];
                                $this->wple_deploy_challenge_files( $acmefile, $challenge );
                                $rsponse = $this->wple_get_file_response( $acmefile );
                                
                                if ( $rsponse != trim( $challenge['content'] ) ) {
                                    update_option( 'wple_error', 2 );
                                    $this->wple_log( esc_html__( "Could not verify challenge code in above http challenge file. Please make sure its publicly accessible or contact your hosting support to make it public.", 'wp-letsencrypt-ssl' ) . " \n", 'success', 'a' );
                                    $hdrs = wp_remote_head( $acmefile );
                                    
                                    if ( is_wp_error( $hdrs ) ) {
                                        $statuscode = 0;
                                    } else {
                                        $statuscode = $hdrs['response']['code'];
                                    }
                                    
                                    if ( $statuscode != 200 ) {
                                        // not accessible
                                        $this->try_n_prompt_dns();
                                    }
                                }
                                
                                $this->order->verifyPendingOrderAuthorization( $challenge['identifier'], LEOrder::CHALLENGE_TYPE_HTTP );
                            }
                        
                        }
                    }
                
                }
            }
        
        }
    
    }
    
    /**
     * Finalize and get certificates
     *
     * @since 1.0.0
     * @return void
     */
    public function wple_generate_certs( $rectify = true )
    {
        ///$this->wple_generate_order();
        
        if ( $this->order->allAuthorizationsValid() ) {
            // Finalize the order
            
            if ( !$this->order->isFinalized() ) {
                $this->wple_log( esc_html__( 'Finalizing the order', 'wp-letsencrypt-ssl' ), 'success', 'a' );
                $this->order->finalizeOrder();
            }
            
            // get the certificate.
            
            if ( $this->order->isFinalized() ) {
                $this->wple_log( esc_html__( 'Getting SSL certificates', 'wp-letsencrypt-ssl' ), 'success', 'a' );
                $this->order->getCertificate();
            }
            
            $cert = ABSPATH . 'keys/certificate.crt';
            
            if ( file_exists( $cert ) ) {
                $this->wple_save_expiry_date();
                update_option( 'wple_error', 0 );
                $sslgenerated = "<h2>" . esc_html__( 'SSL Certificate generated successfully', 'wp-letsencrypt-ssl' ) . "!</h2>";
                $this->wple_log( $sslgenerated, 'success', 'a' );
                $this->wple_send_usage_data();
                wp_redirect( admin_url( '/admin.php?page=wp_encryption&success=1' ), 302 );
                exit;
            }
        
        } else {
            $this->wple_log( json_encode( $this->order->authorizations ), 'success', 'a' );
            update_option( 'wple_error', 2 );
            $this->wple_log(
                '<h2>' . esc_html__( 'There are some pending verifications. If new DNS records were added, please run this installation again after 5-10mins', 'wp-letsencrypt-ssl' ) . '</h2>',
                'success',
                'a',
                true
            );
        }
    
    }
    
    /**
     * Save expiry date of cert dynamically by parsing the cert
     *
     * @since 1.0.0
     * @return void
     */
    public function wple_save_expiry_date()
    {
        $certfile = ABSPATH . 'keys/certificate.crt';
        
        if ( file_exists( $certfile ) ) {
            $opts = get_option( 'wple_opts' );
            $opts['expiry'] = '';
            try {
                $this->wple_getRemainingDays( $certfile, $opts );
            } catch ( Exception $e ) {
                update_option( 'wple_opts', $opts );
                //echo $e;
                //exit();
            }
        }
    
    }
    
    /**
     * Utility functions
     * 
     * @since 1.0.0 
     */
    public function wple_parseCertificate( $cert_pem )
    {
        // if (false === ($ret = openssl_x509_read(file_get_contents($cert_pem)))) {
        //   throw new Exception('Could not load certificate: ' . $cert_pem . ' (' . $this->get_openssl_error() . ')');
        // }
        if ( !is_array( $ret = openssl_x509_parse( file_get_contents( $cert_pem ), true ) ) ) {
            throw new Exception( 'Could not parse certificate' );
        }
        return $ret;
    }
    
    public function wple_getRemainingDays( $cert_pem, $opts )
    {
        if ( isset( $opts['expiry'] ) && $opts['expiry'] != '' && wp_next_scheduled( 'wple_ssl_reminder_notice' ) ) {
            wp_unschedule_event( strtotime( '-10 day', strtotime( $opts['expiry'] ) ), 'wple_ssl_reminder_notice' );
        }
        $ret = $this->wple_parseCertificate( $cert_pem );
        $expiry = date( 'd-m-Y', $ret['validTo_time_t'] );
        $opts['expiry'] = $expiry;
        if ( $opts['expiry'] != '' ) {
            wp_schedule_single_event( strtotime( '-10 day', strtotime( $opts['expiry'] ) ), 'wple_ssl_reminder_notice' );
        }
        update_option( 'wple_opts', $opts );
        update_option( 'wple_show_review', 1 );
        do_action( 'cert_expiry_updated' );
    }
    
    public function wple_log(
        $msg = '',
        $type = 'success',
        $mode = 'a',
        $redirect = false
    )
    {
        $handle = fopen( WPLE_DEBUGGER . 'debug.log', $mode );
        if ( $type == 'error' ) {
            $msg = '<span class="error"><b>' . esc_html__( 'ERROR', 'wp-letsencrypt-ssl' ) . ':</b> ' . wp_kses_post( $msg ) . '</span>';
        }
        fwrite( $handle, wp_kses_post( $msg ) . "\n" );
        fclose( $handle );
        
        if ( $redirect ) {
            if ( isset( $_POST['wple_send_usage'] ) ) {
                $this->wple_send_usage_data();
            }
            wp_redirect( admin_url( '/admin.php?page=wp_encryption&error=1' ), 302 );
            die;
        }
    
    }
    
    /**
     * Collect usage data to improve plugin
     *
     * @since 2.1.0
     * @return void
     */
    public function wple_send_usage_data()
    {
        $readlog = file_get_contents( WPLE_DEBUGGER . 'debug.log' );
        $handle = curl_init();
        $srvr = array(
            'challenge_folder_exists' => file_exists( ABSPATH . '.well-known/acme-challenge' ),
            'certificate_exists'      => file_exists( ABSPATH . 'keys/certificate.crt' ),
            'server_software'         => $_SERVER['SERVER_SOFTWARE'],
            'http_host'               => $_SERVER['HTTP_HOST'],
            'pro'                     => ( wple_fs()->is__premium_only() ? 'PRO' : 'FREE' ),
        );
        $curlopts = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST           => 1,
            CURLOPT_URL            => 'https://gowebsmarty.in/?catchwple=1',
            CURLOPT_HEADER         => false,
            CURLOPT_POSTFIELDS     => array(
            'response' => $readlog,
            'server'   => json_encode( $srvr ),
        ),
        );
        curl_setopt_array( $handle, $curlopts );
        curl_exec( $handle );
        curl_close( $handle );
    }
    
    /**
     * Show DNS records for domain verification
     *
     * @since 2.2.0
     * @return void
     */
    private function reloop_get_dns( $return = false )
    {
        $site = str_ireplace( 'www.', '', $this->basedomain );
        $vrfy = '';
        $this->wple_get_pendings( true );
        $dns_records = array();
        foreach ( $this->pendings as $challenge ) {
            
            if ( $challenge['type'] == 'dns-01' && stripos( $challenge['identifier'], $site ) !== FALSE ) {
                $vrfy .= 'Name: <b>_acme-challenge.' . $site . '</b> or <b>_acme-challenge</b>
          TTL: <b>60</b> or <b>Lowest</b> possible value
          Type: <b>TXT</b>
          Value: <b>' . esc_html( $challenge['DNSDigest'] ) . '</b><br>
          ';
                $dns_records[] = esc_html( $challenge['DNSDigest'] );
            }
        
        }
        if ( $return ) {
            return $dns_records;
        }
        $this->wple_log( $vrfy, 'success', 'a' );
    }
    
    /**
     * Try overriding http challenge access or prompt for dns challenge
     *
     * @param string $fpath
     * @param array $challenge
     * @since 2.2.0
     * @return void
     */
    private function try_n_prompt_dns()
    {
        $this->wple_log( esc_html__( "Alternatively, You can manually verify your domain by adding the below TXT records to your domain DNS records (Refer FAQ for video tutorial on how to add these DNS records)\n", 'wp-letsencrypt-ssl' ), 'success', 'a' );
        $this->wple_dns_promo();
        //$this->wple_newbie_promo();
        $this->reloop_get_dns();
        $dns_verify = '<h2><a href="?page=wp_encryption&dnsverify=1" style="font-weight:bold">' . esc_html__( 'Click here to continue DNS verification IF you manually added the above DNS records', 'wp-letsencrypt-ssl' ) . '</a></h2>';
        $this->wple_log(
            $dns_verify,
            'success',
            'a',
            true
        );
    }
    
    /**
     * PRO promo on http challenge fail
     *
     * @since 2.4.0
     * @return void
     */
    public function wple_dns_promo( $redirect = false )
    {
        $pro_automate = '<div class="wple-promo"><b>WP Encryption PRO</b> ' . esc_html__( 'can automate this DNS verification process IF your DNS is managed by your cPanel or Godaddy. Buy PRO version today & forget all these difficulties.', 'wp-letsencrypt-ssl' ) . '</div>';
        $nocpanelwc = esc_html__( "Unfortunately, this server dont seem to have cPanel installed so WP Encryption PRO cannot automate DNS verification process. You will have to manually add below DNS records for domain verification to succeed.\n", 'wp-letsencrypt-ssl' );
        $nocpanel = $this->wple_kses( __( "Unfortunately, this server dont seem to have cPanel installed so WP Encryption PRO cannot automate DNS verification process. You will have to manually add below DNS records or contact your hosting support to allow access to <b>.well-known</b> folder for domain verification to succeed.\n", 'wp-letsencrypt-ssl' ) );
        $var = '';
        $this->wple_log(
            $pro_automate . "\n",
            'success',
            'a',
            $redirect
        );
        
        if ( function_exists( 'shell_exec' ) ) {
            
            if ( !empty(shell_exec( 'which cpapi2' )) ) {
            } else {
            }
        
        } else {
            
            if ( function_exists( 'system' ) ) {
                ob_start();
                system( "which cpapi2", $var );
                $shll = ob_get_contents();
                ob_end_clean();
                
                if ( !empty($shll) ) {
                } else {
                }
            
            } else {
                
                if ( function_exists( 'passthru' ) ) {
                    ob_start();
                    passthru( "which cpapi2", $var );
                    $shll = ob_get_contents();
                    ob_end_clean();
                    
                    if ( !empty($shll) ) {
                    } else {
                    }
                
                } else {
                    
                    if ( function_exists( 'exec' ) ) {
                        exec( "which cpapi2", $output, $var );
                        
                        if ( !empty($output) ) {
                        } else {
                        }
                    
                    } else {
                    }
                
                }
            
            }
        
        }
    
    }
    
    /**
     * Deploy challenge files
     *
     * @since 3.2.0
     * @param array $challenge
     * @return void
     */
    private function wple_deploy_challenge_files( $acmefile, $challenge )
    {
        $fpath = ABSPATH . '.well-known/acme-challenge/';
        if ( !file_exists( $fpath ) ) {
            mkdir( $fpath, 0775, true );
        }
        $this->wple_log( esc_html__( 'Creating HTTP challenge file', 'wp-letsencrypt-ssl' ) . ' ' . $acmefile, 'success', 'a' );
        
        if ( file_exists( $fpath . $challenge['filename'] ) ) {
            unlink( $fpath . $challenge['filename'] );
            //remove existing
        }
        
        file_put_contents( $fpath . $challenge['filename'], trim( $challenge['content'] ) );
    }
    
    /**
     * Retrieve file content
     *
     * @since 3.2.0
     * @param string $acmefile
     * @return void
     */
    private function wple_get_file_response( $acmefile )
    {
        $args = array(
            'sslverify' => false,
        );
        $remoteget = wp_remote_get( $acmefile, $args );
        
        if ( is_wp_error( $remoteget ) ) {
            $rsponse = 'error';
        } else {
            $rsponse = trim( wp_remote_retrieve_body( $remoteget ) );
        }
        
        return $rsponse;
    }
    
    /**
     * Escape html but retain bold
     *
     * @since 3.3.3
     * @param string $translated
     * @param string $additional Additional allowed html tags
     * @return void
     */
    private function wple_kses( $translated, $additional = '' )
    {
        $allowed = array(
            'strong' => array(),
            'b'      => array(),
        );
        if ( $additional == 'a' ) {
            $allowed['a'] = array(
                'href'   => array(),
                'rel'    => array(),
                'target' => array(),
                'title'  => array(),
            );
        }
        return wp_kses( $translated, $allowed );
    }
    
    /**
     * Save DNS challenges for later use
     *
     * @since 4.6.0
     * @return void
     */
    private function wple_save_dns_challenges()
    {
        $chtype = LEOrder::CHALLENGE_TYPE_DNS;
        $dns_challenges = $this->order->getPendingAuthorizations( $chtype );
        $site = str_ireplace( 'www.', '', $this->basedomain );
        
        if ( !empty($dns_challenges) ) {
            $opts = ( FALSE === get_option( 'wple_opts' ) ? array() : get_option( 'wple_opts' ) );
            foreach ( $dns_challenges as $challenge ) {
                if ( $challenge['type'] == 'dns-01' && stripos( $challenge['identifier'], $site ) !== FALSE ) {
                    $opts['dns_challenges'][] = sanitize_text_field( $challenge['DNSDigest'] );
                }
            }
            update_option( 'wple_opts', $opts );
        }
    
    }
    
    /**
     * Detect sub-dir site & act accordingly
     *
     * @since 4.7.0
     * @return void
     */
    private function wple_override_subdir_logic()
    {
        $opts = get_option( 'wple_opts' );
        
        if ( isset( $opts['subdir'] ) && !isset( $_GET['wpleauto'] ) ) {
            $this->wple_log( 'Cleaning & re-generating challenges', 'success', 'a' );
            if ( isset( $opts['challenge_files'] ) ) {
                unset( $opts['challenge_files'] );
            }
            if ( isset( $opts['dns_challenges'] ) ) {
                unset( $opts['dns_challenges'] );
            }
            $this->wple_get_pendings();
            $this->wple_save_dns_challenges();
            wp_redirect( admin_url( '/admin.php?page=wp_encryption&subdir=1' ), 302 );
            exit;
        }
    
    }

}
///$this->order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_HTTP);
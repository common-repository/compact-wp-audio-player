<?php
/*
  Plugin Name: Compact Audio Player
  Plugin URI: https://www.tipsandtricks-hq.com/wordpress-audio-music-player-plugin-4556
  Description: Plays a specified audio file (.mp3 or .ogg) using a simple and compact audio player. The audio player is compatible with all major browsers and devices (Android, iPhone).
  Version: 1.9.14
  Author: Tips and Tricks HQ
  Author URI: https://www.tipsandtricks-hq.com/
  License: GPL
 */

 //Prefix - scap_

define('SC_AUDIO_PLUGIN_VERSION', '1.9.14');
define('SC_AUDIO_BASE_URL', plugins_url('/', __FILE__));
define('SC_AUDIO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

include_once( SC_AUDIO_PLUGIN_PATH . 'scap-utility-functions.php' );
include_once( SC_AUDIO_PLUGIN_PATH . 'shortcodes-functions.php' );

add_action('init', 'wp_sc_audio_init');

function wp_sc_audio_init() {
    if (!is_admin()) {
        wp_register_script('scap.soundmanager2', SC_AUDIO_BASE_URL . 'js/soundmanager2-nodebug-jsmin.js');
        wp_enqueue_script('scap.soundmanager2');
        wp_register_style('scap.flashblock', SC_AUDIO_BASE_URL . 'css/flashblock.css');
        wp_enqueue_style('scap.flashblock');
        wp_register_style('scap.player', SC_AUDIO_BASE_URL . 'css/player.css');
        wp_enqueue_style('scap.player');
    }
}

// Add the settings link
function scap_add_settings_link( $links, $file ) {
    if ( $file == plugin_basename( __FILE__ ) ) {
	$settings_link = '<a href="options-general.php?page=compact-wp-audio-player%2Fsc_audio_player.php">' . (__( "Settings", "compact-audio-player" )) . '</a>';
	array_unshift( $links, $settings_link );
    }
    return $links;
}
add_filter( 'plugin_action_links', 'scap_add_settings_link', 10, 2 );

//Footer code function
function scap_footer_code() {
    $debug_marker = "<!-- WP Audio player plugin v" . SC_AUDIO_PLUGIN_VERSION . " - https://www.tipsandtricks-hq.com/wordpress-audio-music-player-plugin-4556/ -->";
    echo "\n" . $debug_marker . "\n";
    ?>
    <script type="text/javascript">
        soundManager.useFlashBlock = true; // optional - if used, required flashblock.css
        soundManager.url = '<?php echo SC_AUDIO_BASE_URL; ?>swf/soundmanager2.swf';
        function play_mp3(flg, ids, mp3url, volume, loops)
        {
            //Check the file URL parameter value
            var pieces = mp3url.split("|");
            if (pieces.length > 1) {//We have got an .ogg file too
                mp3file = pieces[0];
                oggfile = pieces[1];
                //set the file URL to be an array with the mp3 and ogg file
                mp3url = new Array(mp3file, oggfile);
            }

            soundManager.createSound({
                id: 'btnplay_' + ids,
                volume: volume,
                url: mp3url
            });

            if (flg == 'play') {
    <?php
    if (get_option('sc_audio_disable_simultaneous_play') == '1') {
        echo 'stop_all_tracks();';
    }
    ?>
                soundManager.play('btnplay_' + ids, {
                    onfinish: function() {
                        if (loops == 'true') {
                            loopSound('btnplay_' + ids);
                        }
                        else {
                            document.getElementById('btnplay_' + ids).style.display = 'inline';
                            document.getElementById('btnstop_' + ids).style.display = 'none';
                        }
                    }
                });
            }
            else if (flg == 'stop') {
    //soundManager.stop('btnplay_'+ids);
                soundManager.pause('btnplay_' + ids);
            }
        }
        function show_hide(flag, ids)
        {
            if (flag == 'play') {
                document.getElementById('btnplay_' + ids).style.display = 'none';
                document.getElementById('btnstop_' + ids).style.display = 'inline';
            }
            else if (flag == 'stop') {
                document.getElementById('btnplay_' + ids).style.display = 'inline';
                document.getElementById('btnstop_' + ids).style.display = 'none';
            }
        }
        function loopSound(soundID)
        {
            window.setTimeout(function() {
                soundManager.play(soundID, {onfinish: function() {
                        loopSound(soundID);
                    }});
            }, 1);
        }
        function stop_all_tracks()
        {
            soundManager.stopAll();
            var inputs = document.getElementsByTagName("input");
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].id.indexOf("btnplay_") == 0) {
                    inputs[i].style.display = 'inline';//Toggle the play button
                }
                if (inputs[i].id.indexOf("btnstop_") == 0) {
                    inputs[i].style.display = 'none';//Hide the stop button
                }
            }
        }
    </script>
    <?php
}

add_action('wp_footer', 'scap_footer_code');

//Create admin page
add_action('admin_menu', 'scap_mp3_player_admin_menu');

function scap_mp3_player_admin_menu() {
    add_options_page('SC Audio Player', 'SC Audio Player', 'manage_options', __FILE__, 'scap_mp3_options');
}

function scap_mp3_options() {
    echo '<div class="wrap">';
    echo '<h2>Compact Audio Player</h2>';

    echo '<div style="background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; padding: 15px 10px">Visit the <a href="https://www.tipsandtricks-hq.com/wordpress-audio-music-player-plugin-4556" target="_blank">Compact Audio Player</a> plugin page for detailed documentation and update.</div>';
    echo "<p>This is a simple all browser supported audio player. Read the documentation and add the shortcode with the MP3 file URL in a WordPress post or page to embed the audio player.</p>";
    echo "<h3>Shortcode Format</h3>";
    echo '<p><code>[sc_embed_player fileurl="URL OF THE MP3 FILE"]</code></p>';
    echo '<p><strong>Example:</strong></p>';
    echo '<p><code>[sc_embed_player fileurl="http://www.example.com/wp-content/uploads/my-music/mysong.mp3"]</code></p>';

    if (isset($_POST['sc_audio_player_settings'])) {
        //Check nonce
        $nonce = $_REQUEST[ '_wpnonce' ];
        if ( ! wp_verify_nonce( $nonce, 'scap_settings_update' ) ) {
            wp_die( 'Error! Nonce Security Check Failed! Go back to the settings menu and save the settings again.' );
        }

        update_option('sc_audio_disable_url_validation', isset($_POST["sc_audio_disable_url_validation"]) ? '1' : '');
        update_option('sc_audio_disable_simultaneous_play', isset($_POST["sc_audio_disable_simultaneous_play"]) ? '1' : '');
    }

    echo '<div id="poststuff"><div id="post-body">';
    ?>
    <form method="post" action="">

        <?php wp_nonce_field( 'scap_settings_update' ); ?>

        <div class="postbox">
            <h3 class="hndle"><label for="title">Audio Player Settings</label></h3>
            <div class="inside">

                <table class="form-table">

                    <tr valign="top">
                        <td width="25%" align="left">
                            Disable MP3 File URL Validation (Not Recommended):
                        </td>
                        <td align="left">
                            <input name="sc_audio_disable_url_validation" type="checkbox"<?php if (get_option('sc_audio_disable_url_validation') != '') echo ' checked="checked"'; ?> value="1"/>
                            <br /><p class="description">Select this option if you want to disable the mp3 file URL validation that the plguin performs on the shortcodes.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <td width="25%" align="left">
                            Disable Simultaneous Play:
                        </td>
                        <td align="left">
                            <input name="sc_audio_disable_simultaneous_play" type="checkbox"<?php if (get_option('sc_audio_disable_simultaneous_play') != '') echo ' checked="checked"'; ?> value="1"/>
                            <br /><p class="description">Check this option if you only want to allow one audio file to be played at a time (helpful if you have multiple audio files on a page). It will automatically stop the audio file that is currently playing when a user plays a new file.</p>
                        </td>
                    </tr>

                </table>

                <div class="submit">
                    <input type="submit" class="button-primary" name="sc_audio_player_settings" value="<?php _e('Update'); ?>" />
                </div>

            </div>
        </div>
    </form>

    <div style="background: #D7E7F5; border: 1px solid #1166BB; color: #333333; margin: 20px 0; padding: 10px;">
        Want to sell your audio files? You can use the <a href="https://wordpress.org/plugins/wp-express-checkout/" target="_blank">WP Express Checkout</a> or the <a href="https://wordpress.org/plugins/stripe-payments/" target="_blank">Stripe Payments</a> plugin to sell your audio files easily.
    </div>

    <?php
    echo '</div></div>'; //end of post-stuff
    echo '</div>'; //end of wrap
}

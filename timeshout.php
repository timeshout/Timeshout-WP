<?php
/*
Plugin Name: Timeshout
Plugin URI: https://github.com/cyberwombat/Timeshout-WP
Description: This plugin lets you present a Timeshout.com calendar.
Version: 0.1
Author: Sebastian
Author URI: http://timeshout.com

---------------------------------------------------------------------
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You can see a copy of GPL at <http://www.gnu.org/licenses/>
---------------------------------------------------------------------
*/

define('TIMESHOUT_VERS', '0.1');
define('TIMESHOUT_PATH', plugin_dir_path(__FILE__));
define('TIMESHOUT_URL', plugin_dir_url(__FILE__));
define('TIMESHOUT_LIB_PATH', TIMESHOUT_PATH.'lib/');
define('TIMESHOUT_TPL_PATH', TIMESHOUT_PATH.'templates/');
define('TIMESHOUT_CACHE_PATH', TIMESHOUT_PATH.'/cache/');
define('TIMESHOUT_CSS_URL', TIMESHOUT_URL.'assets/css/');
define('TIMESHOUT_JS_URL', TIMESHOUT_URL.'assets/js/');
define('TIMESHOUT_IMG_URL', TIMESHOUT_URL.'assets/images/');
define('FORMAT_NICE_DAYTIME', 'M jS, Y @ g:ia');
define('FORMAT_NICE_DAY', 'M jS');

add_shortcode('timeshout' ,'show_calendar');
add_action('admin_menu', 'timeshout_menu');

wp_register_style('timeshout', TIMESHOUT_CSS_URL.'calendar.css', array(), TIMESHOUT_VERS);
wp_enqueue_style('timeshout');

function timeshout_init()
{
    require_once(TIMESHOUT_LIB_PATH.'Timeshout.php');
    require_once(TIMESHOUT_LIB_PATH.'Cache.php');
}

function render_dates($event)
{
    if($event->start_date ==  $event->end_date)
    {
        if((string)$event->start_time == null)
            return date(FORMAT_NICE_DAY, strtotime($event->start_date));
        else
            return date(FORMAT_NICE_DAYTIME, strtotime($event->start_date.' '.$event->start_time));
    }
    else
    {
        if((string)$event->start_time == null)
            return date(FORMAT_NICE_DAY, strtotime($event->start_date)).' - '.date(FORMAT_NICE_DAY, strtotime($event->end_date));
        else
            return date(FORMAT_NICE_DAY, strtotime($event->start_date)).' - '.date(FORMAT_NICE_DAYTIME, strtotime($event->end_date.' '.$event->start_time));
    }
}

function show_calendar($attr, $content = null)
{
    timeshout_init();
    $options = maybe_unserialize(get_option('timeshout'));

    $api = new Timeshout();
        $api->setMethod('calendar.get');
        $api->setProtocol('get');
        $api->setParameters(array(
            'api'           => $options['api_key'],
            'id' => $options['calendar_id']
            ));

        $cache = new Cache();
        $expiry = (int)$options['cache_lifetime'] * 60;
        $cache->setCacheDir(TIMESHOUT_CACHE_PATH);
        $cache->setIdentifier($api->getQuery());
        $cache->setActive($expiry > 0);
        $cache->setExpiry($expiry);
        $cached = false;
        if($cache->isCached())
        {
            $api->setResponse($cache->fetchData());
            $xml = $api->getXmlObject();
            $cached = true;
        }
        else
        {
            $api->processRequest();
            if(!$api->hasError())
            {
                $xml = $api->getXmlObject();
                $cache->saveData($api->getResponse());
            }
            else
                $errors = $api->getErrorMessages();
        }

        require_once(TIMESHOUT_TPL_PATH.'calendar.get.php');
}

function timeshout_menu()
{
	add_options_page('Timeshout Options', 'Timeshout Calendar', 'manage_options', 'timeshout', 'timeshout_options');
}
function get_timeshout_options()
{
    return maybe_unserialize(get_option('timeshout'));
}
function timeshout_options()
{
    timeshout_init();

    if (!current_user_can('manage_options'))
      wp_die( __('You do not have sufficient permissions to access this page.') );

    $hidden_field_name = 'submit_hidden';
    $options = get_timeshout_options();

    if( isset($_POST['ts_submit']) && $_POST['ts_submit'] == 'Y' ):

        $options = $_POST['ts'];
        $errors = array();

        if($options['api_key'] == null)
            $errors[] = 'the API key is required';
        if($options['calendar_id'] == null)
            $errors[] = 'the calendar ID is required';

        if(count($errors))
            echo '<div class="error"><p><strong>'.implode(' and ', $errors).'.</strong></p></div>';

        update_option( 'timeshout', maybe_serialize( $options));

        echo '<div class="updated"><p><strong>'.__('settings saved.', 'timeshout' ).'</strong></p></div>';

    endif;

$form = <<<EOF
<div class="wrap">
<div id="icon-options-general" class="icon32"><br /></div><h2>%title%</h2>

<form name="form1" method="post" action="">
    <input type="hidden" name="ts_submit" value="Y">
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="posts_per_page">Your API Key</label></th>
            <td><input name="ts[api_key]" type="text" value="%api_key%" class="regular-text" /> <span class="description">get your API key <a href="http://timeshout.com/dev.php/api/manage"target="_blank">here</a></span></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="posts_per_rss">Calendar ID</label></th>
            <td><input name="ts[calendar_id]" type="text"  value="%calendar_id%" class="small-text" /></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="blog_charset">Cache lifetime</label></th>
            <td><input name="ts[cache_lifetime]" type="text" value="%cache_lifetime%" class="small-text" />
            <span class="description">Set to 0 to disable caching for testing - recommended minimum is 15</span></td>
        </tr>
    </table>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"  /></p>
</form>
</div>
EOF;

echo str_replace(
    array('%title%', '%api_key%', '%calendar_id%', '%cache_lifetime%'),
    array(__( 'Calendar Settings', 'timeshout' ), $options['api_key'], $options['calendar_id'], $options['cache_lifetime']),
    $form
    );
}
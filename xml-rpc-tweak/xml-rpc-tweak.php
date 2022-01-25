<?php
/**
 * Plugin Name:   XML-RPC Tweak
 * Description:   Aids in the testing of XML-RPC responses to clients. This is a development and testing plugin and is not intended for production use.
 * Version:       1.0.0
 * Author:        Automattic / @twstokes
 * Author URI:    https://www.automattic.com
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires PHP:  5.2
 */

// Returns a 401 Unauthorized - a likely code from a server that's blocking XML-RPC requests.
function xml_rpc_tweak_return_bad_login_code()
{
    http_response_code(401);
    exit();
}

// Adds a "Settings" button to the activated plugin.
function xml_rpc_tweak_add_action_links($actions)
{
    $mylinks = array(
      '<a href="' . admin_url('options-general.php?page=xml_rpc_tweak') . '">Settings</a>',
   );
    $actions = array_merge($mylinks, $actions);
    return $actions;
}

// Plugin initializer.
function xml_rpc_error_plugin_init()
{
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'xml_rpc_tweak_add_action_links');
    add_action('admin_init', 'xml_rpc_tweak_settings_init');
    add_action('admin_menu', 'xml_rpc_tweak_options_page');

    $options = get_option('xml_rpc_tweak_mode');
    $mode = $options["xml_rpc_tweak_field_mode"];

    switch ($mode) {
        case 0:
            break;
        case 1:
            add_filter('xmlrpc_enabled', '__return_false');
            break;
        case 2:
            // note - in order for this option to work, the user must input a bad username + password!
            add_action('xmlrpc_login_error', '__return_true');
            break;
        case 3:
            // note - in order for this option to work, the user must input a bad username + password!
            add_action('xmlrpc_login_error', 'xml_rpc_tweak_return_bad_login_code');
            break;
        case 4:
            add_action('pre_option_enable_xmlrpc', 'xml_rpc_tweak_return_bad_login_code');
            break;
    }
}

// Settings initializer.
function xml_rpc_tweak_settings_init()
{
    register_setting('xml_rpc_tweak', 'xml_rpc_tweak_mode', array('type' => 'integer', 'default' => 0));
 
    add_settings_section(
        'xml_rpc_tweak_section_mode',
        __('Select Mode', 'xml_rpc_tweak'),
        'xml_rpc_tweak_mode_cb',
        'xml_rpc_tweak'
    );
 
    add_settings_field(
        'xml_rpc_tweak_field_mode',
        __('Mode', 'xml_rpc_tweak'),
        'xml_rpc_tweak_field_mode_cb',
        'xml_rpc_tweak',
        'xml_rpc_tweak_section_mode',
        array(
            'label_for'         => 'xml_rpc_tweak_field_mode',
            'class'             => 'xml_rpc_tweak_row',
        )
    );
}

// Mode description HTML.
function xml_rpc_tweak_mode_cb($args)
{
    ?>
    <table class="description" style="text-align: left; background-color: white; border: 1px solid gray; border-spacing: 5px;">
        <tr style="border: 1px solid gray;"><th>Off</th><td>Disables the plugin</td></tr>
        <tr><th>Mode 1: Block Auth Only</th><td>Disable XML-RPC requests that require authentication.</td></tr>
        <tr><th>Mode 2: Block Auth Only + Invalid Payload Response</th><td>Simulate a response with an invalid auth payload.<br><strong style="color: red;">You must use an invalid username / password to trigger!</strong></td></tr>
        <tr><th>Mode 3: Block Auth Only + No Payload Response</th><td>Simulate a response with no payload.<br><strong style="color: red;">You must use an invalid username / password to trigger!</strong></td></tr>
        <tr><th>Mode 4: Block All Calls</th><td>Simulate a server that has blocked all XML-RPC calls.</td></tr>
    </table>
    <?php
}

// Mode selector HTML.
function xml_rpc_tweak_field_mode_cb($args)
{
    $options = get_option('xml_rpc_tweak_mode'); ?>

    <select
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="xml_rpc_tweak_mode[<?php echo esc_attr($args['label_for']); ?>]">
        <option value="0" <?php echo isset($options[ $args['label_for'] ]) ? (selected($options[ $args['label_for'] ], '0', false)) : (''); ?>>
            <?php esc_html_e('Off', 'xml_rpc_tweak'); ?>
        </option>
        <option value="1" <?php echo isset($options[ $args['label_for'] ]) ? (selected($options[ $args['label_for'] ], '1', false)) : (''); ?>>
            <?php esc_html_e('Mode 1: Block Auth Only', 'xml_rpc_tweak'); ?>
        </option>
        <option value="2" <?php echo isset($options[ $args['label_for'] ]) ? (selected($options[ $args['label_for'] ], '2', false)) : (''); ?>>
            <?php esc_html_e('Mode 2: Block Auth Only + Invalid Payload Response', 'xml_rpc_tweak'); ?>
        </option>
        <option value="3" <?php echo isset($options[ $args['label_for'] ]) ? (selected($options[ $args['label_for'] ], '3', false)) : (''); ?>>
            <?php esc_html_e('Mode 3: Block Auth Only + No Payload Response', 'xml_rpc_tweak'); ?>
        </option>
        <option value="4" <?php echo isset($options[ $args['label_for'] ]) ? (selected($options[ $args['label_for'] ], '4', false)) : (''); ?>>
            <?php esc_html_e('Mode 4: Block All Calls', 'xml_rpc_tweak'); ?>
        </option>
    </select>
    <?php
}

// Adds the plugin menu page.
function xml_rpc_tweak_options_page()
{
    add_menu_page(
        'XML-RPC Tweak',
        'XML-RPC Tweak',
        'manage_options',
        'xml_rpc_tweak',
        'xml_rpc_tweak_options_page_html'
    );
}

// Options page HTML.
function xml_rpc_tweak_options_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
 
    if (isset($_GET['settings-updated'])) {
        add_settings_error('xml_rpc_tweak_messages', 'xml_rpc_tweak_message', __('Settings Saved', 'xml_rpc_tweak'), 'updated');
    }
 
    settings_errors('xml_rpc_tweak_messages'); ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php

            settings_fields('xml_rpc_tweak');

    do_settings_sections('xml_rpc_tweak');

    submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

xml_rpc_error_plugin_init();

<?php
/*
Plugin Name: Blessing Skin 数据对接
Description: 用于与 Blessing Skin Server 进行用户数据同步。
Plugin URI: https://github.com/bs-community/bs-integration-for-wordpress
Author: GPlane
Author URI: https://gplane.win/
License: MIT
Version: 0.1.0
*/

function bs_verify_password($raw, $hash) {
    $cipher_class = get_option('bs_pwd_method');
    require_once(__DIR__."/ciphers/$cipher_class.php");
    $cipher = new $cipher_class();
    return $cipher->verify($raw, $password, get_option('bs_pwd_salt'));
}

function bs_hash_password($password) {
    $cipher_class = get_option('bs_pwd_method');
    require_once(__DIR__."/ciphers/$cipher_class.php");
    $cipher = new $cipher_class();
    return $cipher->hash($password, get_option('bs_pwd_salt'));
}

add_option('bs_db_host');
add_option('bs_db_username');
add_option('bs_db_password');
add_option('bs_db_database');
add_option('bs_db_prefix');
add_option('bs_pwd_method');
add_option('bs_pwd_salt');
add_option('bs_new_user_score', 1000);

function bs_check_auth($user, $email, $password) {
    $email = sanitize_email($email);
    $password = sanitize_text_field($password);
    if (! is_email($email)) {
        return $user;
    }

    $u = get_user_by('email', $email);
    if ($u) {
        $status = get_user_meta($u->ID, 'bs_sync_status', true);
        if (! $status || $status == 'ok') {
            return $user;
        } elseif ($status == 'wip') {
            $db = new mysqli(
                sanitize_title(get_option('bs_db_host')),
                sanitize_title(get_option('bs_db_username')),
                sanitize_title(get_option('bs_db_password')),
                sanitize_title(get_option('bs_db_database'))
            );
            $stmt = $db->prepare(
                'SELECT `email`, `password` FROM `'.sanitize_title(get_option('bs_db_prefix')).'users` WHERE `email`=? LIMIT 1'
            );
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($bs_email, $bs_password);
            if ($stmt->fetch()) {
                if (bs_verify_password($password, $bs_password)) {
                    wp_update_user([
                        'ID' => $u->ID,
                        'user_pass' => $password,
                    ]);
                    update_user_meta($id, 'bs_sync_status', 'ok');
                    $user = get_user_by('ID', $u->ID);
                } else {
                    $user = new WP_Error(
                        'incorrect_password',
                        sprintf(
                            /* translators: %s: email address */
                            __('<strong>ERROR</strong>: The password you entered for the email address %s is incorrect.'),
                            '<strong>'.esc_html($email).'</strong>'
                        ).
                        ' <a href="'.wp_lostpassword_url().'">'.
                        __('Lost your password?').
                        '</a>'
                    );
                }
            }
            $stmt->close();
            $db->close();

            return $user;
        }
    }

    $db = new mysqli(
        sanitize_title(get_option('bs_db_host')),
        sanitize_title(get_option('bs_db_username')),
        sanitize_title(get_option('bs_db_password')),
        sanitize_title(get_option('bs_db_database'))
    );
    $stmt = $db->prepare('SELECT `email`, `nickname`, `password` FROM `'.sanitize_title(get_option('bs_db_prefix')).'users` WHERE `email`=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($bs_email, $bs_nickname, $bs_password);
    if ($stmt->fetch()) {
        $id = wp_create_user($bs_nickname, 'wip_'.time(), $email);
        if (is_int($id)) {
            $u = get_user_by('ID', $id);
            if (bs_verify_password($password, $bs_password)) {
                update_user_meta($id, 'bs_sync_status', 'ok');
                wp_update_user([
                    'ID' => $u->ID,
                    'user_pass' => $password,
                ]);
                $user = $u;
            } else {
                update_user_meta($id, 'bs_sync_status', 'wip');
                $user = new WP_Error(
                    'incorrect_password',
                    sprintf(
                        /* translators: %s: email address */
                        __('<strong>ERROR</strong>: The password you entered for the email address %s is incorrect.'),
                        '<strong>'.esc_html($email).'</strong>'
                    ).
                    ' <a href="'.wp_lostpassword_url().'">'.
                    __('Lost your password?').
                    '</a>'
                );
            }
        }
    }
    $stmt->close();
    $db->close();

    return $user;
}
add_filter('authenticate', 'bs_check_auth', 10, 3);

function sync_to_bs($username, $user) {
    $status = get_user_meta($user->ID, 'bs_sync_status', true);
    if ($status) {
        return;
    }

    $db = new mysqli(
        sanitize_title(get_option('bs_db_host')),
        sanitize_title(get_option('bs_db_username')),
        sanitize_title(get_option('bs_db_password')),
        sanitize_title(get_option('bs_db_database'))
    );
    $stmt = $db->prepare('INSERT INTO `'.sanitize_title(get_option('bs_db_prefix')).'users` (`email`,`nickname`,`score`,`password`,`ip`,`last_sign_at`,`register_at`) VALUES (?, ?, ?, ?, ?, ?, ?);');
    $stmt->bind_param('ssissss', $email, $nickname, $score, $password, $ip, $last_sign_at, $register_at);
    $email = $user->user_email;
    $nickname = $user->user_login;
    $score = (int) get_option('bs_new_user_score');
    $password = bs_hash_password($_POST['pwd']);  // No need to sanitize password here because it will be hashed.
    $ip = '255.255.255.255';
    $last_sign_at = date('Y-m-d H:i:s', time() - 24 * 60 * 60);
    $register_at = date('Y-m-d H:i:s');
    $stmt->execute();
    $stmt->close();
    $db->close();

    update_user_meta($user->ID, 'bs_sync_status', 'ok');
}
add_action('wp_login', 'sync_to_bs', 10, 2);

function bs_sync_password($user_id) {
    $password = $_POST['pass1'];  // No need to sanitize password here because it will be hashed.
    if (strlen($password) === 0) {
        return;
    }

    $email = get_user_by('ID', $user_id)->user_email;
    $hash = bs_hash_password($password);
    $db = new mysqli(
        sanitize_title(get_option('bs_db_host')),
        sanitize_title(get_option('bs_db_username')),
        sanitize_title(get_option('bs_db_password')),
        sanitize_title(get_option('bs_db_database'))
    );
    $stmt = $db->prepare('UPDATE `'.sanitize_title(get_option('bs_db_prefix')).'users` SET `password`=? WHERE `email`=?');
    $stmt->bind_param('ss', $hash, $email);
    $stmt->execute();
    $stmt->close();
    $db->close();
}
add_action('personal_options_update', 'bs_sync_password');

function bs_sync_reset_password($user, $password) {
    $email = $user->user_email;
    $hash = bs_hash_password($password);  // No need to sanitize password here because it will be hashed.
    $db = new mysqli(
        sanitize_title(get_option('bs_db_host')),
        sanitize_title(get_option('bs_db_username')),
        sanitize_title(get_option('bs_db_password')),
        sanitize_title(get_option('bs_db_database'))
    );
    $stmt = $db->prepare('UPDATE `'.sanitize_title(get_option('bs_db_prefix')).'users` SET `password`=? WHERE `email`=?');
    $stmt->bind_param('ss', $hash, $email);
    $stmt->execute();
    $stmt->close();
    $db->close();
}
add_action('password_reset', 'bs_sync_reset_password', 10, 2);

function bs_check_user_exists($user_id) {
    if ($user_id || ! (isset($_POST['user_email']) && is_string($_POST['user_email']))) {
        return $user_id;
    }

    $email = sanitize_email($_POST['user_email']);
    $db = new mysqli(
        sanitize_title(get_option('bs_db_host')),
        sanitize_title(get_option('bs_db_username')),
        sanitize_title(get_option('bs_db_password')),
        sanitize_title(get_option('bs_db_database'))
    );
    $stmt = $db->prepare('SELECT `email` FROM `'.sanitize_title(get_option('bs_db_prefix')).'users` WHERE `email`=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user_id = $stmt->fetch() ? -1 : false;
    $stmt->close();
    $db->close();
    return $user_id;
}
add_filter('username_exists', 'bs_check_user_exists');

if (is_admin()) {
    function bs_plugin_menu() {
        require_once(__DIR__ . '/options.php');
        add_options_page('Blessing Skin 数据对接配置', 'BS 对接配置', 'manage_options', 'bs-integration', 'bs_options_page');
    }
    add_action('admin_menu', 'bs_plugin_menu');

    function bs_settings_link($links) {
        $link = '<a href="options-general.php?page=bs-integration">'.__('Settings').'</a>';
        array_unshift($links, $link);
        return $links;
    }
    add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'bs_settings_link');

    function bs_register_settings() {
        register_setting('bs-options-group', 'bs_db_host');
        register_setting('bs-options-group', 'bs_db_username');
        register_setting('bs-options-group', 'bs_db_password');
        register_setting('bs-options-group', 'bs_db_database');
        register_setting('bs-options-group', 'bs_db_prefix');
        register_setting('bs-options-group', 'bs_pwd_method');
        register_setting('bs-options-group', 'bs_pwd_salt');
        register_setting('bs-options-group', 'bs_new_user_score');
    }
    add_action('admin_init', 'bs_register_settings');
}

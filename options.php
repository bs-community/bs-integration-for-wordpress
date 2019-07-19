<?php

function bs_db_connection_check() {
    $db = @new mysqli(get_option('bs_db_host'), get_option('bs_db_username'), get_option('bs_db_password'), get_option('bs_db_database'));
    if ($db->connect_error) {
        $class = 'notice-error';
        $message = '数据库连接失败。详细信息：'.$db->connect_error;
    } else {
        $table = get_option('bs_db_prefix').'users';
        $stmt = $db->prepare("SHOW TABLES LIKE '".$table."'");
        if (! $stmt) {
            $class = 'notice-error';
            $message = '数据库连接失败，请确认数据库名是否正确。';
        } else {
            $stmt->execute();
            if ($stmt->fetch()) {
                $class = 'notice-success';
                $message = '数据库连接成功。';
            } else {
                $class = 'notice-error';
                $message = '数据库连接失败，请确认表前缀是否正确。';
            }
            $stmt->close();
        }
    }
    @$db->close();

	printf('<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

function bs_options_page() {
?>
<div class="wrap"><h1>Blessing Skin 数据对接配置</h1>
<p>觉得插件很有帮助？不妨前往 <a rel="noreferrer noopener" target="_blank" href="https://afdian.net/@blessing-skin">爱发电</a> 赞助以支持我们的开发。</p>
<?php bs_db_connection_check() ?>
<form method="post" action="options.php">
<?php settings_fields('bs-options-group'); ?>
<?php do_settings_sections('bs-options-group'); ?>
<table class="form-table">
    <tr valign="top">
    <th scope="row">BS 数据库主机</th>
    <td><input type="text" name="bs_db_host" value="<?php echo esc_attr(get_option('bs_db_host')); ?>" /></td>
    </tr>

    <tr valign="top">
    <th scope="row">BS 数据库用户名</th>
    <td><input type="text" name="bs_db_username" value="<?php echo esc_attr(get_option('bs_db_username')); ?>" /></td>
    </tr>

    <tr valign="top">
    <th scope="row">BS 数据库密码</th>
    <td><input type="text" name="bs_db_password" value="<?php echo esc_attr(get_option('bs_db_password')); ?>" /></td>
    </tr>

    <tr valign="top">
    <th scope="row">BS 数据库名</th>
    <td><input type="text" name="bs_db_database" value="<?php echo esc_attr(get_option('bs_db_database')); ?>" /></td>
    </tr>

    <tr valign="top">
    <th scope="row">BS 数据表前缀</th>
    <td>
        <input type="text" name="bs_db_prefix" value="<?php echo esc_attr(get_option('bs_db_prefix')); ?>" /><br>
        如果没有设置表前缀，留空即可。
    </td>
    </tr>

    <tr valign="top">
    <th scope="row">密码哈希算法</th>
    <td>
        <select name="bs_pwd_method">
            <option value="BCRYPT" <?php echo esc_attr(get_option('bs_pwd_method')) == 'BCRYPT' ? 'selected="selected"' : '' ?>>BCRYPT</option>
            <option value="PHP_PASSWORD_HASH" <?php echo esc_attr(get_option('bs_pwd_method')) == 'PHP_PASSWORD_HASH' ? 'selected="selected"' : '' ?>>PHP_PASSWORD_HASH</option>
            <option value="MD5" <?php echo esc_attr(get_option('bs_pwd_method')) == 'MD5' ? 'selected="selected"' : '' ?>>MD5</option>
            <option value="SALTED2MD5" <?php echo esc_attr(get_option('bs_pwd_method')) == 'SALTED2MD5' ? 'selected="selected"' : '' ?>>SALTED2MD5</option>
            <option value="SHA256" <?php echo esc_attr(get_option('bs_pwd_method')) == 'SHA256' ? 'selected="selected"' : '' ?>>SHA256</option>
            <option value="SALTED2SHA256" <?php echo esc_attr(get_option('bs_pwd_method')) == 'SALTED2SHA256' ? 'selected="selected"' : '' ?>>SALTED2SHA256</option>
            <option value="SHA512" <?php echo esc_attr(get_option('bs_pwd_method')) == 'SHA512' ? 'selected="selected"' : '' ?>>SHA512</option>
            <option value="SALTED2SHA512" <?php echo esc_attr(get_option('bs_pwd_method')) == 'SALTED2SHA512' ? 'selected="selected"' : '' ?>>SALTED2SHA512</option>
        </select>
        <br>
        请务必与 BS 那边的设置保持一致。
    </td>
    </tr>

    <tr valign="top">
    <th scope="row">盐</th>
    <td>
        <input type="text" name="bs_pwd_salt" value="<?php echo esc_attr(get_option('bs_pwd_salt')); ?>" /><br>
        如果使用的密码哈希算法不需要盐，那么这项可以忽略。请务必与 BS 那边的设置保持一致。
    </td>
    </tr>

    <tr valign="top">
    <th scope="row">新用户积分</th>
    <td><input type="text" name="bs_new_user_score" value="<?php echo esc_attr(get_option('bs_new_user_score')); ?>" /><br></td>
    </tr>
</table>
<?php submit_button(); ?>
</form></div>
<?php } ?>

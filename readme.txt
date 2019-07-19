=== Plugin Name ===
Contributors: GPlane
Donate link: https://afdian.net/@blessing-skin
Tags: integration
Requires at least: 5.0
Tested up to: 5.2
Stable tag: 0.1
Requires PHP: 7.0.0
License: MIT
License URI: https://spdx.org/licenses/X11.html

Blessing Skin Server integration for WordPress.

== Description ==

此插件可以使 WordPress 与 Blessing Skin 进行用户数据对接，使用户用同一邮箱和密码登录两个网站。

当用户在 WordPress 中登录时，如果该用户已在 WordPress 中注册，会同时向 Blessing Skin 的数据库创建该用户的信息；
如果未注册，则会向 Blessing Skin 中获取用户数据。

另外，如果用户在 WordPress 中更改密码（例如在个人资料页中更改密码，或重置密码），此插件也会向 Blessing Skin 的数据库中更改该用户的密码。

== Installation ==

1. 将所有文件上传到 `/wp-content/plugins/bs-integration` 目录，或直接在 WordPress 安装
2. 在 WordPress 激活此插件
3. 进入配置页面，配置数据库等信息

== Changelog ==

= 0.1 =
First release.

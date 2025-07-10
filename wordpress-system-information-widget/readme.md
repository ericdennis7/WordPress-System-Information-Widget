# 📊 System Information Dashboard Widget

A customizable WordPress dashboard widget that displays detailed system information — tailored per user.

## ✨ Features

- WordPress version, theme, plugin count, and post count
- Server configuration: PHP version, memory limits, software, etc.
- Server IP details including geolocation and ISP (via `ipinfo.io`)
- User agent, login, and security/debug info
- File system paths (ABSPATH, content, plugin, theme dirs)
- User-specific section toggles — saved via AJAX and remembered per user
- Clean expand/collapse box to manage visible sections

## 🧑‍💻 Installation

### Option 1:

1. WIP

### Option 2:
1. Download the ZIP or clone this repo.
2. Upload to `/wp-content/plugins/` or use the **"Upload Plugin"** option in your WordPress Admin (`Plugins → Add New`).
3. Activate the plugin.
4. Visit your **WordPress Dashboard** to see the widget in action.

## 🛠️ Customization

Users can toggle what sections are visible using the **"🛠️ Customize Sections"** expander. Preferences are stored in `user_meta` per user and persist across sessions.

## 🔒 Data Storage

User preferences are stored in `user_meta` under the key:  
`sysinfo_widget_prefs`

These are automatically updated via AJAX whenever a user toggles a section.

> ⚠️ Note: No personal data is transmitted or stored externally. The only external API used is:
- `https://ipinfo.io` (IP geolocation)
- `https://flagsapi.com` (country flag display)

## 🚫 Uninstallation Behavior

This plugin does **not automatically delete user data** upon deactivation or deletion. If you’d like to purge user preferences manually, you can run:

```php
delete_user_meta($user_id, 'sysinfo_widget_prefs');

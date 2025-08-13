✨ Features

- Office 365 SMTP Support – Pre-configured defaults for smtp.office365.com.
- Password Encryption – Uses PHP's Sodium library for secure storage.
- Customizable Settings – Host, port, encryption, username, and “From” details.
- Test Email Tool – Send a test email directly from the admin panel.
- Security – Nonces, sanitization, and capability checks included.
- Clean Uninstall – Removes all plugin settings on uninstall.

📥 **Installation**

Download or clone this repository into your WordPress plugins directory:

wp-content/plugins/simple-office365-smtp

**Activate Simple Office 365 SMTP from the Plugins menu in WordPress.**

Go to Settings → Simple SMTP to configure.

⚙ Configuration

**On the settings page, you can set:**

Setting	Description
- SMTP Host	Default: smtp.office365.com
- SMTP Port	Usually 587 (TLS) or 465 (SSL)
- Encryption	TLS or SSL
- Username	Your Office 365 email address
- Password	Stored securely (leave blank to keep existing)
- From Email	Email address shown in the “From” field
- From Name	Name shown in the “From” field

🧪 Sending a Test Email

**At the bottom of the settings page:**

Enter an email address

Click Send Test Email

A success or error message will appear, with debugging output if it fails.

(Rate-limited to once every 30 seconds.)

🔐 **Security Notes**
- Passwords are encrypted using sodium_crypto_secretbox (if available).
- Input values are sanitized before saving.

Access to settings is restricted to users with the manage_options capability.

🗑 Uninstall

When the plugin is deleted from the Plugins menu, all stored settings are automatically removed.

📄 License

This project is licensed under the MIT License – see the LICENSE file for details.

👨‍💻 Author

Developed by ᴎξXṵ§
- Feel free to contribute via pull requests or open issues for bug reports and feature requests.

## +----------------------------------------------------------------------+
## | OpenConf                                                             |
## +----------------------------------------------------------------------+
## | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
## +----------------------------------------------------------------------+
## | This source file is subject to the OpenConf License, available on    |
## | the OpenConf web site: www.OpenConf.com                              |
## +----------------------------------------------------------------------+


## NOTE: This file cannot contain a semi-colon (;) except at the end of a
## SQL statement.

# --------------------------------------------------------

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_mailer', 'php', 'Mailer', 'Mailer to use for sending messages, options: php (built-in PHP mail function), smtp (SMTP server)', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_from_email', '', 'From Email', 'Email address to use in From field. Leave blank for Chair Email Address', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_from_name', '', 'From Name', 'Name to use in From field. Leave blank for Event Short Name', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_return_path', 1, 'Return Path', 'Set return path to match From Email (1=Yes, 0=no)', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_smtp_host', '', 'SMTP Host', 'SMTP server name', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_smtp_port', '', 'SMTP Port', 'SMTP server port', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_smtp_encryption', 'none', 'SMTP Encryption', 'SMTP server encryption, options: none, ssl, tls', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_smtp_username', '', 'SMTP Username', 'SMTP server account username', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('mail', 'MOD_MAIL_smtp_password', '', 'SMTP Password', 'SMTP server account password', 0);

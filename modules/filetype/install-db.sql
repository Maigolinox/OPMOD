## +----------------------------------------------------------------------+
## | OpenConf                                                             |
## +----------------------------------------------------------------------+
## | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
## +----------------------------------------------------------------------+
## | This source file is subject to the OpenConf License, available on    |
## | the OpenConf web site: www.OpenConf.com                              |
## +----------------------------------------------------------------------+


## NOTE: UPDATES TO THIS FILE NEED TO BE REFLECTED IN lib/DB.sql



## NOTE: This file cannot contain a semi-colon (;) except at the end of a
## SQL statement.

# --------------------------------------------------------

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('filetype', 'MOD_FILETYPE_chairoverride', '1', 'Skip Check if Chair', 'Skips file format check if Chair uploading', 0);

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('filetype', 'MOD_FILETYPE_allow_rtfforword', '1', 'Accept RTF for Word Doc', 'Permit RTF MS Word docs', 0);

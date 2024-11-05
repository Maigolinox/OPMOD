## +----------------------------------------------------------------------+
## | OpenConf                                                             |
## +----------------------------------------------------------------------+
## | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
## +----------------------------------------------------------------------+
## | This source file is subject to the OpenConf License, available on    |
## | the OpenConf web site: www.OpenConf.com                              |
## +----------------------------------------------------------------------+

# NOTE: It is important that a ; only appear at the end of a SQL statement

# --------------------------------------------------------

#
# Table structure for table `acceptance`
#

CREATE TABLE `acceptance` (
  `value` varchar(50) NOT NULL default '',
  `color` varchar(6) default 'ffffff',
  `publish` tinyint unsigned NOT NULL default '0',
  `title` varchar(255) default NULL,
  `accepted` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`value`),
  KEY `publish` (`publish`),
  KEY `accepted` (`accepted`)
) ;

# --------------------------------------------------------

#
# Table structure for table `author`
#

CREATE TABLE `author` (
  `paperid` int unsigned NOT NULL default '0',
  `position` tinyint unsigned NOT NULL default '0',
  `name_last` varchar(255) NOT NULL default '',
  `name_first` varchar(255) default NULL,
  `position_title` varchar(255) default NULL,
  `department` varchar(255) default NULL,
  `organization` varchar(150) default NULL,
  `country` varchar(255) default NULL,
  `email` varchar(150) default NULL,
  `honorific` varchar(255) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `spc` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `presenter` varchar(255) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `role` text DEFAULT NULL,
  `orcid` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`paperid`,`position`),
  KEY `organization` (`organization`),
  KEY `email` (`email`)
) ;

# --------------------------------------------------------

#
# Table structure for table `config`
#

CREATE TABLE `config` (
  `module` varchar(100) NOT NULL default '',
  `setting` varchar(100) NOT NULL default '',
  `value` longtext,
  `name` varchar(100) default NULL,
  `description` text,
  `parse` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`setting`),
  KEY `module` (`module`)
) ;

# --------------------------------------------------------

#
# Table structure for table `conflict`
#

CREATE TABLE `conflict` (
  `paperid` int unsigned NOT NULL default '0',
  `reviewerid` int unsigned NOT NULL default '0',
  PRIMARY KEY  (`paperid`,`reviewerid`)
) ;

# --------------------------------------------------------

# 
# Table structure for table `email_queue`
# 

CREATE TABLE `email_queue` (
  `id` int unsigned NOT NULL auto_increment,
  `queued` datetime NOT NULL,
  `sent` datetime default NULL,
  `tries` tinyint NOT NULL default '0',
  `to` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `queued` (`queued`),
  KEY `sent` (`sent`)
) AUTO_INCREMENT=1 ;

# --------------------------------------------------------

# 
# Table structure for table `log`
# 

CREATE TABLE `log` (
  `logid` int unsigned NOT NULL auto_increment,
  `datetime` datetime NOT NULL,
  `entry` text NOT NULL,
  `type` varchar(50) NOT NULL default 'sql',
  `extra` text,
  PRIMARY KEY  (`logid`),
  KEY `type` (`type`)
) AUTO_INCREMENT=1 ;

# --------------------------------------------------------

#
# Table structure for table `modules`
#

CREATE TABLE `modules` (
  `moduleId` varchar(100) NOT NULL default '',
  `moduleActive` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`moduleId`),
  KEY `enabled` (`moduleActive`)
) ;

# --------------------------------------------------------

#
# Table structure for table `paper`
#

CREATE TABLE `paper` (
  `paperid` int unsigned NOT NULL,
  `accepted` varchar(50) default NULL,
  `password` varchar(255) default NULL,
  `title` varchar(1000) NOT NULL default '',
  `student` varchar(1) default NULL,
  `type` varchar(255) default NULL,
  `contactid` tinyint unsigned NOT NULL default '0',
  `altcontact` varchar(255) default NULL,
  `submissiondate` date NOT NULL,
  `lastupdate` date default NULL,
  `decision_date` date default NULL,
  `consent` text,
  `format` varchar(10) default NULL,
  `keywords` text,
  `comments` text,
  `abstract` text,
  `pcnotes` text,
  `edittoken` varchar(100) default NULL,
  `edittime` int unsigned default NULL,
  PRIMARY KEY  (`paperid`),
  KEY `accepted` (`accepted`)
);

# --------------------------------------------------------

#
# Table structure for table `paperadvocate`
#

CREATE TABLE `paperadvocate` (
  `paperid` int unsigned NOT NULL default '0',
  `advocateid` int unsigned NOT NULL default '0',
  `adv_recommendation` varchar(30) default NULL,
  `adv_comments` text,
  PRIMARY KEY  (`paperid`)
) ;

# --------------------------------------------------------

#
# Table structure for table `paperreviewer`
#

CREATE TABLE `paperreviewer` (
  `paperid` int unsigned NOT NULL default '0',
  `reviewerid` int unsigned NOT NULL default '0',
  `completed` enum('T','F') NOT NULL default 'F',
  `assigned` date default NULL,
  `updated` date default NULL,
  `score` smallint unsigned default NULL,
  `recommendation` tinyint unsigned default NULL,
  `category` tinyint unsigned default NULL,
  `value` varchar(20) default NULL,
  `familiar` enum('High','Low','Moderate') default NULL,
  `bpcandidate` enum('Yes','No','Unsure') default NULL,
  `length` enum('Yes','No','Unsure') default NULL,
  `difference` tinyint unsigned default NULL,
  `pccomments` text,
  `authorcomments` text,
  PRIMARY KEY  (`paperid`,`reviewerid`),
  KEY `completed` (`completed`)
) ;

# --------------------------------------------------------

#
# Table structure for table `papersession`
#

CREATE TABLE `papersession` (
  `paperid` int unsigned NOT NULL default '0',
  `reviewerid` int unsigned NOT NULL default '0',
  `topicid` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`paperid`,`reviewerid`,`topicid`)
) ;

# --------------------------------------------------------

#
# Table structure for table `papertopic`
#

CREATE TABLE `papertopic` (
  `paperid` int unsigned NOT NULL default '0',
  `topicid` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`paperid`,`topicid`)
) ;

# --------------------------------------------------------

#
# Table structure for table `reviewer`
#

CREATE TABLE `reviewer` (
  `reviewerid` int unsigned NOT NULL auto_increment,
  `username` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `name_last` varchar(40) NOT NULL default '',
  `name_first` varchar(60) default NULL,
  `email` varchar(150) NOT NULL default '',
  `orcid` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `organization` varchar(150) default NULL,
  `country` varchar(2) default NULL,
  `telephone` varchar(30) default NULL,
  `onprogramcommittee` enum('T','F') NOT NULL default 'F',
  `comments` text,
  `signupdate` date DEFAULT NULL,
  `lastupdate` date DEFAULT NULL,
  `lastsignin` date DEFAULT NULL,
  `consent` text,
  PRIMARY KEY  (`reviewerid`),
  KEY `onprogramcommittee` (`onprogramcommittee`),
  KEY `email` (`email`),
  KEY `organization` (`organization`)
) AUTO_INCREMENT=1 ;

# --------------------------------------------------------

#
# Table structure for table `reviewertopic`
#

CREATE TABLE `reviewertopic` (
  `reviewerid` int unsigned NOT NULL default '0',
  `topicid` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`reviewerid`,`topicid`)
) ;

# --------------------------------------------------------

#
# Table structure for table `status`
#

CREATE TABLE `status` (
  `module` varchar(100) NOT NULL default '',
  `setting` varchar(100) NOT NULL default '',
  `description` text,
  `status` tinyint unsigned NOT NULL default '0',
  `name` varchar(100) default NULL,
  `open` datetime default NULL,
  `close` datetime default NULL,
  `dependency` varchar(100) default NULL,
  `order` tinyint unsigned default '0',
  PRIMARY KEY  (`setting`),
  KEY `module` (`module`)
) ;

# --------------------------------------------------------

#
# Table structure for table `template`
#

CREATE TABLE `template` (
  `templateid` varchar(100) NOT NULL default '',
  `type` varchar(30) NOT NULL DEFAULT 'email',
  `module` varchar(100) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `subject` varchar(255) default NULL,
  `body` text,
  `updated` date default NULL,
  `variables` text,
  PRIMARY KEY  (`templateid`),
  KEY `module` (`module`)
) ;

# --------------------------------------------------------

#
# Table structure for table `topic`
#

CREATE TABLE `topic` (
  `topicid` tinyint unsigned NOT NULL default '0',
  `topicname` varchar(255) NOT NULL default '',
  `short` varchar(50) default NULL,
  PRIMARY KEY  (`topicid`)
) ;

# --------------------------------------------------------

#
# Table structure for table `withdrawn`
#

CREATE TABLE `withdrawn` (
  `paperid` int unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `contact_author` varchar(255) NOT NULL default '',
  `contact_email` varchar(150) default NULL,
  `papersql` longtext NOT NULL,
  `authorsql` text NOT NULL,
  `topicsql` text,
  `withdraw_date` datetime default NULL,
  `withdrawn_by` enum('Author','Chair') default NULL,
  PRIMARY KEY  (`paperid`)
) ;

# --------------------------------------------------------

#
# Data for table `acceptance`
#

INSERT INTO `acceptance` (`value`, `color`, `publish`, `title`, `accepted`) VALUES('Accept', 'aaffaa', 1, 'Papers', 1);
INSERT INTO `acceptance` (`value`, `color`, `publish`, `title`, `accepted`) VALUES('Reject', 'ffcccc', 0, '', 0);

# --------------------------------------------------------

#
# Data for table `config`
#

INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_advocateReadPapers', '1', 'Allow Adv. to View All Submissions', 'Allow advocates to read all submitted submissions', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_advocateSeeAuthors', '0', 'Allow Adv. See Authors?', 'Allow advocates to see submission authors', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_advocateSeeDecision', '0', 'Allow Adv. See Decision', 'Allow advocates to see acceptance decision', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_advocateSeeOtherReviews', '0', 'Allow Adv. to See All Reviews', 'Allow advocates to see reviews of non-assigned submissions', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_allowConflictOverride', '0', 'Allow Conflict Override', 'Allow Chair to override conflict settings when making manual assignments.  This will also determine whether advocate/reviewers see listings in conflict.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_allowEmailConflict', '0', 'Allow Email Conflict', 'Allow a submission author and reviewer to have the same email address', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_allowOrgConflict', '0', 'Allow Organization Conflict', 'Allow a submission author and reviewer to have the same organization name', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorOneContact', '0', 'Set Author 1 as Contact', 'Auto set Author 1 as contact author and hide Contact ID field (1=Yes, 0=No [default])', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorsEmailUnique', '0', 'Authors Email Unique', 'Require author email addresses to be unique (1=Yes, 0=No [default])', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorsMinDisplay', '3', 'Min. Authors', 'Minimum number of authors to display on submission form', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorsMax', '20', 'Max. Authors', 'Maximum number of authors allowed per submission', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorsRequiredData', '0', 'Authors Required Data', 'Author(s) for whom required sub. form fields must be filled in (0=All, 1=First, 2=Contact)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorSeePendingSubReviews', '0', 'Display Reviews to Author if Sub. Decision Pending', 'Allow authors to see reviews when checking status if submission decision is pending', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorViewSubIfEditClosed', '0', 'View Sub. if Edit Closed', 'Allow author to view submission if editing is closed (1=Yes, 0=No [default])', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chair_pwd', 'c23a11a3dbbbb527ae1e54981868619361425340ad6b32e222', 'Chair Password', 'Chair Password', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chair_uname', 'chair', 'Chair Username', 'Chair Login name', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairChangePassword', '1', 'Allow Chair Change Pwd', 'Allow Chair to change password?', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairFailedSignIn', '', 'Chair Failed Sign Ins', 'for internal use', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairMFA', '', 'Chair Multi-Factor Authentication', 'Enter email address to receive authentication code or leave blank to disable', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairMFAcode', '', 'Chair MFA Code', 'for internal use', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairPasswordForgot', '1', 'Display Chair Pwd Forgot', 'Display Chair Sign In Password "i forgot it"?', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_ChairTimeout', '60', 'Chair Timeout', 'Chair session timeout in minutes or enter 0 for no timeout.  Note: subject to server PHP settings.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairUsernameForgot', '1', 'Enable Chair Get Username', 'Display Chair Sign In Username "i forgot it"?', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_confirmmail', 'openconf@example.com', 'Confirm Email', 'Receives a copy of confirmation emails sent to authors and reviewers (e.g., submissions, edits, signups) - see Email Notification below.  A comma-delimited list of address (without spaces) is permitted.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_committeeFooter', '', 'Committee Footer', 'Notice to appear at the bottom of the main committee page', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_confName', 'OCC2024', 'Short Name/Title', 'Event/Journal abbreviated name, primarily used in email from and subject lines. Quotes not permitted.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_confNameFull', 'OpenConf Conference 2024', 'Full Name/Title', 'Full name of event/journal for use on Web pages and in email messages', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_confURL', 'https://www.openconf.com', 'Website Address', 'Complete event/journal Website address', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_dataDir', 'data/', 'Data Directory', 'Directory for data files offset from openconf/ or full path, include trailing slash (/)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_editAcceptedOnly', '0', 'Edit Accepted Only', 'Restrict Edit Submission to accepted submissions only (1=Accepted only, 0=All)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_emailAuthorOnUpload', 0, 'Email Author on Upload', 'Emails the author when a file is uploaded', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_emailAuthorRecipients', 0, 'Author Email Recipients', 'Author(s) to receive notices and Chair emails (0: contact only, 1: all)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_emailWrap', '70', 'Email Wrap', 'Number of characters at which to wrap email lines - only used when displaying a preview message via chair/email.php', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_extar', 'pdf', 'File Formats', 'File formats to accept (select at least one).  Use FileType module to verify file is in the proper format', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_fileLimit', '', 'File Limit', 'File upload size limit in MB', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_footerFile', 'footer.php', 'Footer File', 'File containing the footer section of OpenConf pages', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_friendlyURLs', '0', 'Friendly URLs', 'Friendly URLs are more easily indexed by search engines.  Only limited parts of OpenConf make use of this option, and it requires RewriteEngine. Use this feature carefully (still under development).', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_googleAnalytics', '', 'Google Analytics', 'Google Analytics code snippet for tracking access.  Must begin with script tag', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_headerFile', 'header.php', 'Header File', 'File containing the header section of OpenConf pages', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_hideCmtFields', 'fs_personal:orcid', 'Hide Cmt. Profile Form Fields', 'Comma-delimited list of committee profile form fields to not display (e.g., fieldset:field,fieldset:field,fieldset:field).  Overridden by Custom Forms module if used.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_hideRevFields', '', 'Hide Rev. Form Fields', 'Comma-delimited list of review form fields to not display (e.g., field1,field2,field3).  Overridden by Custom Forms module if used.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_hideSubFields', 'fs_general:type,fs_general:student,fs_authors:honorific,fs_authors:suffix,fs_authors:position_title,fs_authors:department,fs_authors:address,fs_authors:address2,fs_authors:city,fs_authors:spc,fs_authors:postcode,fs_authors:phone,fs_authors:url,fs_authors:facebook,fs_authors:twitter,fs_authors:linkedin,fs_authors:presenter,fs_authors:biography,fs_authors:orcid,fs_authors:photo,fs_authors:role,fs_content:file', 'Hide Sub. Form Fields', 'Comma-delimited list of submission form fields to not display (e.g., fieldset:field,fieldset:field,fieldset:field).  Overridden by Custom Forms module if used.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_headerImage', '', 'Header Image', 'Complete web address for an image to be displayed in place of conference name atop pages', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_homePageNotice', '', 'Home Page Notice', 'Notice appearing atop OpenConf home page', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_includeReferenceSearchLinks', '0', 'Include Reference Service Links', 'Include reference service links on committee abstract page (1=Yes, 0=No)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_keycode_program', CONCAT('pckey-',LEFT(MD5(CONCAT(UUID(),RAND())), 6)), 'Program Cmt. Keycode', 'Keycode for signing up as a program committee member.  May enter a comma-delimited list (no spaces).', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_keycode_reviewer', CONCAT('revkey-',LEFT(MD5(CONCAT(UUID(),RAND())), 6)), 'Reviewer Keycode', 'Keycode for signing up as a reviewer committee member.  May enter a comma-delimited list (no spaces).', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_localeDefault', 'en', 'Default Locale', 'Default locale for initial display', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_locales', 'en', 'Available Locales', 'Locales available for user to choose between', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_logSQL', '0', 'Log DB Update SQL', 'Log the SQL statements used to update database', 0);
INSERT INTO `config` VALUES ('OC', 'OC_mailCopyLast', '0', 'Forward Last Bulk Email', 'Forwards to the Confirm Email address a copy of the last bulk email sent', 0);
INSERT INTO `config` VALUES ('OC', 'OC_mailHeaders', 'From: "$OC_confName" <$OC_pcemail>\r\nReply-To: $OC_pcemail\r\nX-OC: PHP/OpenConf', 'Mail Headers', 'Headers sent to PHP mail() function. Do not include trailing \\r\\n', 1);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_mailUTF8', '1', 'Encode Mail for UTF-8', 'Sends out encoded messages supporting UTF-8', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_minReviewersPerPaper', '3', 'Reviewers per Submission', 'Default minimum number used when auto assigning reviewers to submissions', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_multipleCommitteeTopics', '1', 'Multiple Cmt. Topics', 'Allow multiple topics to be selected by committee members (or just one)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_multipleSubmissionTopics', '1', 'Multiple Sub. Topics', 'Allow multiple submission topics to be selected (or just one).  Overridden by Custom Forms module.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyAuthorEdit', '1', 'Notify of Sub. Edit', 'Notify when a submission is edited (updated)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyAuthorEmailPapers', '0', 'Notify Sub. Requested', 'Notify when list of submissions requested', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyAuthorReset', '0', 'Notify Author Pwd Reset', 'Notify when author resets password', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyAuthorSubmit', '1', 'Notify Submission', 'Notify when a submission is made', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyAuthorUpload', '1', 'Notify File Uploaded', 'Notify when a file is uploaded', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyAuthorWithdraw', '1', 'Notify Sub. Withdrawn', 'Notify when a submission is withdrawn', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyIncludeIP', '0', 'Include IP', 'Include IP address in email notices', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyReviewerEmailUsername', '0', 'Notify Rev. Email Username', 'Notify when committee member requests username be emailed', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyReviewerProfileUpdate', '0', 'Notify Rev. Profile Update', 'Notify when a committee member updates profile', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyReviewerReset', '0', 'Notify Reviewer Pwd Reset', 'Notify when a committee member resets their password', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_notifyReviewerSignup', '1', 'Notify Reviewer Sign Up', 'Notify when a committee member signs up for an account', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_paperAdvocates', '1', 'Use Sub. Advocates', 'Indicates whether submission advocate functionality is to be used', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_paperDir', '$OC_dataDir$papers/', 'File Directory', 'Directory where files are stored for review - offset from openconf/[dir]/', 1);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_paperSubNote', '', 'Submission Notice', 'Notice appearing atop submission page', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_pcemail', 'openconf@example.com', 'Chair Email', 'Used as the general contact email, including the From header for outgoing messages, and in case of errors or other follow-up.  Although a comma-delimited list of addresses (without spaces) is permitted, this is not recommended as many mail servers will reject messages coming from multiple addresses.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_privacy_banner_options', '{"display":0,"message":"This website uses cookies to provide essential services only.","dismiss":"X","link":"","href":""}', 'Cookie Banner Options', 'JSON-encoded list of attributes (display, message, dismiss, link)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_privacy_display', '1', 'Privacy Policy Link Display', 'Whether and where to display a Privacy Policy Link (0: none, 1: menu, 2: page footer).', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_privacy_link', '', 'Privacy Policy Web Address', 'Full web address to privacy policy on remote site. Leave blank to use built-in privacy template.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_programSignUpNotice', '', 'Adv. Sign Up Notice', 'Notice to appear atop program committee sign up page', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_queueEmails', '1', 'Queue Emails', 'Queues emails for delivery, storing each in the database', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_requiredField', '', 'Required Field', 'Required field designator - experimental', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerCompleteBeforeSAR', '1', 'Require Rev. Complete Own First', 'Only allow reviewer to see reviews of assigned submissions if own is complete', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerReadPapers', '1', 'Allow Rev. View All Submissions', 'Allow reviewers to read all submittions', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeAdvocate', '0', 'Allow Rev. See Advocate', 'Allow reviewers to see submission advocate', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeAssignedReviews', '0', 'Allow Rev. See Assigned Reviews', 'Allow reviewers to see other''s review of assigned submissions', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeAuthors', '0', 'Allow Rev. See Authors', 'Allow reviewers to see submission authors (i.e., non-blind reviews)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeDecision', '0', 'Allow Rev. See Decision', 'Allow reviewers to see acceptance decision', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeOtherReviewers', '1', 'Allow Rev. See Other Rev. Info', 'Allow reviewers to see each other''s information', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeOtherReviews', '0', 'Allow Rev. to See All Reviews', 'Allow reviewer to see reviews of non-assigned submissions', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerUnassignReviews', '0', 'Allow Rev. to Unassign Own Reviews', 'Allow reviewer to unassign review and delete review data', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSignUpNotice', '', 'Rev. Sign Up Notice', 'Notice to appear atop reviewer sign up page', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_ReviewerTimeout', '60', 'Reviewer Timeout', 'Reviewer session timeout in minutes or enter 0 for no timeout.  Note: subject to server PHP settings.', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subBackupEmail', NULL, 'Sub. Backup Email', 'Submission Backup Address - will get a submission''s SQL statements', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subConfirmNotice', '<p><strong>Thank you for your submission.  Your submission ID number is [:sid:].  Please write this number down and include it in any communications with us.</strong></p>\n\n<p><strong>Below is the information submitted.  We have also emailed a copy to the submission contact.  If you notice any problems or do <em>not</em> receive the email within 24 hours, please contact us.</strong></p>\n\n<p>[:formfields:]</p>', 'Sub. Confirm Notice', 'Notice displayed on Make Submission confirmation page.  Variables: [:sid:] = submission ID, [:formfields:] = form fields', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subtypes', 'Paper', 'Sub. Types', 'Internal Use Only', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_topicDisplayAlpha', '0', 'Display Topics Alphabetically', 'Display topics on submission and committee sign up forms alphabetically', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_timeZone', 'UTC', 'Time Zone', 'Default time zone for use by OpenConf', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_version', '7.40', 'OpenConf Version', 'Current OpenConf software version, manual modification discouraged', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_versionLatest', '7.40', 'Latest Version', 'Latest version of software - used for update notification', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_wordForAuthor', 'Author', 'Word for Author', 'English (US) word to be used for Author (e.g., Author, Presenter, Applicant) -- must pluralize with s at the end', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_wordForChair', 'Chair', 'Word for Chair', 'English (US) word to be used for Chair (e.g., Chair, Administrator, Editor)', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('filetype', 'MOD_FILETYPE_chairoverride', '1', 'Skip Check if Chair', 'Skips file format check if Chair uploading', 0);
INSERT INTO `config` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('filetype', 'MOD_FILETYPE_allow_rtfforword', '1', 'Accept RTF for Word Doc', 'Permit RTF MS Word docs', 0);

# --------------------------------------------------------

#
# Data for table `modules`
#

INSERT INTO `modules` (`moduleId`, `moduleActive`) VALUES ('filetype', 1);

# --------------------------------------------------------

#
# Data for table `status`
#

INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_submissions_open', NULL, 0, 'New Submission', NULL, NULL, NULL, 1);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_edit_open', NULL, 0, 'Edit Submission', NULL, NULL, NULL, 2);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_withdraw_open', NULL, 0, 'Withdraw Submission', NULL, NULL, NULL, 3);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_upload_open', NULL, 0, 'Upload File', NULL, NULL, NULL, 4);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_view_file_open', NULL, 0, 'View File', NULL, NULL, NULL, 5);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_status_open', 'When open, lets author check acceptance status and view reviewer comments (based on OC_authorSeePendingSubReviews)', 0, 'Check Status', NULL, NULL, NULL, 6);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_rev_signup_open', NULL, 0, 'Review Cmt. Sign Up', NULL, NULL, NULL, 10);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_rev_signin_open', NULL, 0, 'Review Cmt. Sign In', NULL, NULL, NULL, 11);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_reviewing_open', NULL, 0, 'Reviewing', NULL, NULL, NULL, 12);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_pc_signup_open', NULL, 0, 'Program Cmt. Sign Up', NULL, NULL, 'OC_paperAdvocates', 15);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_pc_signin_open', 'If closed and Review Cmt. Sign In is open, Program Cmt. members may still sign in, however will not be able to advocate submissions even if Advocating is open', 0, 'Program Cmt. Sign In', NULL, NULL, 'OC_paperAdvocates', 16);
INSERT INTO `status` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_advocating_open', NULL, 0, 'Advocating', NULL, NULL, 'OC_paperAdvocates', 17);
        
# --------------------------------------------------------

#
# Data for table `template`
#

INSERT INTO `template` VALUES ('authors_accept', 'email', 'OC', 'Authors - Accepted Submission', '[[:OC_confName:]] Your submission has been accepted!', 'On behalf of the [:OC_confNameFull:], I am pleased to inform you that your submission, titled\n\n[:title:]\n\nhas been accepted.  \n\nWe have included the reviewers'' feedback at the end of this message.\n\nCongratulations,\n  Program Committee, [:OC_confName:]\n  [:OC_pcemail:]\n\n[:review-fields:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('authors_nofile', 'email', 'OC', 'Authors - Missing File', '[[:OC_confName:]] Missing File', 'Thank you again for submitting to the [:OC_confNameFull:].  Please note however that we have not yet received your file.  At your earliest convenience, please upload your file so that we may begin the review process.  \n\nYour file may be uploaded through the [:OC_confName:] OpenConf system accessible at our Web site: [:OC_confURL:] .\n\nSubmission ID: [:paperid:]\nSubmission Title: [:title:]\n\nThank you,\n  Program Committee, [:OC_confName:]\n  [:OC_pcemail:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('authors_reject', 'email', 'OC', 'Authors - Rejected Submission', '[[:OC_confName:]] Submission [:paperid:] declined', 'Dear Author,\n\nOn behalf of the [:OC_confNameFull:], I am sorry to inform you that your submission, titled \n\n[:title:]\n\nhas not been accepted.  We received many excellent submissions this year, and were limited in the number we could accept.\n\nAt the end of this email you will find a set of comments from the submission reviewers.  If you have questions about the comments, please contact the Chair.\n\nSincerely,\n  Program Committee, [:OC_confName:]\n  [:OC_pcemail:]\n\n[:review-fields:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('pc_assigned', 'email', 'OC', 'Program Committee - Submissions Assigned', '[[:OC_confName:]] Submissions have been assigned to advocates', 'Now that the submission reviews have been completed, it is time to perform your advocate duties in preparation for the Program Committee meeting.  Your duties include:\n\n1.	Read all the reviews and be able to present the results of the review at the PC meeting.\n2.	If the reviews are in conflict (for example, some saying Clear Accept, while others say Reject) then we ask that you read the submission (unless you already have) to form your own opinion.\n3.	If you feel that the submission needs an additional review, let us know and we will try to obtain an additional review prior to the PC meeting.\n\nThank you again for supporting [:OC_confNameFull:].\n\n[:OC_confName:] Program Chair\n[:OC_pcemail:]\n[:OC_confURL:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('pc_norecommendation', 'email', 'OC', 'Program Committee - Missing Recommendation', '[[:OC_confName:]] Missing recommendation', 'Dear [:OC_confName:] Advocate,\n\nJust a reminder to complete your submission recommendations as soon as possible.  We show that you have at least one submission assigned for which no recommendation has been provided.\n\nIn case you don''t recall the instructions:\n\nYou will find the submissions assigned to you by going to [:OC_confURL:], selecting OpenConf, and signing in under Committee Members (functions are available to reset your user id and password if you''ve forgotten them).  This will display the list of submissions you have been assigned to review.  Clicking on the submission title will bring up the online review form and clicking on the file symbol to the right of the submission title will bring up a copy of the file.  You may reopen and modify or amend previously submitted reviews.\n\nThank you for your support of [:OC_confNameFull:].\n\n[:OC_confName:] Program Chair\n[:OC_pcemail:]\n[:OC_confURL:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('reviewers_assigned', 'email', 'OC', 'Reviewers - Reviews Assigned', '[[:OC_confName:]] Submissions have been assigned for review', 'Dear [:OC_confName:] Reviewer,\n\nThank you for signing up to review submissions submitted to [:OC_confName:].  It is now time for the review process to begin and submission assignments have been made.  Using the username and password you created when you registered to be a reviewer, you will find the submissions assigned to you by going to [:OC_confURL:], selecting OpenConf, and signing in under Committee Members.  This will display the list of submissions you have been assigned to review.  Clicking on the submission title will bring up the online review form and clicking on the file symbol to the right of the submission title will bring up a copy of the file.  You may reopen and modify or amend previously submitted reviews.\n\nIf you do not remember your username and password, features are available to let you reset them if you still remember the email address you specified when filling out the reviewer registration form.\n\nAs much as possible we tried to assign submissions that we think will interest you.  We apologize in advance if you are not interested or knowledgeable about the topic areas you were assigned.  If you will not be able to review a submission, please inform us IMMEDIATELY so we can reassign the submission to someone else.\n\nREMINDER: By reviewing unpublished submissions, you are accepting the ethical responsibility not to disclose their contents to anyone else.\n\nIf we assigned you your own submission or a submission for which you have a conflict of interest, let us know and we will reassign you!\n\nPlease complete these reviews as soon as possible. \n\nThanks again for helping make [:OC_confNameFull:] a success!\n\n[:OC_confName:] Program Chair\n[:OC_pcemail:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('reviewers_nocomment', 'email', 'OC', 'Reviewers - Missing Author Comments', '[[:OC_confName:]] Missing comments for authors', 'Please be sure to fill out the Comments for the Authors field in all reviews.  We show that you have at least one review assigned for which you have not included any comments.\n\nIn case you don''t recall the instructions:\n\nYou will find the submissions assigned to you by going to [:OC_confURL:], selecting OpenConf, and signing in under Committee Members (functions are available to reset your user id and password if you''ve forgotten them).  This will display the list of submissions you have been assigned to review.  Clicking on the submission title will bring up the online review form and clicking on the file symbol to the right of the submission title will bring up a copy of the file.  You may reopen and modify or amend previously submitted reviews.\n\nThank you for your support of [:OC_confNameFull:].\n\n[:OC_confName:] Program Chair\n[:OC_pcemail:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('reviewers_noreview', 'email', 'OC', 'Reviewers - Missing Review', '[[:OC_confName:]] Missing review', 'Just a reminder to complete your submission reviews as soon as possible.  We show that you have at least one review assigned for which you have not indicated that it is completed or that is missing a recommendation.\n\nIn case you don''t recall the instructions:\n\nYou will find the submissions assigned to you by going to [:OC_confURL:], selecting OpenConf, and signing in under Committee Members (functions are available to reset your user id and password if you''ve forgotten them).  This will display the list of submissions you have been assigned to review.  Clicking on the submission title will bring up the online review form and clicking on the file symbol to the right of the submission title will bring up a copy of the file.  You may reopen and modify or amend previously submitted reviews.\n\nThank you for your support of [:OC_confNameFull:].\n\n[:OC_confName:] Program Chair\n[:OC_pcemail:]\n', NULL, NULL);
INSERT INTO `template` VALUES ('reviewers_reminder', 'email', 'OC', 'Reviewers - Reminder', '[[:OC_confName:]] Submissions have been assigned for review', 'Just a reminder to complete your submission reviews as soon as possible.  For those who haven''t started and don''t recall the instructions:\n\nYou will find the submissions assigned to you by going to [:OC_confURL:], selecting OpenConf, and signing in under Committee Members (functions are available to reset your user id and password if you''ve forgotten them).  This will display the list of submissions you have been assigned to review.  Clicking on the submission title will bring up the online review form and clicking on the file symbol to the right of the submission title will bring up a copy of the file.  You may reopen and modify or amend previously submitted reviews.\n\nThank you for your support of [:OC_confNameFull:].\n\n[:OC_confName:] Program Chair\n[:OC_pcemail:]\n', NULL, NULL);

INSERT INTO `template` VALUES ('author-edit', 'notification', 'OC', 'Author - Edit Submission', 'Submission Update ID [:sid:]', '[:fields:]', NULL, '{"fields":"Fields","sid":"Submission ID"}');
INSERT INTO `template` VALUES ('author-submit', 'notification', 'OC', 'Author - New Submission', 'Submission ID [:sid:]', 'Thank you for your submission to [:OC_confName:].  Below is a copy of the information submitted for your records.\n\n[:fields:]', NULL, '{"fields":"Fields","sid":"Submission ID"}');
INSERT INTO `template` VALUES ('author-upload', 'notification', 'OC', 'Author - File Upload', 'Submission ID [:sid:] file uploaded', 'Submission ID [:sid:] has been uploaded.\n\n[:error:]', NULL, '{"sid":"Submission ID","error":"Upload Error"}');
INSERT INTO `template` VALUES ('author-withdraw', 'notification', 'OC', 'Author - Withdraw Submission', 'Submission Withdraw - ID [:sid:]', 'The submission below has been withdrawn at the author''s request.  If you did not intend to withdraw the submission, please reply back.\n\n[:submission:]', NULL, '{"sid":"Submission ID","submission":"Submission ID/Title"}');
INSERT INTO `template` VALUES ('chair-assign_reviews', 'notification', 'OC', 'Chair - Manual Review Assignment(s)', 'New Reviewer Assignment(s)', 'New assignments have been made for you to review in the [:OC_confName:] OpenConf system:\n\n\n[:assignments:]\n\nThank you.', NULL, '{"assignments":"New Assignments","name_first":"First Name","name_last":"Last Name","name":"Full Name","reviewerid":"Reviewer ID","username":"Username"}');
INSERT INTO `template` VALUES ('chair-assign_advocates', 'notification', 'OC', 'Chair - Manual Advocate Assignment(s)', 'New Advocate Assignment(s)', 'New assignments have been made for you to advocate in the [:OC_confName:] OpenConf system:\n\n\n[:assignments:]\n\nThank you.', NULL, '{"assignments":"New Assignments","name_first":"First Name","name_last":"Last Name","name":"Full Name","advocateid":"Advocate ID","username":"Username"}');
INSERT INTO `template` VALUES ('committee-review', 'notification', 'OC', 'Reviewer - Submit Review', 'Review of submission [:sid:]', 'Following is a copy of your review for submission number [:sid:] submitted to [:OC_confName:].  Note that you will receive this email even if an error occurred during submission.\n\n[:fields:]', NULL, '{"fields":"Fields","sid":"Submission ID"}');
INSERT INTO `template` VALUES ('committee-reviewunassign', 'notification', 'OC', 'Reviewer - Unassign Review', 'Review Unassigned [:sid:]-[:reviewerid:]', 'Reviewer [:name:] ([:reviewerid:]) has been unassigned from submission:\n\n[:sid:]. [:title:]', NULL, '{"sid":"Submission ID","title":"Submission Title","reviewerid":"Reviewer ID","username":"Reviewer Username","name":"Reviewer Name"}');
INSERT INTO `template` VALUES ('committee-withdrawnsub', 'notification', 'OC', 'Committee - Withdrawn Submission', 'Submission [:sid:] Withdrawn', 'The submission below has been withdrawn and is no longer assigned to you.\n\n[:sid:]. [:title:]', NULL, '{"sid":"Submission ID","title":"Submission Title","memberid":"Member ID","username":"Member Username","name":"Member Name"}');
INSERT INTO `template` VALUES ('committee-signup', 'notification', 'OC', 'Committee - New Signup', 'Committee Signup', 'Thank you for signing up for the [:OC_confName:] [:committee:].  Below is the information you provided.  If you have any questions, please contact [:OC_pcemail:] or reply to this email.\n\n[:fields:]', NULL, '{"committee":"Committee Type","fields":"Fields"}');
INSERT INTO `template` VALUES ('committee-update', 'notification', 'OC', 'Committee - Profile Update', 'Committee Member Profile Updated', 'Your profile has been updated.  The submitted information follows below:\n\n[:fields:]', NULL, '{"fields":"Fields"}');

INSERT INTO `template` VALUES ('privacy_policy', 'other', 'OC', 'Privacy Policy', '', '<p><strong>What information is being collected?</strong></p>\n\n<p>For anyone browsing our web site, our server records the page visited, IP address where the request came from, and if included the browser type and referring web page.</p>\n\n<p>If you choose to make a submission, become a reviewer or advocate, or serve as a discussant or session chair, we will request your name, email address, and affiliation, along with possibly other information as denoted when filling out the respective form for each role.</p>\n\n<p><strong>Who is collecting it?</strong></p>\n\n<p>Information is collected by the entity and/or activity named atop this page. You may contact us by using the Email menu link above.</p>\n\n<p><strong>How is it collected?</strong></p>\n\n<p>Information used in this system is collected through the forms provided (e.g., submission, account sign up), or automatically through our server logs (e.g., page visited, IP address).</p>\n\n<p><strong>Why is it being collected?</strong></p>\n\n<p>Information is being collected in relation to the activity named atop this page and your optional participation in this activity.</p>\n\n<p><strong>How will it be used?</strong></p>\n\n<p>The information automatically recorded by our server is used to troubleshoot issues and improve our service. Other information collected is used for evaluation, communication, and publishing related to the activity specified above.</p>\n\n<p><strong>Who will it be shared with?</strong></p>\n\n<p>Information collected is shared with the organizers of the above activity. A subset of information may also be shared with other participants in the activity (e.g., reviewers). Some of the information (e.g., name, affiliation) of accepted submitters and other participants may also be made publicly available to meet the goals of the activity.</p>\n\n<p>By virtue of the information being hosted on a server, the information is accessible to our hosting provider and subject to their privacy policy. Additional third-party providers may include: payment processor, plagiarism detection service, copyright processing service, reviewer credit service, publishing service.</p>\n\n<p><strong>How do I correct information or request its erasure?</strong></p>\n\n<p>In order to correct information, submitters and committee members may use the Edit Submission and Update Profile options respectively. If these do not apply to you, are no longer accessible, or you wish to request erasure of your data, use the Email menu link above to contact us. We ask that you contact us as erasure of the data may impact others on the system and the activity specified above, thus requiring manual intervention and notification on our part.</p>\n\n<p><strong>Are cookies or other trackers used?</strong></p>\n\n<p>Temporary (session) cookies are used to maintain language selection and allow you to remain logged into your account without having to keep re-entering credentials — these cookies are removed upon signing out of your account or exiting your browser. Cookies are also stored for a 1-month period for maintaining personalized program options and a 1-year period for recording your cookie consent acknowledgement — these cookies do not store any personally-identifiable information.</p>', NULL, NULL);

# --------------------------------------------------------
   
#
# Data for table `topic`
#

INSERT INTO `topic` (`topicid`, `topicname`, `short`) VALUES (1, 'default', '');

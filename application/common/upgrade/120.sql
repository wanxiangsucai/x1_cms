INSERT INTO `qb_admin_menu` (`id`, `pid`, `type`, `name`, `title`, `url`, `target`, `ifshow`, `list`, `style`, `groupid`, `tier`, `icon`, `fontcolor`, `bgcolor`, `script`, `allowgroup`) VALUES(0, 11, 0, 'WAP注册页标签', '', '/index.php/index/reg/index.html?label_set=set&in=wap', 0, 1, 0, '', 3, 0, '', '', '', '', '');
INSERT INTO `qb_admin_menu` (`id`, `pid`, `type`, `name`, `title`, `url`, `target`, `ifshow`, `list`, `style`, `groupid`, `tier`, `icon`, `fontcolor`, `bgcolor`, `script`, `allowgroup`) VALUES(0, 11, 0, 'WAP登录页标签', '', '/index.php/index/login/index.html?label_set=set&in=wap', 0, 1, 0, '', 3, 0, '', '', '', '', '');
INSERT INTO `qb_admin_menu` (`id`, `pid`, `type`, `name`, `title`, `url`, `target`, `ifshow`, `list`, `style`, `groupid`, `tier`, `icon`, `fontcolor`, `bgcolor`, `script`, `allowgroup`) VALUES(0, 11, 0, 'WAP手机登录注册页', '', '/index.php/index/login/phone.html?label_set=set&in=wap', 0, 1, 0, '', 3, 0, '', '', '', '', '');
UPDATE `qb_admin_menu` SET list='-1' WHERE id='14';


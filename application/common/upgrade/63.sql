DELETE FROM `qb_config` WHERE `c_key`='must_yz_phone';
INSERT INTO `qb_config` (`id`, `type`, `title`, `c_key`, `c_value`, `form_type`, `options`, `ifsys`, `htmlcode`, `c_descrip`, `list`, `sys_id`) VALUES(0, 8, '是否强制绑定手机号', 'must_yz_phone', '0', 'radio', '0|不强制\r\n1|强制绑定', 1, '', '', 0, 0);

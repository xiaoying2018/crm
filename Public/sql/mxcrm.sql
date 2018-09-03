SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO" ;

CREATE TABLE IF NOT EXISTS `mxcrm_account_money` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `account_id` int(10) NOT NULL COMMENT '银行账户ID',
  `money` decimal(10,2) NOT NULL COMMENT '账户初始化余额',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '初始化时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='财务账户初始化余额' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_action_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `action_name` varchar(100) NOT NULL,
  `param_name` varchar(100) DEFAULT NULL,
  `action_id` int(10) NOT NULL,
  `action_delete` int(1) NOT NULL COMMENT '查动态时使用  0：不是删除操作  1：为删除操作',
  `content` varchar(500) NOT NULL,
  `create_time` int(10) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='操作日志表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_announcement` (
  `announcement_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `order_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL COMMENT '发表人岗位',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(10) NOT NULL COMMENT '发表时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `color` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL COMMENT '通知部门id',
  `status` int(1) NOT NULL COMMENT '是否发布1发布2停用',
  `isshow` int(1) NOT NULL DEFAULT '0' COMMENT '是否公开1是0否',
  PRIMARY KEY (`announcement_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='存放知识文章信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_announcement_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL COMMENT '公告ID',
  `role_id` int(11) NOT NULL COMMENT '阅读人',
  `read_time` int(10) NOT NULL COMMENT '阅读时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='公告阅读表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_bank_account` (
  `account_id` int(10) NOT NULL AUTO_INCREMENT,
  `bank_account` varchar(50) NOT NULL COMMENT '银行账号',
  `company` varchar(50) NOT NULL COMMENT '收款单位',
  `open_bank` varchar(50) NOT NULL COMMENT '开户行',
  `description` varchar(50) NOT NULL COMMENT '备注',
  PRIMARY KEY (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='银行账户表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_business` (
  `business_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '商机id',
  `name` varchar(255) NOT NULL DEFAULT '..',
  `code` varchar(20) NOT NULL DEFAULT '' COMMENT '商机编号',
  `prefixion` varchar(30) NOT NULL,
  `customer_id` int(10) NOT NULL COMMENT '客户id',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者岗位',
  `owner_role_id` int(10) NOT NULL COMMENT '所有者岗位',
  `total_amount` int(10) unsigned NOT NULL COMMENT '产品总数',
  `total_subtotal_val` decimal(16,2) NOT NULL COMMENT '共计价格(折前)',
  `final_discount_rate` decimal(5,2) NOT NULL COMMENT '整单折扣',
  `final_price` decimal(16,2) NOT NULL COMMENT '最终价格',
  `create_time` int(10) NOT NULL COMMENT '商机创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `status_id` int(10) NOT NULL COMMENT '商机状态id',
  `nextstep_time` int(10) NOT NULL,
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL,
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `contacts_id` int(10) NOT NULL COMMENT '商机联系人',
  `possibility` varchar(500) NOT NULL COMMENT '可能性',
  PRIMARY KEY (`business_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表存放商机相关信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_business_status` (
  `status_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '商机状态',
  `name` varchar(20) DEFAULT NULL COMMENT '商机状态名',
  `order_id` int(10) DEFAULT NULL COMMENT '顺序号',
  `is_end` int(1) NOT NULL,
  `description` varchar(200) DEFAULT NULL COMMENT '商机状态描述',
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='本表存放商机状态信息' AUTO_INCREMENT=101 ;

INSERT INTO `mxcrm_business_status` (`status_id`, `name`, `order_id`, `is_end`, `description`) VALUES
(1, '初步洽谈', 1, 0, '初步洽谈'),
(2, '深入沟通', 2, 0, '深入沟通'),
(3, '销售定价', 3, 0, '定价'),
(98, '合同发票', 6, 1, '合同发票'),
(99, '项目失败', 99, 1, '项目失败'),
(100, '完成收款', 100, 1, '完成收款');

CREATE TABLE IF NOT EXISTS `mxcrm_comment` (
  `comment_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '评论id',
  `content` varchar(1000) NOT NULL COMMENT '评论内容',
  `creator_role_id` int(10) NOT NULL COMMENT '评论人',
  `to_role_id` int(10) NOT NULL COMMENT '被评论人',
  `module` varchar(50) NOT NULL COMMENT '模块',
  `module_id` int(10) NOT NULL COMMENT '模块id',
  `create_time` int(10) NOT NULL COMMENT '添加时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='评论表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_config` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `value` text NOT NULL,
  `description` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

INSERT INTO `mxcrm_config` (`id`, `name`, `value`, `description`) VALUES
(1, 'defaultinfo', 'a:10:{s:4:"logo";N;s:8:"logo_min";N;s:4:"name";s:9:"MXCRM";s:11:"description";s:24:"客户关系管理系统";s:5:"state";s:9:"北京市";s:4:"city";s:9:"市辖区";s:15:"allow_file_type";s:55:"pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,txt,zip";s:19:"contract_alert_time";i:30;s:10:"task_model";s:0:"";s:10:"is_invoice";s:0:"";}', ''),
(2, 'customer_outdays', '7', '客户设置放入客户池天数'),
(3, 'customer_limit_condition', 'month', '客户池领取条件限制 day：今日 week： 本周 month：本月'),
(4, 'customer_limit_counts', '30', '客户池领取次数限制'),
(5, 'leads_outdays', '7', '线索超出天数放入客户池'),
(6, 'contract_custom', 'C_', ''),
(7, 'num_id', 'and', ''),
(8, 'is_invoice', '', '是否添加开发票选项'),
(9, 'receivables_custom', 'M_', '应收款前缀'),
(10, 'sms', 'a:5:{s:3:"uid";s:17:"";s:6:"";s:6:"";s:9:"sign_name";s:6:"";s:12:"sign_sysname";s:0:"";s:4:"name";s:3:"sms";}', ''),
(11, 'smtp', 'a:9:{s:12:"MAIL_ADDRESS";s:16:"";s:9:"MAIL_SMTP";s:11:"";s:9:"MAIL_PORT";s:2:"";s:14:"MAIL_LOGINNAME";s:16:"";s:13:"MAIL_PASSWORD";s:16:"";s:11:"MAIL_SECURE";N;s:12:"MAIL_CHARSET";s:5:"UTF-8";s:9:"MAIL_AUTH";b:1;s:9:"MAIL_HTML";b:1;}', ''),
(12, 'business_custom', 'M_', '商机编码前缀'),
(13, 'openrecycle', '2', ''),
(14, 'business_code', '0', '商机编号数'),
(15, 'contract_outdays', '30', ''),
(16, 'cc_check', '', ''),
(17, 'bc_check', '', ''),
(18, 'fc_check', '', ''),
(19, 'contract_outdays', '30', ''),
(20, 'user_custom', 'K', '员工编号前缀'),
(21, 'uc_check', '', '员工编号前缀是否替换');

CREATE TABLE IF NOT EXISTS `mxcrm_contacts` (
  `contacts_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '联系人id',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者岗位id',
  `name` varchar(50) NOT NULL COMMENT '联系人姓名',
  `post` varchar(20) NOT NULL COMMENT '客户联系人岗位',
  `department` varchar(20) NOT NULL COMMENT '客户联系人部门',
  `sex` varchar(50) NOT NULL COMMENT '联系人性别',
  `saltname` varchar(20) NOT NULL DEFAULT '',
  `telephone` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `qq_no` varchar(50) NOT NULL DEFAULT '',
  `contacts_address` varchar(500) NOT NULL COMMENT '地址',
  `zip_code` varchar(20) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '信息更新时间',
  `is_deleted` int(1) NOT NULL COMMENT '是否被删除',
  `delete_role_id` int(10) NOT NULL,
  `delete_time` int(10) NOT NULL,
  `gender` varchar(20) NOT NULL COMMENT '性别',
  `role` varchar(255) NOT NULL DEFAULT '' COMMENT '角色',
  PRIMARY KEY (`contacts_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表存放客户联系人对应关系信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_contacts_data` (
  `contacts_id` int(10) NOT NULL,
  `ceshi` text NOT NULL,
  `last_time` int(10) NOT NULL,
  `last_content` text NOT NULL,
  `constellation` varchar(255) NOT NULL DEFAULT '',
  `hobby` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`contacts_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `mxcrm_contract` (
  `contract_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `prefixion` varchar(50) NOT NULL COMMENT '表前缀',
  `number` varchar(50) NOT NULL COMMENT '编号',
  `business_id` int(10) NOT NULL COMMENT '商机',
  `supplier_id` int(10) NOT NULL COMMENT '供应商id',
  `customer_id` int(10) NOT NULL COMMENT '客户id',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '合同类型',
  `price` decimal(10,2) NOT NULL COMMENT '总价',
  `count_nums` int(11) NOT NULL COMMENT '产品数量',
  `due_time` int(10) NOT NULL COMMENT '签约日期',
  `owner_role_id` int(10) NOT NULL COMMENT '负责人',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者',
  `content` text NOT NULL COMMENT '合同内容',
  `description` text NOT NULL COMMENT '描述',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `start_date` int(10) NOT NULL COMMENT '生效时间',
  `end_date` int(10) NOT NULL COMMENT '到期时间',
  `status` varchar(20) NOT NULL COMMENT '合同状态',
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `contract_name` varchar(100) NOT NULL COMMENT '合同名称',
  `is_checked` int(10) NOT NULL COMMENT '是否审核0未审核1通过2未通过',
  `check_time` int(10) NOT NULL COMMENT '审核时间',
  `check_des` int(10) NOT NULL COMMENT '审核备注',
  `examine_role_id` int(11) NOT NULL COMMENT '审核人',
  PRIMARY KEY (`contract_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_contract_check` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `role_id` int(11) NOT NULL COMMENT '负责人ID',
  `is_checked` int(11) NOT NULL COMMENT '审核状态',
  `content` varchar(200) DEFAULT NULL COMMENT '审核内容',
  `check_time` int(10) NOT NULL COMMENT '审核时间',
  PRIMARY KEY (`check_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_control` (
  `control_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '操作id',
  `module_id` int(10) NOT NULL COMMENT '模块id',
  `name` varchar(20) NOT NULL COMMENT '操作名',
  `m` varchar(20) NOT NULL COMMENT '对应Action',
  `a` varchar(20) NOT NULL COMMENT '行为',
  `parameter` varchar(50) NOT NULL COMMENT '参数',
  `description` varchar(200) NOT NULL COMMENT '操作描述',
  PRIMARY KEY (`control_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='本表存放操作信息' AUTO_INCREMENT=79 ;

INSERT INTO `mxcrm_control` (`control_id`, `module_id`, `name`, `m`, `a`, `parameter`, `description`) VALUES
(1, 1, 'crm面板操作', 'index', 'index', '', 'CRM系统面板'),
(2, 7, '修改个人信息', 'User', 'edit', '', '是的法士大夫地方'),
(4, 7, '添加用户', 'User', 'add', '', ''),
(78, 7, '删除员工', 'User', 'delete', '', ''),
(6, 7, '添加部门', 'User', 'department_add', '', ''),
(7, 7, '修改部门', 'User', 'department_edit', '', ''),
(8, 7, '删除部门', 'User', 'department_delete', '', ''),
(9, 7, '添加岗位', 'User', 'role_add', '', ''),
(10, 7, '修改岗位', 'User', 'role_edit', '', ''),
(11, 7, '删除岗位', 'User', 'role_delete', '', ''),
(12, 2, '添加商机', 'Business', 'add', '', ''),
(34, 2, '完整商机信息', 'Business', 'view', '', ''),
(13, 2, '修改商机', 'Business', 'edit', '', ''),
(14, 2, '删除商机', 'Business', 'delete', '', ''),
(15, 2, '添加商机日志', 'Business', 'addLogging', '', ''),
(16, 2, '修改商机日志', 'Business', 'eidtLogging', '', ''),
(17, 2, '删除商机日志', 'Business', 'deleteLogging', '', ''),
(18, 1, '用户登录', 'User', 'login', '', ''),
(19, 1, '用户注册', 'User', 'register', '', ''),
(20, 1, '退出', 'User', 'logout', '', ''),
(21, 7, '查看部门信息', 'User', 'department', '', ''),
(22, 1, '找回密码', 'User', 'lostPW', '', ''),
(23, 1, '重置密码', 'User', 'lostpw_reset', '', ''),
(24, 7, '查看员工信息', 'User', 'index', '', ''),
(25, 7, '查看岗位信息', 'User', 'role', '', ''),
(26, 7, '岗位分配', 'User', 'user_role_relation', '', ''),
(27, 7, '员工资料修改', 'User', 'editUsers', '', ''),
(28, 1, '查看我的日志', 'User', 'mylog', '', ''),
(60, 6, '岗位授权', 'Permission', 'authorize', '', ''),
(30, 7, '个人日志详情', 'User', 'mylog_view', '', ''),
(31, 7, '删除个人日志', 'User', 'mylog_delete', '', ''),
(32, 2, '查看商机信息', 'Business', 'index', '', ''),
(33, 2, '查看商机日志', 'Business', 'logging', '', ''),
(35, 3, '产品列表', 'product', 'index', '', ''),
(36, 3, '添加产品', 'Product', 'add', '', ''),
(37, 3, '修改产品信息', 'product', 'edit', '', ''),
(38, 3, '删除产品', 'Product', 'delete', '', ''),
(39, 3, '查看产品分类信息', 'Product', 'category', '', ''),
(40, 3, '添加产品分类', 'Product', 'category_add', '', ''),
(41, 3, '删除产品分类', 'Product', 'deleteCategory', '', ''),
(42, 3, '修改产品分类', 'Product', 'editcategory', '', ''),
(43, 3, '产品销量统计', 'Product', 'count', '', ''),
(44, 5, '查看客户信息', 'Customer', 'customerView', '', ''),
(45, 5, '添加客户', 'Customer', 'add', '', ''),
(46, 5, '修改客户信息', 'Customer', 'edit', '', ''),
(47, 5, '删除客户', 'Customer', 'delete', '', ''),
(48, 5, '添加客户联系人', 'Contacts', 'add', '', ''),
(49, 5, '查看客户联系人', 'Contacts', 'view', '', ''),
(50, 5, '删除客户联系人', 'Contacts', 'delete', '', ''),
(51, 5, '修改客户联系人', 'Contacts', 'edit', '', ''),
(52, 6, '查看操作模块', 'Permission', 'module', '', ''),
(53, 6, '修改操作模块', 'Permission', 'module_edit', '', ''),
(54, 6, '添加操作模块信息', 'Permission', 'module_add', '', ''),
(55, 6, '删除操作模块', 'Permission', 'module_delete', '', ''),
(56, 6, '查看操作信息', 'Permission', 'index', '', ''),
(57, 6, '修改操作', 'Permission', 'control_edit', '', ''),
(58, 6, '删除模块', 'Permission', 'control_delete', '', ''),
(59, 6, '添加操作', 'Permission', 'control_add', '', ''),
(61, 9, 'smtp设置', 'Config', 'smtpConfig', '', ''),
(62, 9, '删除状态', 'Config', 'deleteStatus', '', ''),
(63, 9, '修改状态', 'Config', 'editStatus', '', ''),
(64, 9, '添加状态', 'Config', 'addStatus', '', ''),
(65, 9, '查看状态', 'Config', 'statusList', '', ''),
(66, 9, '查看状态流', 'Config', 'flowList', '', ''),
(67, 9, '添加状态流', 'Config', 'addStatusflow', '', ''),
(68, 9, '删除状态流', 'Config', 'deleteStatusFlow', '', ''),
(69, 9, '修改状态流信息', 'Config', 'editStatusFlow', '', '');

CREATE TABLE IF NOT EXISTS `mxcrm_customer` (
  `customer_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '客户id',
  `owner_role_id` int(10) NOT NULL COMMENT '所有者岗位',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者id',
  `contacts_id` int(10) NOT NULL DEFAULT '0' COMMENT '首要联系人',
  `name` varchar(333) NOT NULL DEFAULT '' COMMENT '客户名称',
  `origin` varchar(150) NOT NULL DEFAULT '' COMMENT '客户信息来源',
  `industry` varchar(150) NOT NULL DEFAULT '' COMMENT '客户行业',
  `create_time` int(10) NOT NULL COMMENT '建立时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `get_time` int(10) NOT NULL COMMENT '领取或分配时间',
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `is_locked` int(1) NOT NULL COMMENT '是否锁定',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `grade` varchar(255) NOT NULL DEFAULT '1' COMMENT '客户等级',
  `customer_code` varchar(255) NOT NULL DEFAULT '' COMMENT '客户编号',
  `address` varchar(500) NOT NULL COMMENT '客户地址',
  `customer_owner_id` varchar(50) NOT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表存放客户的相关信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_customer_data` (
  `customer_id` int(10) unsigned NOT NULL COMMENT '客户id',
  `no_of_employees` varchar(150) NOT NULL DEFAULT '' COMMENT '员工数',
  `description` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='客户附表信息';

CREATE TABLE IF NOT EXISTS `mxcrm_customer_record` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL COMMENT '客户',
  `user_id` int(10) NOT NULL COMMENT '用户',
  `start_time` int(10) NOT NULL COMMENT '时间',
  `type` int(10) NOT NULL COMMENT '1：领取 2：分配',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii COMMENT='客户记录表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_email_template` (
  `template_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `subject` varchar(200) NOT NULL COMMENT '主题',
  `title` varchar(100) NOT NULL,
  `content` varchar(500) NOT NULL COMMENT '内容',
  `order_id` int(4) NOT NULL COMMENT '顺序id',
  PRIMARY KEY (`template_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短信模板' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_examine` (
  `examine_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_role_id` int(10) NOT NULL COMMENT '创建人',
  `owner_role_id` varchar(200) NOT NULL COMMENT '出差人',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `type` int(2) NOT NULL COMMENT '审批类型',
  `content` text NOT NULL COMMENT '审批内容',
  `examine_role_id` int(10) NOT NULL COMMENT '审批人',
  `cate` varchar(200) DEFAULT NULL COMMENT '审批事项',
  `start_time` int(10) NOT NULL COMMENT '开始时间',
  `end_time` int(10) NOT NULL COMMENT '结束时间',
  `duration` float(10,1) NOT NULL COMMENT '时长：天',
  `money` float(10,2) NOT NULL COMMENT '报销金额/借款金额',
  `budget` int(10) NOT NULL COMMENT '预算金额',
  `advance` int(10) NOT NULL COMMENT '预支金额',
  `start_address` varchar(200) NOT NULL COMMENT '出发地',
  `vehicle` varchar(30) NOT NULL COMMENT '交通工具',
  `end_address` varchar(200) NOT NULL COMMENT '目的地 出差地',
  `examine_status` int(1) NOT NULL COMMENT '状态',
  `order_id` int(2) NOT NULL COMMENT '审核排序ID',
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `description` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`examine_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='审批表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_examine_check` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `role_id` int(11) NOT NULL COMMENT '负责人ID',
  `is_checked` int(11) NOT NULL COMMENT '审核状态',
  `content` varchar(200) DEFAULT NULL COMMENT '审核内容',
  `check_time` int(10) NOT NULL COMMENT '审核时间',
  PRIMARY KEY (`check_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_examine_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file_id` int(10) NOT NULL,
  `examine_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文件和审批对应关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_examine_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(11) NOT NULL COMMENT '审批类型',
  `name` varchar(30) NOT NULL COMMENT '审批名',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `type` int(11) NOT NULL COMMENT '0启用1停用',
  `option` int(11) NOT NULL COMMENT '0自选1设置',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

INSERT INTO `mxcrm_examine_status` (`id`, `status`, `name`, `update_time`, `type`, `option`) VALUES
(1, 1, '普通审批', 1486694160, 0, 0),
(2, 2, '请假审批', 1486694160, 0, 0),
(3, 3, '普通报销', 1486694160, 0, 0),
(4, 4, '差旅报销', 1486694160, 0, 0),
(5, 5, '出差申请', 1486694160, 0, 0),
(6, 6, '借款申请', 1486694160, 0, 0);

CREATE TABLE IF NOT EXISTS `mxcrm_examine_step` (
  `step_id` int(10) NOT NULL AUTO_INCREMENT,
  `department_id` int(10) NOT NULL COMMENT '部门ID',
  `process_id` int(10) NOT NULL COMMENT '所属流程',
  `name` varchar(50) NOT NULL COMMENT '步骤名称',
  `position_id` int(10) NOT NULL COMMENT '岗位',
  `role_id` int(10) NOT NULL COMMENT '审批人',
  `order_id` int(10) NOT NULL COMMENT '排序',
  PRIMARY KEY (`step_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='审批流程 - 步骤' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_examine_travel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(10) NOT NULL COMMENT '审批ID',
  `start_address` varchar(150) NOT NULL COMMENT '出发地',
  `start_time` int(10) NOT NULL COMMENT '出发时间',
  `end_address` varchar(150) NOT NULL COMMENT '目的地',
  `end_time` int(10) NOT NULL COMMENT '到达时间',
  `vehicle` varchar(40) NOT NULL COMMENT '交通工具',
  `duration` varchar(10) NOT NULL COMMENT '住宿(天)',
  `money` decimal(9,2) NOT NULL COMMENT '金额',
  `description` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_fields` (
  `field_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `model` varchar(20) NOT NULL COMMENT '对应模块小写，如business',
  `is_main` int(1) NOT NULL COMMENT '是否是主表字段1是0否',
  `field` varchar(50) NOT NULL COMMENT '数据库字段名',
  `name` varchar(100) NOT NULL COMMENT '显示标识',
  `form_type` varchar(20) NOT NULL COMMENT '数据类型 text 单行文本 textarea 多行文本 editor 编辑器 box 选项 datetime 日期 number 数字 user员工email邮箱phone手机号mobile电话phone',
  `default_value` varchar(100) NOT NULL COMMENT '默认值',
  `color` varchar(20) NOT NULL COMMENT '颜色',
  `max_length` int(4) NOT NULL COMMENT '字段长度',
  `is_unique` int(1) NOT NULL COMMENT '是否唯一1是0否',
  `is_null` int(1) NOT NULL COMMENT '是否允许为空',
  `is_validate` int(1) NOT NULL COMMENT '是否验证',
  `in_index` int(1) NOT NULL COMMENT '是否列表页显示1是0否',
  `in_add` int(1) NOT NULL DEFAULT '1' COMMENT '是否添加时显示1是0否',
  `input_tips` varchar(500) NOT NULL COMMENT '输入提示',
  `setting` text NOT NULL COMMENT '设置',
  `order_id` int(5) NOT NULL COMMENT '同一模块内的顺序id',
  `operating` int(1) NOT NULL COMMENT '0改删、1改、2无、3删',
  `is_show` int(1) NOT NULL COMMENT '是否在客户页显示',
  PRIMARY KEY (`field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='字段表' AUTO_INCREMENT=47 ;

INSERT INTO `mxcrm_fields` (`field_id`, `model`, `is_main`, `field`, `name`, `form_type`, `default_value`, `color`, `max_length`, `is_unique`, `is_null`, `is_validate`, `in_index`, `in_add`, `input_tips`, `setting`, `order_id`, `operating`, `is_show`) VALUES
(1, '', 1, 'owner_role_id', '负责人', 'user', '', '', 10, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(2, '', 1, 'creator_role_id', '创建人', 'user', '', '', 10, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(3, '', 1, 'delete_role_id', '删除人', 'user', '', '', 10, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(4, '', 1, 'is_deleted', '是否删除', 'deleted', '', '', 1, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(5, '', 1, 'create_time', '创建时间', 'datetime', '', '', 10, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(6, '', 1, 'update_time', '更新时间', 'datetime', '', '', 10, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(7, '', 1, 'delete_time', '删除时间', 'datetime', '', '', 10, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(8, 'customer', 1, 'name', '客户名称', 'text', '', '5521FF', 333, 1, 1, 1, 1, 1, '测试客户', '', 0, 1, 0),
(9, 'customer', 1, 'origin', '客户信息来源', 'box', '', '333333', 150, 0, 1, 1, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''电话营销'',2=>''网络营销'',3=>''上门推销''))', 4, 1, 0),
(10, 'customer', 1, 'industry', '客户行业', 'box', '', '050505', 150, 0, 0, 0, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''IT/教育'',2=>''电子/商务'',3=>''对外贸易'',4=>''酒店、旅游'',5=>''金融、保险'',6=>''房产行业'',7=>''医疗/保健'',8=>''政府、机关''))', 2, 1, 0),
(11, 'customer', 0, 'no_of_employees', '员工数', 'box', '', '0A0A0A', 150, 0, 0, 0, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''10人以下'',2=>''10--20人'',3=>''20-50人'',4=>''50人以上''))', 5, 1, 0),
(12, 'customer', 0, 'description', '备注', 'textarea', '', '333333', 0, 0, 0, 1, 0, 1, '', '', 7, 1, 0),
(13, 'customer', 1, 'customer_code', '客户编号', 'text', '', '333333', 0, 1, 1, 1, 1, 1, '', '', 1, 1, 0),
(14, 'customer', 1, 'grade', '客户等级', 'box', '1', '333333', 0, 0, 1, 0, 1, 1, '', 'array(''type''=>''radio'',''data''=>array(1=>''1'',2=>''2'',3=>''3'',4=>''4'',5=>''5''))', 6, 2, 0),
(15, 'customer', 1, 'address', '客户地址', 'address', '', '333333', 0, 0, 1, 0, 1, 1, '', '', 3, 1, 0),
(16, 'customer', 1, 'customer_owner_id', '客户负责人', 'text', '', '333333', 0, 0, 0, 0, 1, 1, '', '', 0, 2, 0),
(17, 'product', 1, 'sketch', '商品描述', 'text', '', '333333', 0, 0, 1, 0, 0, 1, '', '', 0, 2, 0),
(18, 'product', 1, 'product_num', '产品编号', 'text', '', '333333', 0, 0, 0, 0, 1, 1, '', '', 3, 1, 0),
(19, 'product', 1, 'standard', '规格', 'box', '', '333333', 200, 0, 1, 1, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''个'',2=>''箱'',3=>''套'',4=>''盒'',5=>''瓶'',6=>''块'',7=>''只'',8=>''把'',9=>''枚'',10=>''条''))', 2, 1, 0),
(20, 'product', 0, 'description', '备注', 'textarea', '', '', 0, 0, 0, 0, 0, 1, '', '', 6, 1, 0),
(21, 'product', 1, 'name', '产品名称', 'text', '', '021012', 200, 0, 1, 1, 1, 1, '', '', 0, 1, 0),
(22, 'product', 1, 'cost_price', '成本价', 'floatnumber', '', '1F1F1F', 10, 0, 0, 0, 1, 1, '', '', 4, 2, 0),
(23, 'product', 1, 'suggested_price', '建议售价', 'floatnumber', '', '', 0, 0, 0, 0, 1, 1, '', '', 5, 2, 0),
(24, 'product', 1, 'category_id', '产品类别', 'p_box', '', '', 0, 0, 0, 0, 1, 1, '', '', 1, 2, 0),
(25, 'contacts', 1, 'role', '角色', 'box', '', '333333', 0, 0, 1, 1, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''普通人'',2=>''决策人'',3=>''分项决策人'',4=>''商务决策'',5=>''技术决策'',6=>''财务决策'',7=>''使用人'',8=>''意见影响人''))', 2, 1, 0),
(26, 'contacts', 1, 'saltname', '尊称', 'box', '', '333333', 50, 0, 1, 0, 1, 1, '', 'array(''type''=>''radio'',''data''=>array(1=>''先生'',2=>''女士''))', 3, 1, 0),
(27, 'contacts', 1, 'customer_id', '所属客户', 'customer', '', '333333', 50, 0, 0, 1, 1, 1, '', '', 0, 2, 0),
(28, 'contacts', 1, 'post', '职位', 'text', '', '333333', 20, 0, 1, 0, 1, 1, '', '', 4, 2, 0),
(29, 'contacts', 1, 'telephone', '手机', 'mobile', '', '333333', 50, 0, 1, 0, 1, 1, '', '', 5, 2, 1),
(30, 'contacts', 1, 'email', '邮箱', 'email', '', '333333', 50, 0, 1, 0, 1, 1, '', '', 7, 2, 0),
(31, 'contacts', 1, 'qq_no', 'QQ', 'text', '', '333333', 50, 0, 1, 0, 1, 1, '', '', 6, 2, 0),
(32, 'contacts', 1, 'contacts_address', '地址', 'address', '', '333333', 100, 0, 1, 1, 0, 1, '', '', 8, 1, 0),
(33, 'contacts', 1, 'name', '联系人姓名', 'text', '', '333333', 20, 0, 1, 1, 1, 1, '', '', 1, 2, 1),
(34, 'contacts', 1, 'zip_code', '邮编', 'text', '', '333333', 20, 0, 1, 0, 0, 1, '', '', 9, 1, 0),
(35, 'contacts', 1, 'description', '备注', 'textarea', '', '333333', 500, 0, 1, 0, 0, 1, '', '', 10, 1, 0),
(36, 'leads', 1, 'nextstep_time', '下次联系时间', 'datetime', '', '', 0, 0, 0, 0, 1, 1, '', '', 9, 2, 0),
(37, 'leads', 1, 'nextstep', '下次联系内容', 'text', '', '', 0, 0, 0, 0, 1, 1, '', '', 10, 2, 0),
(38, 'leads', 1, 'contacts_name', '联系人姓名', 'text', '', '333333', 0, 0, 1, 1, 1, 1, '', '', 2, 1, 0),
(39, 'leads', 1, 'saltname', '尊称', 'box', '', '333333', 0, 0, 0, 0, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''女士'',2=>''先生''))', 5, 1, 0),
(40, 'leads', 1, 'mobile', '手机', 'mobile', '', '333333', 0, 0, 0, 1, 1, 1, '', '', 6, 1, 0),
(41, 'leads', 1, 'email', '邮箱', 'email', '', '', 0, 0, 0, 1, 0, 1, '', '', 7, 1, 0),
(42, 'leads', 1, 'position', '职位', 'text', '', '', 0, 0, 0, 0, 0, 1, '', '', 4, 1, 0),
(43, 'leads', 1, 'address', '地址', 'address', '', '333333', 0, 0, 0, 0, 0, 1, '', '', 8, 0, 0),
(44, 'leads', 0, 'description', '备注', 'textarea', '', '', 0, 0, 0, 0, 0, 1, '', '', 11, 1, 0),
(45, 'leads', 1, 'name', '公司名', 'text', '', '05330E', 0, 0, 1, 0, 1, 1, '', '', 3, 1, 0),
(46, 'leads', 1, 'source', '来源', 'box', '', '333333', 0, 0, 1, 0, 0, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''网络营销'',2=>''公开媒体'',3=>''合作伙伴'',4=>''员工介绍'',5=>''广告'',6=>''推销电话'',7=>''其他''))', 1, 1, 0);

CREATE TABLE IF NOT EXISTS `mxcrm_file` (
  `file_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '附件主键',
  `name` varchar(50) NOT NULL COMMENT '附件名',
  `role_id` int(10) NOT NULL COMMENT '创建者岗位',
  `size` int(10) NOT NULL COMMENT '文件大小字节',
  `create_date` int(10) NOT NULL COMMENT '创建时间',
  `file_path` varchar(200) NOT NULL COMMENT '文件路径',
  `file_path_thumb` varchar(200) NOT NULL COMMENT '图片缩略图',
  PRIMARY KEY (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='附件表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_finance_category` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL COMMENT '活动编号',
  `name` varchar(255) NOT NULL COMMENT '活动名称',
  `type` tinyint(1) NOT NULL,
  `account_ids` varchar(500) NOT NULL COMMENT '科目ID',
  `remark` varchar(1000) NOT NULL COMMENT '备注',
  `is_pause` tinyint(1) NOT NULL COMMENT '1停用',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='财务活动表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_knowledge` (
  `knowledge_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `category_id` int(10) NOT NULL COMMENT '文章类别',
  `role_id` int(10) NOT NULL COMMENT '发表人岗位',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(10) NOT NULL COMMENT '发表时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `hits` int(10) NOT NULL COMMENT '点击次数',
  `color` varchar(50) NOT NULL,
  PRIMARY KEY (`knowledge_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='存放知识文章信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_knowledge_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章类别id',
  `parent_id` int(11) NOT NULL COMMENT '父类别id',
  `name` varchar(30) NOT NULL COMMENT '类别名称',
  `description` varchar(100) NOT NULL COMMENT '备注',
  `to_department` varchar(200) NOT NULL COMMENT '权限部门id',
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='知识文章分类信息表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_leads` (
  `leads_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '线索主键',
  `owner_role_id` int(10) NOT NULL COMMENT '拥有者岗位',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者岗位',
  `name` varchar(255) NOT NULL,
  `position` varchar(20) NOT NULL COMMENT '职位',
  `contacts_name` varchar(255) NOT NULL,
  `saltname` varchar(255) NOT NULL DEFAULT '',
  `mobile` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL COMMENT '电子邮箱',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人的岗位id',
  `delete_time` int(10) NOT NULL,
  `is_transformed` int(1) NOT NULL COMMENT '是否转换',
  `transform_role_id` int(10) NOT NULL COMMENT '转换者',
  `contacts_id` int(10) NOT NULL COMMENT '转换成联系人',
  `customer_id` int(10) NOT NULL COMMENT '转换成的客户',
  `business_id` int(10) NOT NULL COMMENT '转换成的商机',
  `nextstep` varchar(50) NOT NULL COMMENT '下一次联系',
  `nextstep_time` int(10) NOT NULL COMMENT '联系时间',
  `have_time` int(10) NOT NULL COMMENT '最后一次领取时间',
  `first_time` int(10) NOT NULL COMMENT '第一次跟进时间',
  `address` varchar(500) NOT NULL,
  `source` varchar(500) NOT NULL COMMENT '线索来源',
  PRIMARY KEY (`leads_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='线索表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_leads_data` (
  `leads_id` int(10) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`leads_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_leads_record` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `leads_id` int(10) NOT NULL,
  `owner_role_id` int(10) NOT NULL,
  `start_time` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_log` (
  `log_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '日志id',
  `role_id` int(11) NOT NULL COMMENT '创建者岗位',
  `category_id` int(10) NOT NULL,
  `sign` int(1) NOT NULL DEFAULT '0' COMMENT '1关联签到',
  `create_date` int(10) NOT NULL COMMENT '创建时间',
  `update_date` int(10) NOT NULL COMMENT '更新时间',
  `subject` varchar(200) NOT NULL COMMENT '主题',
  `content` text NOT NULL COMMENT '内容',
  `comment_id` int(10) NOT NULL COMMENT '评论id',
  `about_roles` varchar(200) NOT NULL COMMENT '新增相关人',
  `about_roles_name` varchar(500) NOT NULL COMMENT '新增相关人姓名',
  `status` tinyint(1) NOT NULL COMMENT '0未阅1已阅2已点评',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日志表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_log_category` (
  `category_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `name` varchar(200) NOT NULL COMMENT '分类名',
  `order_id` int(10) NOT NULL COMMENT '顺序id',
  `description` varchar(500) NOT NULL COMMENT '描述',
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='日志类型表' AUTO_INCREMENT=5 ;

INSERT INTO `mxcrm_log_category` (`category_id`, `name`, `order_id`, `description`) VALUES
(1, '模块日志', 4, '其他模块的相关日志'),
(2, '月报', 3, '每月工作总结'),
(3, '周报', 2, '每周工作总结'),
(4, '日报', 1, '每日工作总结');

CREATE TABLE IF NOT EXISTS `mxcrm_log_talk` (
  `talk_id` int(10) NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) NOT NULL COMMENT '组内分标示',
  `log_id` int(10) NOT NULL,
  `send_role_id` int(10) NOT NULL COMMENT '发送者id',
  `receive_role_id` int(10) NOT NULL COMMENT '接收者id',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(10) NOT NULL,
  `g_mark` varchar(50) NOT NULL COMMENT '标示',
  PRIMARY KEY (`talk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日志评论回复表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_member` (
  `member_id` int(10) NOT NULL AUTO_INCREMENT,
  `telephone` varchar(20) NOT NULL COMMENT '手机号',
  `name` varchar(200) NOT NULL COMMENT '客户名',
  `password` varchar(200) NOT NULL COMMENT '密码',
  `salt` varchar(4) NOT NULL COMMENT '安全符',
  `honorific` varchar(50) NOT NULL COMMENT '尊称',
  `birth` date NOT NULL COMMENT '出生日期',
  `create_time` int(10) NOT NULL COMMENT '创建（注册）日期',
  `update_time` int(10) NOT NULL COMMENT '修改日期',
  `lostpw_time` int(10) NOT NULL COMMENT '密码找回时间',
  `reg_ip` varchar(50) NOT NULL COMMENT '注册IP',
  `address_id` int(10) NOT NULL COMMENT '上次（默认）送货地址',
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='线上客户表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_member_address` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `member_id` int(10) NOT NULL COMMENT '线上客户ID',
  `address` varchar(2000) NOT NULL COMMENT '地址',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='线上客户地址表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_message` (
  `message_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `to_role_id` int(11) unsigned NOT NULL,
  `from_role_id` int(11) unsigned NOT NULL,
  `content` text NOT NULL,
  `read_time` int(11) unsigned NOT NULL,
  `send_time` int(11) unsigned NOT NULL,
  `status` int(1) NOT NULL,
  `file_id` int(10) NOT NULL COMMENT '附件ID',
  `is_mark` TINYINT(1) NOT NULL COMMENT '1已标记',
  PRIMARY KEY (`message_id`),
  KEY `to_role_id` (`to_role_id`,`from_role_id`,`read_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_note` (
  `note_id` int(10) NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL,
  `content` varchar(1000) NOT NULL COMMENT '内容',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`note_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='便笺表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_order` (
  `order_id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT '编号',
  `prefixion` varchar(50) NOT NULL COMMENT '前缀',
  `price` decimal(10,2) NOT NULL COMMENT '金额',
  `payment_state` tinyint(1) NOT NULL COMMENT '0未支付1已支付（线上）2已支付（线下）3已退还',
  `check_state` tinyint(1) NOT NULL COMMENT '0未接单1已接单2已取消',
  `status` int(10) NOT NULL COMMENT '订单阶段',
  `member_id` int(10) NOT NULL COMMENT '客户ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `check_role_id` int(10) NOT NULL COMMENT '审核人ID',
  `remark` varchar(1000) NOT NULL COMMENT '备注',
  `address` varchar(1000) NOT NULL COMMENT '收货地址',
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商城订单表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_payables` (
  `payables_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '应付款id',
  `customer_id` int(10) NOT NULL COMMENT '客户id',
  `receiver` varchar(100) NOT NULL COMMENT '收款单位',
  `contract_id` int(10) DEFAULT NULL COMMENT '合同id',
  `purchase_id` int(10) DEFAULT NULL COMMENT '采购单id',
  `sales_id` int(10) DEFAULT NULL COMMENT '销售单',
  `sales_code` varchar(20) NOT NULL COMMENT '销售单序列号',
  `purchase_code` varchar(20) NOT NULL COMMENT '采购单序列号',
  `type` int(1) NOT NULL COMMENT '0：普通  1：采购  2：销售退货',
  `name` varchar(500) NOT NULL COMMENT '应付款名',
  `price` decimal(10,2) NOT NULL COMMENT '应付金额',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者id',
  `owner_role_id` int(10) NOT NULL,
  `description` text NOT NULL COMMENT '描述',
  `pay_time` int(10) NOT NULL COMMENT '付款时间',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `status` int(2) NOT NULL COMMENT '状态0未收1部分收2已收',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `is_deleted` int(1) NOT NULL DEFAULT '0' COMMENT ' 是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  PRIMARY KEY (`payables_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='应付款表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_paymentorder` (
  `paymentorder_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '付款单id',
  `name` varchar(500) NOT NULL COMMENT '付款单主题',
  `money` decimal(10,2) NOT NULL COMMENT '付款金额',
  `payables_id` int(10) NOT NULL COMMENT '应付款id',
  `type` int(10) NOT NULL COMMENT '应付款，付款单类别',
  `description` text NOT NULL COMMENT '描述',
  `pay_time` int(10) NOT NULL COMMENT '付款时间',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者id',
  `owner_role_id` int(10) NOT NULL COMMENT '负责人',
  `status` int(2) NOT NULL DEFAULT '0' COMMENT '状态0待审1审核通过2审核失败',
  `update_time` int(10) NOT NULL COMMENT '审核时间',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `is_deleted` int(1) NOT NULL DEFAULT '0' COMMENT ' 是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `receipt_account` varchar(50) DEFAULT NULL COMMENT '付款账户',
  `examine_role_id` int(11) NOT NULL COMMENT '审核人',
  `check_des` varchar(200) NOT NULL COMMENT '审核备注',
  `bank_account_id` int(10) NOT NULL,
  `bank_name` int(100) NOT NULL,
  `bank_acount` int(100) NOT NULL,
  `company` int(100) NOT NULL,
  PRIMARY KEY (`paymentorder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='付款单' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_permission` (
  `permission_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '权限id',
  `role_id` int(10) NOT NULL COMMENT '岗位id',
  `position_id` int(10) NOT NULL COMMENT '岗位组id',
  `url` varchar(50) NOT NULL COMMENT '对应模块操作',
  `description` varchar(200) NOT NULL COMMENT '权限备注',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1自己和下属2所有人3自己4部门所有人',
  PRIMARY KEY (`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表用来存放权限信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_position` (
  `position_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '岗位id',
  `parent_id` int(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `department_id` int(10) NOT NULL,
  `description` varchar(200) NOT NULL COMMENT '描述',
  PRIMARY KEY (`position_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='岗位表控制权限' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_position` (`position_id`, `parent_id`, `name`, `department_id`, `description`) VALUES
(1, 0, 'CEO', 1, '');

CREATE TABLE IF NOT EXISTS `mxcrm_praise` (
  `praise_id` int(10) NOT NULL AUTO_INCREMENT,
  `log_id` int(10) NOT NULL COMMENT '日志id',
  `role_id` int(10) NOT NULL COMMENT '赞的人role_id',
  PRIMARY KEY (`praise_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_product` (
  `product_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '产品id',
  `category_id` int(11) NOT NULL COMMENT '产品类别的id',
  `name` varchar(200) NOT NULL DEFAULT '',
  `creator_role_id` int(10) NOT NULL COMMENT '产品信息添加者',
  `cost_price` int(10) NOT NULL DEFAULT '0' COMMENT '成本价',
  `suggested_price` float(10,2) NOT NULL COMMENT '建议售价',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `standard` varchar(200) NOT NULL DEFAULT '' COMMENT '规格',
  `points` int(10) NOT NULL COMMENT '产品积分',
  `description` varchar(2000) NOT NULL DEFAULT '' COMMENT '备注',
  `is_deleted` tinyint(1) NOT NULL COMMENT '1删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除人ID',
  `product_num` varchar(255) NOT NULL DEFAULT '' COMMENT '产品编号',
  `is_shop` tinyint(1) NOT NULL COMMENT '1商城展示',
  `sketch` varchar(500) NOT NULL COMMENT '商品描述',
  PRIMARY KEY (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_product_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '产品类别id',
  `parent_id` int(11) NOT NULL COMMENT '父类别id',
  `name` varchar(30) NOT NULL COMMENT '类别名称',
  `description` varchar(100) NOT NULL COMMENT '备注',
  `is_shop` tinyint(1) NOT NULL COMMENT '1展示',
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_product_category` (`category_id`, `parent_id`, `name`, `description`) VALUES
(1, 0, '默认', '');

CREATE TABLE IF NOT EXISTS `mxcrm_product_data` (
  `product_id` int(10) NOT NULL COMMENT '主键',
  `description` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='产品信息附表';

CREATE TABLE IF NOT EXISTS `mxcrm_product_images` (
  `images_id` int(10) NOT NULL AUTO_INCREMENT,
  `product_id` int(10) NOT NULL COMMENT '关联产品id',
  `is_main` int(1) NOT NULL COMMENT '0：副图  1：主图',
  `name` varchar(500) NOT NULL COMMENT '源文件名',
  `save_name` varchar(500) NOT NULL COMMENT '保存至服务器的文件名',
  `size` varchar(500) NOT NULL COMMENT 'KB',
  `path` varchar(500) NOT NULL COMMENT '路径',
  `thumb_path` varchar(255) NOT NULL COMMENT '缩略图',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `listorder` int(10) NOT NULL COMMENT '排序',
  PRIMARY KEY (`images_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='产品图库' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_receivables` (
  `receivables_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '应收款id',
  `customer_id` int(10) NOT NULL COMMENT '客户id',
  `payer` varchar(500) NOT NULL COMMENT '付款单位',
  `contract_id` int(10) DEFAULT NULL COMMENT '合同id',
  `sales_id` int(10) DEFAULT NULL COMMENT '销售单',
  `purchase_id` int(10) DEFAULT NULL COMMENT '采购单id',
  `sales_code` varchar(20) NOT NULL COMMENT '销售单序列号',
  `purchase_code` varchar(20) NOT NULL COMMENT '采购单序列号',
  `type` int(10) NOT NULL COMMENT '0：普通  1：销售  2：采购退货',
  `name` varchar(500) NOT NULL COMMENT '应收款名',
  `price` decimal(10,2) NOT NULL COMMENT '应收金额',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者id',
  `owner_role_id` int(10) NOT NULL,
  `description` text NOT NULL COMMENT '描述',
  `pay_time` int(10) NOT NULL COMMENT '收款时间',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `status` int(2) NOT NULL COMMENT '状态0未收1部分收2已收',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `is_deleted` int(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `prefixion` varchar(50) NOT NULL COMMENT '表前缀',
  PRIMARY KEY (`receivables_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='应收款表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_receivingorder` (
  `receivingorder_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '收款单id',
  `name` varchar(500) NOT NULL COMMENT '收款单主题',
  `money` decimal(10,2) NOT NULL COMMENT '收款金额',
  `receivables_id` int(10) NOT NULL COMMENT '应收款id',
  `type` tinyint(1) NOT NULL COMMENT '应收款类别',
  `description` text NOT NULL COMMENT '描述',
  `pay_time` int(10) NOT NULL COMMENT '付款时间',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者id',
  `owner_role_id` int(10) NOT NULL COMMENT '负责人',
  `status` int(2) NOT NULL DEFAULT '0' COMMENT '状态0待审1审核通过2审核失败',
  `update_time` int(10) NOT NULL COMMENT '审核时间',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `is_deleted` int(1) NOT NULL DEFAULT '0' COMMENT ' 是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `contract_id` int(10) NOT NULL COMMENT '合同ID',
  `invoice` int(1) NOT NULL COMMENT '1开发票 2不开发票',
  `invoice_num` varchar(100) NOT NULL COMMENT '发票号',
  `invoice_money` decimal(9,2) NOT NULL COMMENT '发票金额',
  `check_des` varchar(200) NOT NULL COMMENT '审核备注',
  `bank_account_id` int(10) NOT NULL COMMENT '账户id',
  `receipt_account` varchar(500) DEFAULT NULL COMMENT '收款账户',
  `examine_role_id` int(11) NOT NULL COMMENT '审核人',
  `receipt_bank` varchar(500) NOT NULL COMMENT '开户行',
  `company` varchar(100) NOT NULL,
  PRIMARY KEY (`receivingorder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='收款单' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_remind` (
  `remind_id` int(10) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL COMMENT '相关模块',
  `module_id` int(10) NOT NULL COMMENT '相关模块ID',
  `remind_time` int(10) NOT NULL COMMENT '提醒时间',
  `content` varchar(500) NOT NULL COMMENT '提醒内容',
  `create_role_id` int(10) NOT NULL COMMENT '提醒人ID',
  `is_remind` tinyint(1) NOT NULL COMMENT '1已提醒',
  PRIMARY KEY (`remind_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='相关提醒表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_role` (
  `role_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '岗位id',
  `position_id` int(10) NOT NULL COMMENT '岗位组名',
  `user_id` int(10) NOT NULL COMMENT '员工id',
  PRIMARY KEY (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表存放用户岗位信息' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_role` (`role_id`, `position_id`,`user_id`) VALUES
(1, 1, 1);

CREATE TABLE IF NOT EXISTS `mxcrm_role_department` (
  `department_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '部门id',
  `parent_id` int(10) NOT NULL COMMENT '父类部门id',
  `name` varchar(50) NOT NULL COMMENT '部门名',
  `charge_position` int(10) NOT NULL COMMENT '部门最高级别岗位',
  `description` varchar(200) NOT NULL COMMENT '部门描述',
  PRIMARY KEY (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表存放部门信息' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_role_department` (`department_id`, `parent_id`, `name`, `description`) VALUES
(1, 0, '办公室', '公司文档管理、财务管理');

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_contacts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(10) NOT NULL,
  `contacts_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_contract` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(10) NOT NULL COMMENT '商机id',
  `contract_id` int(10) NOT NULL COMMENT '合同id',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商机合同关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_customer` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(10) NOT NULL,
  `file_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(10) NOT NULL,
  `log_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商机和日志id对应关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `ori_price` decimal(12,2) NOT NULL COMMENT '原价',
  `discount_rate` decimal(5,2) NOT NULL COMMENT '单个折扣',
  `unit_price` decimal(12,2) NOT NULL COMMENT '销售价格',
  `sales_price` float(10,2) NOT NULL COMMENT '成交价',
  `estimate_price` float(10,2) NOT NULL COMMENT '报价',
  `amount` int(10) NOT NULL COMMENT '产品交易数量',
  `unit` varchar(50) NOT NULL DEFAULT '' COMMENT '单位',
  `subtotal` decimal(14,2) NOT NULL COMMENT '小计',
  `description` varchar(200) NOT NULL COMMENT '产品交易备注',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_business_status` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '关系主键',
  `business_id` int(10) NOT NULL COMMENT '商机id',
  `gain_rate` int(3) NOT NULL,
  `status_id` int(10) NOT NULL COMMENT '状态id',
  `description` text NOT NULL COMMENT '阶段描述',
  `due_date` int(10) NOT NULL COMMENT '预计成交日期',
  `owner_role_id` int(10) NOT NULL COMMENT '负责人id',
  `update_time` int(10) NOT NULL COMMENT '推进时间',
  `update_role_id` int(10) NOT NULL COMMENT '推进人',
  `total_price` float(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商机状态阶段表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_contacts_customer` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contacts_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_contract_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) NOT NULL COMMENT '合同id',
  `file_id` int(10) NOT NULL COMMENT '文件id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同文件关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_contract_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `sales_price` float(10,2) NOT NULL,
  `estimate_price` float(10,2) NOT NULL,
  `amount` int(10) NOT NULL,
  `description` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_contract_sales` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) NOT NULL COMMENT '合同id',
  `sales_id` int(10) NOT NULL COMMENT '销售单id',
  `distribution_id` int(10) NOT NULL COMMENT '待配货ID',
  `sales_type` int(10) NOT NULL COMMENT '0销售1退货2待配货',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同与销售单关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_customer_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `file_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_customer_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `log_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_file_finance` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file_id` int(10) NOT NULL,
  `finance_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_file_leads` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file_id` int(10) NOT NULL,
  `leads_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文件和日志对应关系' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_file_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file_id` int(10) NOT NULL,
  `log_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文件和日志对应关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_file_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_finance_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `finance_id` int(10) NOT NULL,
  `log_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_leads_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `leads_id` int(10) NOT NULL,
  `log_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_log_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `log_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_member_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `member_id` int(10) NOT NULL COMMENT '客户ID',
  `file_id` int(10) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='线上客户附件关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_member_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `member_id` int(10) NOT NULL COMMENT '线上客户ID',
  `log_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='线上客户日志表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_order_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `order_id` int(10) NOT NULL COMMENT '订单ID',
  `log_id` int(10) NOT NULL COMMENT '日志ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='订单进度日志表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_order_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `order_id` int(10) NOT NULL COMMENT '订单ID',
  `product_id` int(10) NOT NULL COMMENT '产品ID',
  `unit_price` decimal(10,2) NOT NULL COMMENT '单价',
  `ori_price` decimal(10,2) NOT NULL COMMENT '建议售价',
  `amount` int(10) NOT NULL COMMENT '数量',
  `unit` varchar(50) NOT NULL COMMENT '单位',
  `subtotal` decimal(10,2) NOT NULL COMMENT '小计',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='订单产品关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sales` (
  `sales_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '销售单id',
  `customer_id` int(10) NOT NULL COMMENT '客户id',
  `creator_role_id` int(10) NOT NULL COMMENT '制单人',
  `sn_code` varchar(20) NOT NULL COMMENT '销售单序列号',
  `subject` varchar(100) NOT NULL COMMENT '主题',
  `prime_price` decimal(9,2) NOT NULL COMMENT '销售单整体价格未减折扣额时价格',
  `final_discount_rate` decimal(10,2) NOT NULL COMMENT '折扣率',
  `sales_price` decimal(9,2) NOT NULL COMMENT '折扣后销售单实际应付金额',
  `total_amount` int(10) NOT NULL COMMENT '总数量',
  `type` int(1) NOT NULL COMMENT '0：销售   1：退货',
  `status` int(10) NOT NULL COMMENT '97：未出库 98： 已出库 99：未入库   100：已入库',
  `is_checked` int(1) NOT NULL COMMENT '0：未审核   1：审核',
  `shipping_address` varchar(300) DEFAULT NULL COMMENT '发货地址',
  `discount_price` decimal(9,2) NOT NULL COMMENT '折扣额',
  `description` varchar(500) NOT NULL,
  `create_time` int(10) NOT NULL,
  `sales_time` int(10) NOT NULL COMMENT '销售日期',
  `outof_time` int(10) NOT NULL COMMENT '出库时间',
  `logistics_number` varchar(100) NOT NULL COMMENT '物流单号',
  `receiving_people` varchar(50) NOT NULL COMMENT '收件人',
  `receiving_phone` varchar(20) NOT NULL COMMENT '收件电话',
  `check_role_id` int(11) NOT NULL COMMENT '审核人',
  PRIMARY KEY (`sales_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='销售表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sales_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_id` int(11) NOT NULL COMMENT '销售单ID',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `content` varchar(200) NOT NULL COMMENT '物流详情',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sales_product` (
  `sales_product_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '销售单商品id',
  `sales_id` int(10) NOT NULL COMMENT '销售单id',
  `product_id` int(10) NOT NULL COMMENT '商品id',
  `warehouse_id` int(10) NOT NULL COMMENT '仓库id',
  `amount` int(10) NOT NULL COMMENT '数量',
  `ori_price` decimal(10,2) NOT NULL COMMENT '建议售价',
  `unit_price` decimal(9,2) NOT NULL COMMENT '销售时商品单价',
  `unit` varchar(50) NOT NULL COMMENT '商品单位',
  `cost_price` decimal(10,2) NOT NULL COMMENT '销售时成本价',
  `discount_rate` decimal(10,2) NOT NULL COMMENT '折扣率',
  `tax_rate` int(10) NOT NULL COMMENT '税率',
  `description` varchar(500) NOT NULL COMMENT '描述',
  `subtotal` decimal(10,2) NOT NULL COMMENT '小计',
  PRIMARY KEY (`sales_product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='销售单商品表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sign` (
  `sign_id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL,
  `x` float(10,6) NOT NULL COMMENT 'x坐标',
  `y` float(10,6) NOT NULL COMMENT 'y坐标',
  `address` varchar(50) NOT NULL,
  `log` varchar(100) NOT NULL,
  `create_time` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `log_id` int(10) NOT NULL COMMENT '关联沟通日志',
  PRIMARY KEY (`sign_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sign_img` (
  `img_id` int(10) NOT NULL AUTO_INCREMENT,
  `sign_id` int(10) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '图片上传时名字',
  `save_name` varchar(100) NOT NULL COMMENT '图片保存名',
  `path` varchar(200) NOT NULL,
  `create_time` int(10) NOT NULL,
  PRIMARY KEY (`img_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sms_record` (
  `sms_record_id` int(10) NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL COMMENT '发件人',
  `telephone` text NOT NULL COMMENT '发送号码',
  `content` text NOT NULL COMMENT '发送内容',
  `sendtime` int(10) NOT NULL COMMENT '发送时间',
  `phone_counts` int(11) NOT NULL COMMENT '短信数',
  PRIMARY KEY (`sms_record_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短信发送记录表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_sms_template` (
  `template_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `subject` varchar(200) NOT NULL COMMENT '主题',
  `content` varchar(500) NOT NULL COMMENT '内容',
  `order_id` int(4) NOT NULL COMMENT '顺序id',
  PRIMARY KEY (`template_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短信模板' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_sms_template` (`template_id`, `subject`, `content`, `order_id`) VALUES
(1, '默认模板', '有一个特别的日子，鲜花都为你展现；有一个特殊的日期，阳光都为你温暖；有一个美好的时刻，百灵都为你欢颜；有一个难忘的今天，亲朋都为你祝愿；那就是今天是你的生日，祝你幸福安康顺意连年！', 1);

CREATE TABLE IF NOT EXISTS `mxcrm_stock` (
  `stock_id` int(10) NOT NULL AUTO_INCREMENT,
  `product_id` int(10) NOT NULL COMMENT '商品id',
  `warehouse_id` int(10) NOT NULL COMMENT '仓库id',
  `amounts` int(10) NOT NULL COMMENT '库存数量',
  `last_change_time` int(10) NOT NULL COMMENT '库存上次变动时间',
  PRIMARY KEY (`stock_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='库存' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_top` (
  `top_id` int(10) NOT NULL AUTO_INCREMENT,
  `module_id` int(10) NOT NULL COMMENT '相关模块ID',
  `set_top` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1置顶',
  `top_time` int(10) NOT NULL COMMENT '置顶时间',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `module` varchar(50) NOT NULL DEFAULT 'business' COMMENT '置顶模块',
  PRIMARY KEY (`top_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='置顶表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_user` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `role_id` int(10) NOT NULL COMMENT '当前使用岗位',
  `category_id` int(11) NOT NULL COMMENT '用户类别',
  `status` int(1) NOT NULL COMMENT '1启用2停用3未激活',
  `type` int(4) NOT NULL COMMENT '员工类型',
  `name` varchar(20) NOT NULL COMMENT '用户名',
  `prefixion` varchar(200) NOT NULL COMMENT '前缀',
  `number` varchar(200) NOT NULL COMMENT '编号',
  `full_name` varchar(255) NOT NULL COMMENT '用户姓名',
  `img` varchar(100) NOT NULL COMMENT '头像',
  `thumb_path` varchar(255) NOT NULL COMMENT '头像缩略图',
  `password` varchar(32) NOT NULL COMMENT '用户密码',
  `salt` varchar(4) NOT NULL COMMENT '安全符',
  `sex` int(1) NOT NULL COMMENT '用户性别1男2女',
  `email` varchar(30) NOT NULL COMMENT '用户邮箱',
  `telephone` varchar(20) NOT NULL COMMENT '用户的电话',
  `address` varchar(100) NOT NULL COMMENT '用户的联系地址',
  `hometown` varchar(255) NOT NULL COMMENT '家乡',
  `birthday` date NOT NULL COMMENT '出生日期',
  `entry` date NOT NULL COMMENT '入职日期',
  `introduce` varchar(1000) NOT NULL COMMENT '自我介绍',
  `office_tel` varchar(30) NOT NULL COMMENT '办公电话',
  `qq` varchar(255) NOT NULL COMMENT 'QQ/MSN',
  `navigation` varchar(1000) NOT NULL COMMENT '用户自定义导航菜单',
  `simple_menu` varchar(1000) NOT NULL COMMENT '自定义快捷添加菜单',
  `dashboard` text NOT NULL COMMENT '个人面板',
  `reg_ip` varchar(15) NOT NULL COMMENT '注册时的ip',
  `reg_time` int(10) NOT NULL COMMENT '用户的注册时间',
  `last_login_time` int(10) NOT NULL COMMENT '用户最后一次登录的时间',
  `lostpw_time` int(10) NOT NULL COMMENT '用户申请找回密码的时间',
  `weixinid` varchar(150) NOT NULL,
  `last_read_time` varchar(500) NOT NULL COMMENT '手机端客户，商机等最后阅读时间',
  `token` varchar(32) NOT NULL COMMENT '会话机制',
  `token_time` int(11) NOT NULL COMMENT '会话时间',
  `developer_token` varchar(100) NOT NULL COMMENT '推送token',
  `is_receivables` int(1) NOT NULL COMMENT '合同审核是否生成应收款',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='本表用来存放用户的相关基本信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_user_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '类别id',
  `name` varchar(20) NOT NULL COMMENT '类别的名字',
  `description` varchar(100) NOT NULL COMMENT '备注',
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='本表存放用户类别信息' AUTO_INCREMENT=3 ;

INSERT INTO `mxcrm_user_category` (`category_id`, `name`, `description`) VALUES
(1, '管理员', ''),
(2, '员工', '');

CREATE TABLE IF NOT EXISTS `mxcrm_user_smtp` (
  `smtp_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '发件箱名称',
  `user_id` int(10) NOT NULL COMMENT '用户id',
  `settinginfo` text NOT NULL COMMENT 'smtp设置',
  PRIMARY KEY (`smtp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='smtp设置表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_voucher_account` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL COMMENT '科目编码',
  `name` varchar(255) NOT NULL COMMENT '科目名称',
  `category` int(10) NOT NULL COMMENT '科目类别',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '借贷方向（1借2贷）',
  `parent_id` int(10) NOT NULL COMMENT '父类ID',
  `is_pause` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0禁用1启用',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `create_role_id` int(10) NOT NULL COMMENT '创建人',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='财务科目' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_warehouse` (
  `warehouse_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '仓库id',
  `name` varchar(200) NOT NULL COMMENT '仓库名',
  `description` varchar(500) NOT NULL COMMENT '描述',
  PRIMARY KEY (`warehouse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='仓库表' AUTO_INCREMENT= 3;

INSERT INTO `mxcrm_warehouse` (`warehouse_id`, `name`, `description`) VALUES
(1, '1号仓库', '存储固体货物'),
(2, '2号仓库', '存储液体货物');

CREATE TABLE IF NOT EXISTS `mxcrm_workrule` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL COMMENT '年份',
  `sdate` int(10) NOT NULL COMMENT '开始时间',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1休息2工作日',
  `status` tinyint(1) NOT NULL COMMENT '1自定义时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='工作日配置' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_workrule_config` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL COMMENT '年份',
  `value` varchar(50) NOT NULL COMMENT '配置工作日',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='工作日配置表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_event` (
  `event_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '活动id',
  `owner_role_id` int(10) NOT NULL COMMENT '所有人岗位',
  `subject` varchar(50) NOT NULL COMMENT '主题',
  `start_date` int(10) NOT NULL COMMENT '开始时间',
  `end_date` int(10) NOT NULL COMMENT '结束时间',
  `venue` varchar(100) NOT NULL COMMENT '活动地点',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者id',
  `create_date` int(10) NOT NULL COMMENT '创建时间',
  `update_date` int(10) NOT NULL COMMENT '修改时间',
  `send_email` INT( 1 ) NOT NULL COMMENT  '发送通知邮件1不发送0',
  `recurring` int(1) NOT NULL COMMENT '重复1 不重复0',
  `description` text NOT NULL COMMENT '描述',
  `isclose` int(1) NOT NULL COMMENT '是否关闭0开启1关闭',
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `color` VARCHAR( 50 ) NOT NULL COMMENT  '颜色',
  `module` VARCHAR( 50 ) NOT NULL COMMENT  '相关模块',
  `module_id` INT( 10 ) NOT NULL COMMENT  '相关模块ID',
  PRIMARY KEY (`event_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='活动信息表' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mxcrm_task` (
  `task_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '任务id',
  `type_id` int(10) NOT NULL COMMENT '分类id',
  `owner_role_id` varchar(200) NOT NULL COMMENT '任务所有者岗位',
  `about_roles` varchar(200) NOT NULL COMMENT '任务相关人',
  `subject` varchar(100) NOT NULL COMMENT '任务主题',
  `due_date` int(10) NOT NULL COMMENT '任务结束时间',
  `status` varchar(20) NOT NULL COMMENT '任务状态',
  `priority` varchar(10) NOT NULL COMMENT '优先级',
  `send_email` varchar(50) NOT NULL COMMENT '是否发送通知邮件  1发送0不发送',
  `description` text NOT NULL COMMENT '描述',
  `creator_role_id` int(10) NOT NULL COMMENT '创建者岗位',
  `create_date` int(10) NOT NULL COMMENT '创建时间',
  `update_date` int(10) NOT NULL COMMENT '修改时间',
  `isclose` int(1) NOT NULL COMMENT '是否关闭',
  `is_deleted` int(1) NOT NULL COMMENT '是否删除',
  `delete_role_id` int(10) NOT NULL COMMENT '删除人',
  `delete_time` int(10) NOT NULL COMMENT '删除时间',
  `finish_date` int(10) NOT NULL COMMENT '完成时间',
  `order_id` int(10) NOT NULL COMMENT '排序ID',
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='任务信息表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_task_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL COMMENT '分类名',
  `role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `order_id` int(10) NOT NULL COMMENT '排序ID',
  `is_deleted` TINYINT( 1 ) NOT NULL COMMENT  '1删除',
  `del_role_id` INT( 10 ) NOT NULL COMMENT  '删除人ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='任务列表分类' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_task_sub` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `task_id` int(10) NOT NULL COMMENT '主任务ID',
  `content` varchar(1000) NOT NULL COMMENT '内容',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int( 10 ) NOT NULL COMMENT '修改时间',
  `is_done` tinyint(1) NOT NULL COMMENT '1完成',
  `done_role_id` int(10) NOT NULL COMMENT '完成人ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='子任务表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_task_talk` (
  `talk_id` int(10) NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) NOT NULL COMMENT '组内分标示',
  `task_id` int(10) NOT NULL,
  `send_role_id` int(10) NOT NULL COMMENT '发送者id',
  `receive_role_id` int(10) NOT NULL COMMENT '接收者id',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(10) NOT NULL,
  `g_mark` varchar(50) NOT NULL COMMENT '标示',
  PRIMARY KEY (`talk_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='任务评论回复表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_task_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `task_id` int(10) NOT NULL COMMENT '任务ID',
  `file_id` int(10) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='任务附件关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_task_action` (
  `action_id` int(10) NOT NULL AUTO_INCREMENT,
  `task_id` int(10) NOT NULL COMMENT '任务ID',
  `role_id` int(10) NOT NULL COMMENT '操作人ID',
  `create_date` date NOT NULL COMMENT '操作时间',
  `create_time` int(10) NOT NULL COMMENT '操作时间',
  `type` int(10) NOT NULL COMMENT '操作类型',
  `content` varchar(1000) NOT NULL COMMENT '操作内容',
  `about_role_id` VARCHAR( 500 ) NOT NULL COMMENT '分配人ID',
  PRIMARY KEY (`action_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='任务活动表' AUTO_INCREMENT=1 ;

ALTER TABLE  `mxcrm_message` ADD  `is_notifi` TINYINT( 1 ) NOT NULL COMMENT  '1已提醒（桌面）';

CREATE TABLE IF NOT EXISTS `mxcrm_scene` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) NOT NULL COMMENT '模块',
  `name` varchar(255) NOT NULL COMMENT '场景名称',
  `role_id` int(10) NOT NULL,
  `data` text NOT NULL COMMENT '属性值',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `is_hide` INT( 1 ) NOT NULL COMMENT  '1隐藏',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='自定义场景' AUTO_INCREMENT=1 ;


ALTER TABLE  `mxcrm_examine` CHANGE  `examine_status`  `examine_status` INT( 1 ) NOT NULL COMMENT  '状态（0待审、1审批中、2通过、3失败）';

CREATE TABLE IF NOT EXISTS `mxcrm_log_status` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '名称',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='沟通日志类型' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_log_status` (`id`, `name`, `create_role_id`, `create_time`, `update_time`) VALUES
(1, '电话', 1, 1502792328, 1502792328),
(2, '发邮件', 1, 1502852220, 1502852220),
(3, '发短信', 1, 1502852228, 1502852228),
(4, '见面拜访', 1, 1502852239, 1502852239);

CREATE TABLE IF NOT EXISTS `mxcrm_log_reply` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `content` VARCHAR( 1000 ) NOT NULL COMMENT  '内容',
  `status_id` int(10) NOT NULL COMMENT '类型ID',
  `type` tinyint(1) NOT NULL COMMENT '1系统2个人',
  `role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='沟通日志自定义回复表' AUTO_INCREMENT=1 ;

ALTER TABLE  `mxcrm_product` CHANGE  `cost_price`  `cost_price` DECIMAL( 10, 2 ) NOT NULL DEFAULT  '0' COMMENT  '成本价';

ALTER TABLE  `mxcrm_product` CHANGE  `suggested_price`  `suggested_price` DECIMAL( 10, 2 ) NOT NULL COMMENT  '建议售价';

ALTER TABLE  `mxcrm_customer` ADD  `nextstep_time` INT( 10 ) NOT NULL COMMENT  '下次联系时间' AFTER  `get_time`;

ALTER TABLE  `mxcrm_log` ADD  `nextstep_time` INT( 10 ) NOT NULL COMMENT  '下次联系时间' AFTER  `update_date`;

ALTER TABLE  `mxcrm_log` ADD  `status_id` INT( 10 ) NOT NULL COMMENT  '跟进类型' AFTER  `category_id`;

ALTER TABLE  `mxcrm_scene` ADD  `order_id` INT( 10 ) NOT NULL COMMENT  '排序ID' AFTER  `role_id`;

ALTER TABLE  `mxcrm_scene` ADD  `type` TINYINT( 1 ) NOT NULL COMMENT  '1系统0自定义',
ADD  `by` VARCHAR( 50 ) NOT NULL COMMENT  '系统参数';

INSERT INTO `mxcrm_scene` (`id`, `module`, `name`, `role_id`, `order_id`, `data`, `create_time`, `update_time`, `is_hide`, `type`, `by`) VALUES
(1, 'customer', '我的客户', 0, 0, '', 0, 0, 0, 1, 'me'),
(2, 'customer', '下属客户', 0, 1, '', 0, 0, 0, 1, 'sub'),
(3, 'customer', '全部客户', 0, 3, '', 0, 0, 0, 1, 'all');

CREATE TABLE IF NOT EXISTS `mxcrm_scene_default` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL COMMENT '模块',
  `role_id` int(10) NOT NULL,
  `scene_id` int(10) NOT NULL COMMENT '场景ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='员工默认场景关系表' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_fields` (`model`, `is_main`, `field`, `name`, `form_type`, `default_value`, `color`, `max_length`, `is_unique`, `is_null`, `is_validate`, `in_index`, `in_add`, `input_tips`, `setting`, `order_id`, `operating`) VALUES
('customer', 1, 'nextstep_time', '下次联系时间', 'datetime', '', '333333', 200, 0, 1, 0, 0, 1, '', '', 0, 2);

ALTER TABLE  `mxcrm_fields` ADD  `is_recheck` INT( 1 ) NOT NULL COMMENT  '是否查重1是0否' AFTER  `is_unique`;

INSERT INTO `mxcrm_fields` (`model`, `is_main`, `field`, `name`, `form_type`, `default_value`, `color`, `max_length`, `is_unique`, `is_recheck`, `is_null`, `is_validate`, `in_index`, `in_add`, `input_tips`, `setting`, `order_id`, `operating`, `is_show`) VALUES('customer', 1, 'customer_status', '客户状态', 'box', '意向客户', '333333', 0, 0, 0, 1, 0, 1, 1, '', 'array(''type''=>''select'',''data''=>array(1=>''意向客户'',2=>''已成交客户'',3=>''失败客户''))', 1, 2, 0);

ALTER TABLE  `mxcrm_customer` ADD  `customer_status` VARCHAR( 255 ) NOT NULL DEFAULT '意向客户' COMMENT '客户状态';

ALTER TABLE  `mxcrm_user` ADD  `customer_num` INT NOT NULL COMMENT  '拥有客户数' AFTER  `number`;

ALTER TABLE  `mxcrm_contract` ADD  `contract_status` INT( 1 ) NOT NULL COMMENT  '1已续签2已忽略' AFTER  `type`;
ALTER TABLE  `mxcrm_contract` ADD  `renew_contract_id` INT NOT NULL COMMENT  '续签合同id' AFTER  `customer_id`;

CREATE TABLE IF NOT EXISTS `mxcrm_customer_share` (
  `share_id` int(10) NOT NULL AUTO_INCREMENT,
  `share_role_id` int(10) NOT NULL COMMENT '分享人ID',
  `by_sharing_id` int(10) NOT NULL COMMENT '被分享人ID',
  `customer_id` int(10) NOT NULL COMMENT '客户ID',
  `share_time` int(10) NOT NULL COMMENT '分享时间',
  PRIMARY KEY (`share_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_scene` (`id`, `module`, `name`, `role_id`, `order_id`, `data`, `create_time`, `update_time`, `is_hide`, `type`, `by`) VALUES (NULL, 'customer', '共享给我的', '', '5', '', '', '', '', '1', 'share');

INSERT INTO `mxcrm_scene` (`id`, `module`, `name`, `role_id`, `order_id`, `data`, `create_time`, `update_time`, `is_hide`, `type`, `by`) VALUES (NULL, 'customer', '我共享的', '', '6', '', '', '', '', '1', 'myshare');

CREATE TABLE IF NOT EXISTS `mxcrm_action_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `create_role_id` int(11) NOT NULL,
  `create_time` int(10) NOT NULL,
  `model_name` varchar(50) NOT NULL COMMENT '模块名',
  `action_id` int(11) NOT NULL COMMENT '模块信息ID',
  `type` varchar(30) NOT NULL,
  `duixiang` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_config` (`id`, `name`, `value`, `description`) VALUES (NULL, 'receivables_time', '30', '应收款提前几天提醒');

ALTER TABLE  `mxcrm_user` ADD  `crm_version` VARCHAR( 20 ) NOT NULL COMMENT  '版本信息';

CREATE TABLE IF NOT EXISTS `mxcrm_r_customer_invoice` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL COMMENT '客户ID',
  `invoice_header` varchar(255) NOT NULL COMMENT '开票抬头',
  `taxes_num` varchar(255) NOT NULL COMMENT '纳税识别号',
  `opening_bank` varchar(255) NOT NULL COMMENT '开户行',
  `account_number` varchar(255) NOT NULL COMMENT '开户账号',
  `billing_address` varchar(500) NOT NULL COMMENT '开票地址',
  `telephone` varchar(50) NOT NULL COMMENT '电话',
  `create_time` INT( 10 ) NOT NULL COMMENT  '创建时间',
  `update_time` INT( 10 ) NOT NULL COMMENT  '修改时间',
  `create_role_id` INT( 10 ) NOT NULL COMMENT  '创建人',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='客户发票信息表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_invoice` (
  `invoice_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR( 255 ) NOT NULL COMMENT '发票编号',
  `customer_id` int(10) NOT NULL COMMENT '客户ID',
  `contract_id` int(10) NOT NULL COMMENT '合同ID',
  `price` decimal(18,2) NOT NULL COMMENT '开票金额',
  `billing_type` tinyint(1) NOT NULL COMMENT '开票类型',
  `number` varchar(255) NOT NULL COMMENT '发票号码',
  `description` varchar(1000) NOT NULL COMMENT '备注',
  `invoice_header` varchar(255) NOT NULL COMMENT '开票抬头',
  `taxes_num` varchar(255) NOT NULL COMMENT '纳税识别号',
  `opening_bank` varchar(255) NOT NULL COMMENT '开户行',
  `account_number` varchar(255) NOT NULL COMMENT '开户账号',
  `billing_address` varchar(255) NOT NULL COMMENT '开票地址',
  `telephone` varchar(50) NOT NULL COMMENT '电话',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  `is_checked` TINYINT( 1 ) NOT NULL COMMENT  '0待审1通过2失败',
  `check_role_id` INT( 10 ) NOT NULL COMMENT  '审核人ID',
  `check_time` INT( 10 ) NOT NULL COMMENT  '审核时间',
  PRIMARY KEY (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同发票表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_file_invoice` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) NOT NULL COMMENT '发票ID',
  `file_id` int(10) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='发票附件关系表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_target` (
  `target_id` int(10) NOT NULL AUTO_INCREMENT,
  `target_type` tinyint(1) NOT NULL COMMENT '目标类型 1销售额 2回款金额',
  `id_type` tinyint(1) NOT NULL COMMENT '1 Id为部门Id  2Id为role_id',
  `id` int(10) NOT NULL,
  `year` int(4) NOT NULL COMMENT '年份',
  `month1` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month2` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month3` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month4` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month5` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month6` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month7` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month8` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month9` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month10` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month11` decimal(10,2) NOT NULL COMMENT '月份目标',
  `month12` decimal(10,2) NOT NULL COMMENT '月份目标',
  `total` decimal(10,2) NOT NULL COMMENT '年度总目标',
  PRIMARY KEY (`target_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='业绩目标设置表' AUTO_INCREMENT=1 ;

ALTER TABLE  `mxcrm_examine` CHANGE  `budget`  `budget` DECIMAL( 18, 2 ) NOT NULL COMMENT  '预算金额';

ALTER TABLE  `mxcrm_examine` CHANGE  `money`  `money` DECIMAL( 18, 2 ) NOT NULL COMMENT  '报销金额/借款金额';

ALTER TABLE  `mxcrm_examine` CHANGE  `advance`  `advance` DECIMAL( 18, 2 ) NOT NULL COMMENT  '预支金额';

ALTER TABLE  `mxcrm_examine_travel` CHANGE  `money`  `money` DECIMAL( 18, 2 ) NOT NULL COMMENT  '金额';

ALTER TABLE  `mxcrm_contract` CHANGE  `price`  `price` DECIMAL( 18, 2 ) NOT NULL COMMENT  '总价';

ALTER TABLE  `mxcrm_payables` CHANGE  `price`  `price` DECIMAL( 18, 2 ) NOT NULL COMMENT  '应付金额';

ALTER TABLE  `mxcrm_paymentorder` CHANGE  `money`  `money` DECIMAL( 18, 2 ) NOT NULL COMMENT  '付款金额';

ALTER TABLE  `mxcrm_sales` CHANGE  `prime_price`  `prime_price` DECIMAL( 18, 2 ) NOT NULL COMMENT  '销售单整体价格未减折扣额时价格';

ALTER TABLE  `mxcrm_sales` CHANGE  `sales_price`  `sales_price` DECIMAL( 18, 2 ) NOT NULL COMMENT  '折扣后销售单实际应付金额';

ALTER TABLE  `mxcrm_sales` CHANGE  `discount_price`  `discount_price` DECIMAL( 18, 2 ) NOT NULL COMMENT  '折扣额';

ALTER TABLE  `mxcrm_user` ADD  `extid` INT( 4 ) NOT NULL COMMENT  '坐席号' AFTER  `user_id`;

CREATE TABLE IF NOT EXISTS `mxcrm_invoice_check` (
  `check_id` int(10) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) NOT NULL COMMENT '发票ID',
  `role_id` int(10) NOT NULL COMMENT '负责人ID',
  `is_checked` tinyint(1) NOT NULL COMMENT '1通过2驳回',
  `content` varchar(500) NOT NULL COMMENT '审核内容',
  `check_time` int(10) NOT NULL COMMENT '审核时间',
  PRIMARY KEY (`check_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='发票审核记录表' AUTO_INCREMENT=1 ;

UPDATE  `mxcrm_fields` SET  `operating` =  '1' WHERE `model` = 'contacts' AND `field` = 'name';

UPDATE  `mxcrm_fields` SET  `operating` =  '1' WHERE `model` = 'contacts' AND `field` = 'telephone';

UPDATE  `mxcrm_fields` SET  `operating` =  '1' WHERE `model` = 'contacts' AND `field` = 'qq_no';

UPDATE  `mxcrm_fields` SET  `operating` =  '1' WHERE `model` = 'contacts' AND `field` = 'email';

ALTER TABLE  `mxcrm_task` ADD  `module` VARCHAR( 50 ) NOT NULL COMMENT  '相关模块',
ADD  `module_id` INT( 10 ) NOT NULL COMMENT  '相关模块ID';

ALTER TABLE  `mxcrm_examine` CHANGE  `budget`  `budget` DECIMAL( 18, 2 ) NOT NULL COMMENT  '普通报销、差旅、出差、借款（金额）';

ALTER TABLE  `mxcrm_invoice` ADD  `invoice_time` INT( 10 ) NOT NULL COMMENT  '开票时间';

ALTER TABLE  `mxcrm_task` CHANGE  `about_roles`  `about_roles` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '任务分配人';

ALTER TABLE  `mxcrm_task` CHANGE  `owner_role_id`  `owner_role_id` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '任务关注人';

CREATE TABLE IF NOT EXISTS `mxcrm_business_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '组名',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商机状态组表' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_business_type` (`id` ,`name` ,`create_role_id` ,`create_time` ,`update_time`) VALUES (1, '默认分组', '1', '1511768134', '1511768134');

ALTER TABLE  `mxcrm_business_status` ADD  `type_id` INT( 10 ) NOT NULL DEFAULT  '1' COMMENT  '状态组ID';

ALTER TABLE  `mxcrm_business` ADD  `status_type_id` INT( 10 ) NOT NULL DEFAULT  '1' COMMENT  '状态组ID';

ALTER TABLE `mxcrm_payables`
  DROP `receiver`,
  DROP `purchase_id`,
  DROP `sales_id`,
  DROP `sales_code`,
  DROP `purchase_code`;

ALTER TABLE  `mxcrm_payables` CHANGE  `contract_id`  `contract_id` INT( 10 ) NULL COMMENT  '合同id';

ALTER TABLE  `mxcrm_payables` CHANGE  `type`  `type_id` INT( 10 ) NOT NULL COMMENT  '应付款类型';

CREATE TABLE IF NOT EXISTS `mxcrm_finance_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `field` varchar(50) NOT NULL COMMENT '字段',
  `name` varchar(255) NOT NULL COMMENT '名称',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='财务相关类型' AUTO_INCREMENT=1 ;

INSERT INTO `mxcrm_finance_type` (`id`, `field`, `name`, `create_role_id`, `create_time`, `update_time`) VALUES
(1, 'payables', '客户支出', 1, 1512113079, 1512113079),
(2, 'payables', '报销', 1, 1512113270, 1512113270),
(3, 'payables', '工资', 1, 1512113279, 1512113279);

ALTER TABLE  `mxcrm_payables` CHANGE  `contract_id`  `contract_id` INT( 10 ) NOT NULL COMMENT  '合同id';

ALTER TABLE  `mxcrm_paymentorder` CHANGE  `update_time`  `update_time` INT( 10 ) NOT NULL COMMENT  '修改时间';

ALTER TABLE  `mxcrm_paymentorder` ADD  `check_time` INT( 10 ) NOT NULL COMMENT  '审核时间' AFTER  `examine_role_id`;

ALTER TABLE  `mxcrm_paymentorder` CHANGE  `type`  `type` INT( 10 ) NOT NULL COMMENT  '类别（暂不用）';

ALTER TABLE `mxcrm_receivables`
  DROP `sales_id`,
  DROP `purchase_id`,
  DROP `sales_code`,
  DROP `purchase_code`;

ALTER TABLE  `mxcrm_bank_account` CHANGE  `bank_account`  `bank_account` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '银行账号';

ALTER TABLE  `mxcrm_bank_account` CHANGE  `company`  `company` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '收款单位';

ALTER TABLE  `mxcrm_bank_account` CHANGE  `open_bank`  `open_bank` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '开户行';

ALTER TABLE  `mxcrm_bank_account` CHANGE  `description`  `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '备注';

ALTER TABLE `mxcrm_paymentorder`
  DROP `bank_name`,
  DROP `bank_acount`;

ALTER TABLE  `mxcrm_paymentorder` CHANGE  `receipt_account`  `receipt_account` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT  '银行账户';

ALTER TABLE  `mxcrm_paymentorder` CHANGE  `company`  `company` VARCHAR( 255 ) NOT NULL COMMENT  '付款单位';

ALTER TABLE  `mxcrm_paymentorder` CHANGE  `bank_account_id`  `bank_account_id` INT( 10 ) NOT NULL COMMENT  '付款账户';

INSERT INTO `mxcrm_fields` ( `model`, `is_main`, `field`, `name`, `form_type`, `default_value`, `color`, `max_length`, `is_unique`, `is_recheck`, `is_null`, `is_validate`, `in_index`, `in_add`, `input_tips`, `setting`, `order_id`, `operating`) VALUES
( 'business', 1, 'name', '商机名', 'text', '', '090D08', 0, 1, 0, 1, 1, 1, 1, '', '', 0, 2),
( 'business', 1, 'customer_id', '客户', 'customer', '', '', 0, 0, 0, 0, 1, 1, 1, '', '', 1, 2),
( 'business', 1, 'contacts_id', '联系人', 'contacts', '', '', 0, 0, 0, 0, 0, 1, 1, '', '', 2, 2),
( 'business', 1, 'status_id', '商机进度', 'b_box', '', '', 0, 0, 0, 0, 0, 1, 1, '', '', 3, 2),
( 'business', 1, 'possibility', '成交几率', 'p_box', '', '', 0, 0, 0, 0, 1, 1, 1, '', '', 4, 2),
( 'business', 1, 'nextstep_time', '下次联系时间', 'datetime', '', '', 0, 0, 0, 0, 1, 1, 1, '', '', 5, 2),
( 'business', 0, 'description', '备注', 'textarea', '', '', 0, 0, 0, 0, 0, 0, 1, '', '', 6, 1);

CREATE TABLE IF NOT EXISTS `mxcrm_business_data` (
  `business_id` int(10) NOT NULL COMMENT '主键',
  `description` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`business_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商机数据表';

ALTER TABLE  `mxcrm_receivingorder` ADD  `check_time` INT( 10 ) NOT NULL COMMENT  '审核时间';

ALTER TABLE  `mxcrm_business_status` CHANGE  `is_end`  `is_end` INT( 1 ) NOT NULL COMMENT  '0正常2失败3成功';

UPDATE `mxcrm_business_status` SET  `is_end` =  '2' WHERE  `mxcrm_business_status`.`status_id` =99;

UPDATE `mxcrm_business_status` SET  `is_end` =  '3' WHERE  `mxcrm_business_status`.`status_id` =100;

ALTER TABLE  `mxcrm_business_status` CHANGE  `name`  `name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT  '商机状态名';

ALTER TABLE mxcrm_business_status DROP INDEX name;

ALTER TABLE mxcrm_business_status DROP INDEX name_2;

CREATE TABLE IF NOT EXISTS `mxcrm_kaoqin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `daka_time` int(10) NOT NULL COMMENT '打卡时间',
  `x` varchar(255) NOT NULL,
  `y` varchar(255) NOT NULL,
  `address` varchar(500) NOT NULL,
  `status` int(2) NOT NULL COMMENT '状态  （1 正常签到）（2 迟到） （3 早退） （4 正常签退）',
  `config_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '规则类型（1wifi,2地理位置）',
  `remark` varchar(500) NOT NULL,
  `shangban_time` varchar(30) NOT NULL COMMENT '上班时间',
  `xiaban_time` varchar(30) NOT NULL COMMENT '下班时间',
  `token_id` varchar(100) NOT NULL COMMENT '设备ID',
  `wifi_name` varchar(255) NOT NULL COMMENT 'wifi名称',
  `mac_address` varchar(255) NOT NULL COMMENT 'MAC地址',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_route` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `wifi_name` varchar(255) NOT NULL COMMENT 'wifi名称',
  `mac_address` varchar(255) NOT NULL COMMENT 'mac地址',
  `create_role_id` int(10) NOT NULL COMMENT '创建人',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='考勤路由' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_kaoqin_config` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `shangban_time` TIME NOT NULL COMMENT '上班时间',
  `xiaban_time` TIME NOT NULL COMMENT '下班时间',
  `x` varchar(30) NOT NULL COMMENT 'x坐标',
  `y` varchar(30) NOT NULL COMMENT 'y坐标',
  `radius` int(10) NOT NULL COMMENT '半径（米）',
  `create_role_id` int(10) NOT NULL COMMENT '创建人',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='考勤规则表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_r_contract_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) NOT NULL COMMENT '合同ID',
  `log_id` int(10) NOT NULL COMMENT '日志ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同日志表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mxcrm_cycel` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL COMMENT '模块',
  `module_id` int(10) NOT NULL COMMENT '模块ID',
  `num` varchar(50) NOT NULL COMMENT '数量',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '类型（1周2月3年4仅一次）',
  `start_time` int(10) NOT NULL COMMENT '开始时间',
  `end_time` int(10) NOT NULL COMMENT '结束时间',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='自定义循环周期表' AUTO_INCREMENT=1 ;

ALTER TABLE  `mxcrm_contract` ADD  `renew_parent_id` INT( 10 ) NOT NULL COMMENT  '续约组父类ID';

CREATE TABLE IF NOT EXISTS `mxcrm_contract_examine` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL COMMENT '岗位ID',
  `order_id` int(10) NOT NULL COMMENT '排序ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同审批流' AUTO_INCREMENT=1 ;

ALTER TABLE  `mxcrm_contract_check` CHANGE  `is_checked`  `is_checked` TINYINT( 1 ) NOT NULL COMMENT  '审核状态(1同意2驳回)';

ALTER TABLE  `mxcrm_contract` ADD  `order_id` INT( 10 ) NOT NULL COMMENT  '审批流程ID';

ALTER TABLE  `mxcrm_contract` CHANGE  `is_checked`  `is_checked` INT( 10 ) NOT NULL COMMENT  '是否审核0未审核1通过2未通过3审批中';

ALTER TABLE  `mxcrm_contract` ADD  `examine_type_id` INT( 10 ) NOT NULL COMMENT  '审批流类型ID（0自选1默认流程1）';

ALTER TABLE  `mxcrm_kaoqin_config` ADD  `reg_address` VARCHAR( 1000 ) NOT NULL COMMENT  '地理位置';

ALTER TABLE  `mxcrm_invoice` ADD  `express` VARCHAR( 255 ) NOT NULL COMMENT  '快递单号';

ALTER TABLE  `mxcrm_user` CHANGE  `name`  `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '用户名';

ALTER TABLE  `mxcrm_user` CHANGE  `email`  `email` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '用户邮箱';

INSERT INTO `mxcrm_fields` ( `model`, `is_main`, `field`, `name`, `form_type`, `default_value`, `color`, `max_length`, `is_unique`, `is_recheck`, `is_null`, `is_validate`, `in_index`, `in_add`, `input_tips`, `setting`, `order_id`, `operating`) VALUES
( 'contract', 1, 'contract_name', '合同名称', 'text', '', '090D08', 0, 1, 0, 1, 1, 1, 1, '', '', 0, 2),
( 'contract', 1, 'customer_id', '客户', 'customer', '', '', 0, 0, 0, 0, 1, 1, 1, '', '', 1, 2),
( 'contract', 1, 'business_id', '商机', 'business', '', '', 0, 0, 0, 0, 0, 1, 1, '', '', 2, 2),
( 'contract', 1, 'price', '合同金额(元)', 'text', '', '', 0, 0, 0, 0, 0, 1, 1, '', '', 3, 2),
( 'contract', 1, 'due_time', '签约时间', 'datetime', '', '', 0, 0, 0, 1, 1, 0, 1, '', '', 4, 2),
( 'contract', 1, 'start_date', '合同生效时间', 'datetime', '', '', 0, 0, 0, 0, 1, 1, 1, '', '', 5, 2),
( 'contract', 1, 'end_date', '合同到期时间', 'datetime', '', '', 0, 0, 0, 0, 1, 1, 1, '', '', 6, 2),
( 'contract', 1, 'description', '合同描述', 'textarea', '', '', 0, 0, 0, 0, 0, 0, 1, '', '', 7, 1);

CREATE TABLE IF NOT EXISTS `mxcrm_contract_data` (
  `contract_id` int(10) NOT NULL COMMENT '主键',
  PRIMARY KEY (`contract_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='合同附表';

INSERT INTO `mxcrm_scene` (`id`, `module`, `name`, `role_id`, `order_id`, `data`, `create_time`, `update_time`, `is_hide`, `type`, `by`) VALUES
('', 'contract', '我的合同', 0, 0, '', 0, 0, 0, 1, 'me'),
('', 'contract', '下属合同', 0, 1, '', 0, 0, 0, 1, 'sub'),
('', 'contract', '全部合同', 0, 3, '', 0, 0, 0, 1, 'all'),
('', 'leads', '我的线索', 0, 0, '', 0, 0, 0, 1, 'me'),
('', 'leads', '下属线索', 0, 1, '', 0, 0, 0, 1, 'sub'),
('', 'leads', '全部线索', 0, 3, '', 0, 0, 0, 1, 'all'),
('', 'business', '我的商机', 0, 0, '', 0, 0, 0, 1, 'me'),
('', 'business', '下属商机', 0, 1, '', 0, 0, 0, 1, 'sub'),
('', 'business', '全部商机', 0, 3, '', 0, 0, 0, 1, 'all');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
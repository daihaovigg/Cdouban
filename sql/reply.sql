create table if not exists `mydouban`.`reply`(
	`id` int(11) not NULL auto_increment,
	`author_id` int(11) default NULL,
	`to_who` int(11) default NULL,
	`to_comment` int(11) default NULL,
	`content` varchar(200) default NULL,
	`time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	primary key (`id`)
	)engine=myisam character set utf8 collate utf8_unicode_ci;
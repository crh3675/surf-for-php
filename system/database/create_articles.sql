-- 
--  create_articles.sql
--  Surf
--  
--  Created by Craig Hoover on 2010-07-29.
--  Copyright 2010 __MyCompanyName__. All rights reserved.
-- 

#DROP TABLE If EXISTS `articles`;
CREATE TABLE `articles` (
	id int unsigned not null auto_increment, 
	title varchar(255) not null, 
	body text, 
	language varchar(4),
	active boolean, 
	created_on datetime, 
	updated_on timestamp not null default current_timestamp on update current_timestamp,
	primary key(id)
);

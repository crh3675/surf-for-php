-- 
--  create_categories.sql
--  Surf
--  
--  Created by Craig Hoover on 2010-07-29.
--  Copyright 2010 __MyCompanyName__. All rights reserved.
-- 
#DROP TABLE I EXISTS categories;
CREATE TABLE `categories` (
	id int unsigned not null auto_increment,
	parent_id int unsigned not null default 0,
	name varchar(255) not null,
	primary key(id),
	index `category_parent_index` (parent_id)	
);
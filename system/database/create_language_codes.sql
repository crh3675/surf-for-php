-- 
--  create_language_codes.sql
--  Surf
--  
--  Created by Craig Hoover on 2010-07-29.
--  Copyright 2010 __MyCompanyName__. All rights reserved.
-- 

#DROP TABLE If EXISTS `language_codes`;
CREATE TABLE `language_codes` (
	id int unsigned not null auto_increment, 
	code varchar(4) not null, 
	name varchar(75),
	created_on datetime, 
	updated_on timestamp not null default current_timestamp on update current_timestamp,
	primary key(id),
	unique `code_index` (code)
);
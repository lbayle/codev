
-- this script is to be executed to update CodevTT DB v18 to v19.
SET SQL_MODE='ANSI';

-- CREATE INDEX idx_tt_bugid_date ON codev_timetracking_table ("bugid", "date");

-- BlogPlugin
CREATE INDEX key1 ON codev_blog_table ("dest_team_id", "dest_user_id", "date_submitted");
CREATE INDEX key1 ON codev_blog_activity_table ("blog_id", "user_id", "action");


-- leader_id is replaced by a list of administrators '123,543,98,43'
ALTER TABLE "codev_team_table" ADD "administrators" varchar(255) DEFAULT NULL AFTER "description";
UPDATE "codev_team_table" SET "administrators" = "leader_id";
-- ALTER TABLE "codev_team_table" DROP "leader_id";

-- increase size for message plugin
ALTER TABLE "codev_blog_table" MODIFY "content" VARCHAR(2000);

-- tag version
UPDATE "codev_config_table" SET "value"='19' WHERE "config_id"='database_version';



-- this script is to be executed to update CodevTT DB v18 to v19.
SET SQL_MODE='ANSI';

-- CREATE INDEX idx_tt_bugid_date ON codev_timetracking_table ("bugid", "date");

-- BlogPlugin
CREATE INDEX key1 ON codev_blog_table ("dest_team_id", "dest_user_id", "date_submitted");
CREATE INDEX key1 ON codev_blog_activity_table ("blog_id", "user_id", "action");

INSERT INTO "codev_blog_table" ("id", "date_submitted", "src_user_id", "dest_user_id", "dest_project_id", "dest_team_id", "severity", "category", "summary", "content", "date_expire", "color") VALUES
(1, 1526228606, 0, 0, 0, 0, 2, '3', 'Exchange messages with your team !', 'Hi,\nthis plugins allows to share notifications within the teams.\n\nExemples:\n\"The time spent on the March 3rd meeting must be counted on task 34001\"\n\"The Mantis/CodevTT server will be unavailable for maintenance on May 13th\"\n\nUse the <img src=\"images/b_add.png\"/> button on your right to add messages.\nTo hide this message, click <img src="images/b_markAsRead.png"/> then <img src="images/b_ghost.png"/>\n\nBest regards', 0, '0');

-- leader_id is replaced by a list of administrators '123,543,98,43'
ALTER TABLE "codev_team_table" ADD "administrators" varchar(255) DEFAULT NULL AFTER "description";
UPDATE "codev_team_table" SET "administrators" = "leader_id";
-- ALTER TABLE "codev_team_table" DROP "leader_id";

-- increase size for message plugin
ALTER TABLE "codev_blog_table" MODIFY "content" VARCHAR(2000);

-- tag version
UPDATE "codev_config_table" SET "value"='19' WHERE "config_id"='database_version';


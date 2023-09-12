SELECT setval('users_id_seq', (select coalesce(max(id), 1) from users), (case when (select max(id) from users) is NULL then false else true end));
SELECT setval('groups_id_seq', (select coalesce(max(id), 1) from groups), (case when (select max(id) from groups) is NULL then false else true end));
SELECT setval('projects_id_seq', (select coalesce(max(id), 1) from projects), (case when (select max(id) from projects) is NULL then false else true end));

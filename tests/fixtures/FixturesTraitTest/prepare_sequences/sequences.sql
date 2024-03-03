SELECT setval('articles_id_seq', (select coalesce(max(id), 1) from articles), (case when (select max(id) from articles) is NULL then false else true end));
SELECT setval('workers_id_seq', (select coalesce(max(id), 1) from workers), (case when (select max(id) from workers) is NULL then false else true end));
SELECT setval('groups_id_seq', (select coalesce(max(id), 1) from groups), (case when (select max(id) from groups) is NULL then false else true end));
SELECT setval('homes_id_seq', (select coalesce(max(id), 1) from homes), (case when (select max(id) from homes) is NULL then false else true end));
SELECT setval('users_id_seq', (select coalesce(max(id), 1) from users), (case when (select max(id) from users) is NULL then false else true end));
SELECT setval('telescope_entries_sequence_seq', (select coalesce(max(sequence), 1) from telescope_entries), (case when (select max(sequence) from telescope_entries) is NULL then false else true end));

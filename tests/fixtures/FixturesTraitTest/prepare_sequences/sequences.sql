SELECT setval('articles_id_seq', (select coalesce(max(id), 1) from public.articles), (case when (select max(id) from public.articles) is NULL then false else true end));
SELECT setval('workers_id_seq', (select coalesce(max(id), 1) from public.workers), (case when (select max(id) from public.workers) is NULL then false else true end));
SELECT setval('groups_id_seq', (select coalesce(max(id), 1) from public.groups), (case when (select max(id) from public.groups) is NULL then false else true end));
SELECT setval('homes_id_seq', (select coalesce(max(id), 1) from public.homes), (case when (select max(id) from public.homes) is NULL then false else true end));
SELECT setval('users_id_seq', (select coalesce(max(id), 1) from public.users), (case when (select max(id) from public.users) is NULL then false else true end));
SELECT setval('telescope_entries_sequence_seq', (select coalesce(max(sequence), 1) from public.telescope_entries), (case when (select max(sequence) from public.telescope_entries) is NULL then false else true end));

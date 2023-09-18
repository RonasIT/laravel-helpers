INSERT INTO users(id, name, email, password, remember_token, set_password_hash, role_id, created_at, updated_at, set_password_hash_created_at) VALUES
  (1, 'Gerhard Feest', 'fidel.kutch@example.com', '$2y$10$X4receiTrF24bXrEbAiChOZ8TMNPqoXuhuThgynvBdWIHZeu5HzsS', null, 'good_token1', 1, '2016-10-20 11:05:00', '2016-10-20 11:05:00', '2018-11-11 10:11:12'),
  (2, 'Alien West', 'alien.west@example.com', 'old_password', null, 'restore_token', 2, '2016-10-20 11:05:00', '2016-10-20 11:05:00', '2018-11-11 10:11:10'),
  (3, 'Alien East', 'alien.ease@example.com', 'old_password', null, 'good_token2', 2, '2016-10-20 11:05:00', '2016-10-20 11:05:00', '2018-11-11 10:35:00'),
  (4, 'Alien North', 'alien.north@example.com', 'old_password', null, 'bad_token1', 2, '2016-10-20 11:05:00', '2016-10-20 11:05:00', '2018-11-11 8:06:00'),
  (5, 'Alien South', 'alien.south@example.com', 'old_password', null, 'bad_token2', 2, '2016-10-20 11:05:00', '2016-10-20 11:05:00', '2018-11-11 9:04:00');
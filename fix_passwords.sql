-- Reset all users password to 'Welcom@123'
-- Hash: $2y$10$ZH6y03xdlw8G6KlDwX5VDedO4/SeeBK03BIfO30FVXzN/JM/q.0tq

UPDATE users SET password = '$2y$10$ZH6y03xdlw8G6KlDwX5VDedO4/SeeBK03BIfO30FVXzN/JM/q.0tq';
FLUSH PRIVILEGES; 

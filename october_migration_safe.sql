-- Migration for October 2025 Users and Bookings
-- Setting proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Add users (excluding problematic special characters for now)
INSERT IGNORE INTO users (id, first_name, last_name, email, password, user_role, role, created_at, updated_at) VALUES
(3, 'Alexey', 'Tamir', 'none@none.com', '$2y$10$tO2ZdIxtGelzILX8xFvqsujj5MA9TluAvIL4h1VuIGYFI3j9EWViS', 'guest', 'guest', '2025-09-26 16:30:45', '2025-10-11 11:24:35'),
(8, 'Clil', 'katz , Atar sivan', 'clil.katz@gmail.com', '$2y$10$2geZhyCm8xNQ8fl45igXPO7CowVgYSXQ5Yq3ezEbBdDYiJMU9f9E.', 'guest', 'guest', '2025-09-29 09:05:54', '2025-09-29 09:05:54'),
(9, 'lisha', 'Levi', 'lihi26013@gmail.com', '$2y$10$LuEh3QL6L/bCTY3T8/iiFuLs2j1N9VpwOoxslrRXzkm1MHhUFJvHi', 'guest', 'guest', '2025-09-29 12:42:13', '2025-09-29 12:42:13'),
(10, 'ella', 'shoval', 'ellagreenfeld33@gmail.com', '$2y$10$/JxrPz6faK3ZxUATelMMweneMxgvXo7UJlwyVEOWcjEuAFpGFd4uq', 'guest', 'guest', '2025-09-29 15:04:00', '2025-09-30 14:35:08'),
(11, 'Roberto', 'Eraso', 'robertoeraso79@gmail.com', '$2y$10$KdI6Ugu2xhfpdrJLoNxbJOYFtUpysQZdcG5FyHZUxYBUnz7rdlTiq', 'guest', 'guest', '2025-09-29 16:48:07', '2025-09-29 16:48:07'),
(12, 'Eden', 'Hili', 'hilieden86@gmail.com', '$2y$10$dmUlDbxZHEp3VyLRFLKzHu0SR23MkGVGFGT8lG2sHyAPpsTfL7vuS', 'guest', 'guest', '2025-09-29 18:14:44', '2025-09-29 18:14:44'),
(13, 'Inbar', 'Shelhava', 'InbarSHA2002@gmail.com', '$2y$10$7FSQ7KPyJYPDW8aMwdq5yuHF1y1cJpDKPHOfvL4e1CsIsOW/NobLS', 'guest', 'guest', '2025-09-30 08:21:46', '2025-09-30 08:21:46'),
(14, 'Tamara', 'Recarte', 'rocketier2005@gmail.com', '$2y$10$jdDj9v4UIaagWKWwf8ON6.jcZ5U.L3Xpg/XUaXqzJN9hRRGio5ZjK', 'guest', 'guest', '2025-09-30 09:55:02', '2025-09-30 09:55:02'),
(15, 'Shir', 'Cohin', 'shirrrr33@gmail.com', '$2y$10$QB8JwTZekNDTBPZHXsPfe.fhfmZDBqn3NO4jknKGkewrEfbA/.3gG', 'guest', 'guest', '2025-09-30 10:15:40', '2025-09-30 10:15:40'),
(16, 'Martin', 'Groppa', 'mgropp.284431@guest.booking.com', '$2y$10$NORqmUKBr0hoSmNsyBdQUOW4J.1JVbXu4HI8qEAjKwMqNFMbRk8eq', 'guest', 'guest', '2025-09-30 11:20:03', '2025-09-30 11:20:03'),
(17, 'Gabay', 'Moran', 'gmoran.788257@guest.booking.com', '$2y$10$7GLMrddIhCcqDSO6XBYh1.vDLJf/nC9WO5W9m4EQzsOrtaOW/xKt2', 'guest', 'guest', '2025-09-30 11:38:27', '2025-09-30 11:38:27'),
(18, 'Roy', 'Armony', 'rarmon.407383@guest.booking.com', '$2y$10$tGjib5YqXVEXryyP8VCdS.N49Mr.VR.vL.CcSVPmJQArcZ.yfcDoq', 'guest', 'guest', '2025-09-30 13:29:16', '2025-09-30 13:29:16'),
(19, 'Matan', 'El', 'matanelch@gmail.com', '$2y$10$uL7wM/H.KRLR9fqtfFAiE.OK5fGD4f4smwE8KrC90opQUzIyKdW7O', 'guest', 'guest', '2025-09-30 13:29:40', '2025-09-30 13:29:40'),
(20, 'Tiltan', 'Marsh', 'no@no.com', '$2y$10$ULN6gBViwKlvI5gRuK5nmuak8tJAlqb7zbSm4VEf1bJtba4xJJou6', 'guest', 'guest', '2025-09-30 15:00:41', '2025-09-30 15:00:41'),
(21, 'Dominik', 'Morgott', 'dmorgo.674433@guest.booking.com', '$2y$10$RCcylnvK8lOcnFW6t.F3QurkISmdOqIOv8jghMVDwG3q..pQOszKK', 'guest', 'guest', '2025-10-01 08:41:36', '2025-10-01 08:41:36'),
(22, 'Shadaj', 'Rotem', 'srvtm.543374@guest.booking.com', '$2y$10$iMvKX6asYU0W2/zB9iAa.u9yoKMUm.ya.jF6vWZW2RS/JzPxTtvIS', 'guest', 'guest', '2025-10-01 10:10:31', '2025-10-01 10:10:31'),
(23, 'Larisa', 'Ustalov', 'lustal.807553@guest.booking.com', '$2y$10$tPOMYcoQS/nxgJGMjDBvmelqvLbKDZoVrlPav0Vut8bLfLKhyfJDO', 'guest', 'guest', '2025-10-01 12:07:44', '2025-10-01 12:07:44'),
(24, 'airbnb', '', 'none@no.com', '$2y$10$K6n70PnHT7RS6qg8oolmm.N8VJcVhyAkuvjVdpXLjXXlg39igsTDa', 'guest', 'guest', '2025-10-01 14:51:40', '2025-10-01 14:51:40'),
(25, 'MAASA', 'YUSAKU', 'myusak.812445@guest.booking.com', '$2y$10$r6.aN1t4Fpl8bC6A6O7nvOwMvWM5O2CzhzVla0dskSOXRu0LzSDA2', 'guest', 'guest', '2025-10-01 15:56:11', '2025-10-01 15:56:11'),
(26, 'Alex', 'Wingarten', 'alexw1900@gmail.com', '$2y$10$43CamNYmhSuBukKGqhrWHe4EjCLLK5tBJnTRLkdmqZ7FSJJBZFa5m', 'guest', 'guest', '2025-10-02 09:05:40', '2025-10-02 09:05:40'),
(27, 'Ina', 'Kathrin', 'ina.nenning@gmail.com', '$2y$10$k1Nr6FKhG30SLoyrsXZhQO6PZ9JIBJhB5Wd1i7pHGRXaJO.wCYUS2', 'guest', 'guest', '2025-10-02 09:27:48', '2025-10-02 09:27:48'),
(28, 'Cassie', 'Luzenski', 'cluzen.768584@guest.booking.com', '$2y$10$F4uKvTmQ1Asdr4DhQgq4KutVbnWgHjw2yfS6/2ddpMh4PTxGMJk4S', 'guest', 'guest', '2025-10-02 09:40:43', '2025-10-02 09:40:43'),
(29, 'Alejandro', 'Costanzo', 'acosta.198532@guest.booking.com', '$2y$10$l5/D0HX7ODBXZ9VWwB8apeK0mu8vbP8x6h2EISzByYBJl0GfM7zMS', 'guest', 'guest', '2025-10-02 10:27:15', '2025-10-02 10:27:15'),
(30, 'Greenfeld', 'Ella', 'glh.907935@guest.booking.com', '$2y$10$vHhj1EkGFgknXvMHO2Tio.XRsgXBDnY4e5asgDT.0qdbPcxJNZM7K', 'guest', 'guest', '2025-10-02 11:16:56', '2025-10-02 11:16:56'),
(31, 'Nadine', 'Salha', 'nsalha.516763@guest.booking.com', '$2y$10$vipmoWL2BMljfMDjD6hs8.E7fczUrXGqLfeDo7uLqJU4IHrwD2Cka', 'guest', 'guest', '2025-10-02 12:05:08', '2025-10-02 12:05:08'),
(32, 'Adriana', 'Pereiro Felipez', 'afelip.767956@guest.booking.com', '$2y$10$2Kin6GCqGY7PUQM2MDch7ekgMcUgat3ew2OaqQ9U6G8ji6a3vDXUi', 'guest', 'guest', '2025-10-02 12:44:32', '2025-10-02 12:44:32'),
(33, 'Chad', 'Mummert', 'markertersnerviana@gmail.com', '$2y$10$wNng.t6tuJ27SLJy8TGh..CfzrVGa6ig1wobc4.zOX7GSJnisIP8W', 'guest', 'guest', '2025-10-03 08:26:50', '2025-10-03 08:26:50'),
(34, 'Yasmin', 'Dital', 'ditalyasmin@gmail.com', '$2y$10$QXjAeNZPdCuyGZRhAmQjO.k6nc1WrNf.SUgY9TcZpLzzO8rxJtj8e', 'guest', 'guest', '2025-10-03 11:31:17', '2025-10-03 11:31:17'),
(35, 'JP', 'GIll', 'none@gmail.com', '$2y$10$PUSmR4B7ij.OZ.Ux6Uy7h.qfSTFFFVoJTXJg5P3sGZ2j.6wrXvzEK', 'guest', 'guest', '2025-10-03 13:47:36', '2025-10-03 13:47:36'),
(36, 'Michel', 'Doerk', 'michel-doerk@gmx.de', '$2y$10$aivpDyB/lmgkt8GhUKgOcOV1IvxVsXgeOoMK.UcqlTvsK8mQzCzQ6', 'guest', 'guest', '2025-10-03 14:37:37', '2025-10-03 14:37:37');

-- Add key bookings for October 2025
INSERT IGNORE INTO bookings (id, user_id, room_id, check_in_date, check_out_date, total_price, status, created_at, is_multi_room, primary_booking_id) VALUES
(7, 3, 14, '2025-09-29', '2025-10-05', 112.00, 'confirmed', '2025-09-29 01:43:12', 0, NULL),
(8, 8, 9, '2025-09-30', '2025-10-03', 77.27, 'confirmed', '2025-09-29 09:05:54', 0, NULL),
(9, 9, 2, '2025-09-29', '2025-10-02', 63.57, 'confirmed', '2025-09-29 12:42:13', 0, NULL),
(10, 10, 12, '2025-09-30', '2025-10-02', 68.57, 'confirmed', '2025-09-29 15:04:00', 0, NULL),
(11, 11, 10, '2025-09-29', '2025-10-02', 12.00, 'confirmed', '2025-09-29 16:48:07', 0, NULL),
(12, 12, 11, '2025-09-30', '2025-10-05', 115.33, 'confirmed', '2025-09-29 18:14:44', 0, NULL),
(13, 12, 11, '2025-09-30', '2025-10-02', 52.00, 'confirmed', '2025-09-29 18:15:30', 0, NULL),
(14, 13, 8, '2025-09-30', '2025-10-05', 157.33, 'confirmed', '2025-09-30 08:21:46', 0, NULL),
(15, 13, 8, '2025-09-30', '2025-10-04', 117.33, 'confirmed', '2025-09-30 08:22:12', 0, NULL),
(16, 14, 13, '2025-09-30', '2025-10-02', 40.00, 'confirmed', '2025-09-30 09:55:02', 0, NULL),
(17, 14, 13, '2025-09-30', '2025-10-02', 40.00, 'confirmed', '2025-09-30 09:55:25', 0, NULL),
(18, 15, 15, '2025-09-30', '2025-10-04', 158.93, 'confirmed', '2025-09-30 10:15:40', 0, NULL),
(19, 16, 5, '2025-09-30', '2025-10-02', 46.00, 'confirmed', '2025-09-30 11:20:03', 0, NULL),
(20, 17, 4, '2025-09-30', '2025-10-02', 46.00, 'confirmed', '2025-09-30 11:38:27', 0, NULL),
(21, 18, 6, '2025-09-30', '2025-10-02', 46.00, 'confirmed', '2025-09-30 13:29:16', 0, NULL),
(22, 19, 7, '2025-09-30', '2025-10-02', 46.00, 'confirmed', '2025-09-30 13:29:40', 0, NULL),
(23, 20, 16, '2025-09-30', '2025-10-09', 369.27, 'confirmed', '2025-09-30 15:00:41', 0, NULL),
(24, 21, 1, '2025-10-01', '2025-10-07', 218.40, 'confirmed', '2025-10-01 08:41:36', 0, NULL),
(25, 22, 3, '2025-10-01', '2025-10-04', 69.00, 'confirmed', '2025-10-01 10:10:31', 0, NULL),
(26, 23, 17, '2025-10-01', '2025-10-03', 92.00, 'confirmed', '2025-10-01 12:07:44', 0, NULL),
(27, 24, 18, '2025-10-01', '2025-10-04', 138.00, 'confirmed', '2025-10-01 14:51:40', 0, NULL),
(28, 25, 19, '2025-10-01', '2025-10-03', 92.00, 'confirmed', '2025-10-01 15:56:11', 0, NULL);
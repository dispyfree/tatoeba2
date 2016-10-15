
-- Database: `tatoeba`
--

-- --------------------------------------------------------

--
-- Table structure for table `users_vocabulary_sentences`
--

CREATE TABLE `users_vocabulary_sentences` (
  `id` int(11) NOT NULL,
  `user_vocabulary_id` int(11) NOT NULL,
  `sentence_id` int(11) NOT NULL,
  `automatically_associated` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `users_vocabulary_sentences`
--
ALTER TABLE `users_vocabulary_sentences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_vocabulary_id` (`user_vocabulary_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users_vocabulary_sentences`
--
ALTER TABLE `users_vocabulary_sentences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

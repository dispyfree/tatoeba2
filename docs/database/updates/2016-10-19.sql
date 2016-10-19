/** add vocabulary association feature 
    github #1281
**/ 

ALTER TABLE `users_vocabulary` ADD `num_assoc_sent` INT NOT NULL AFTER `vocabulary_id`;

source docs/database/tables/users_vocabulary_sentences.sql;
source docs/database/triggers/update_users_vocabulary_associated_sentences.sql;

DROP TRIGGER IF EXISTS increment_vocabulary_associated_sent_count;
DROP TRIGGER IF EXISTS decrement_vocabulary_associated_sent_count;

delimiter |
CREATE TRIGGER increment_vocabulary_associated_sent_count AFTER INSERT ON users_vocabulary_sentences
FOR EACH ROW BEGIN
    UPDATE users_vocabulary SET num_assoc_sent = num_assoc_sent + 1 WHERE id = NEW.user_vocabulary_id;
END;
| 
CREATE TRIGGER decrement_vocabulary_associated_sent_count AFTER DELETE ON users_vocabulary_sentences
FOR EACH ROW BEGIN
    UPDATE users_vocabulary SET num_assoc_sent = num_assoc_sent - 1 WHERE id = OLD.user_vocabulary_id;
END;
|
delimiter ;

<?php
/**
 * Tatoeba Project, free collaborative creation of languages corpuses project
 * Copyright (C) 2010  HO Ngoc Phuong Trang <tranglich@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */

/**
 * Model for association table between UsersVocabulary and Sentences
 *
 * @category UsersVocabularySentences
 * @package  Models
 * @author   dispy
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */

class UsersVocabularySentences extends AppModel
{
    public $name = 'UsersVocabularySentences';
    public $useTable = 'users_vocabulary_sentences';
    public $actsAs = array('Containable');

    public $belongsTo = array(
        'Sentence' => array('foreignKey' => 'sentence_id'),
        'UsersVocabulary' => array('foreignKey' => 'user_vocabulary_id')
    );
    
    public function findByCombination($sentence, $usersVocabulary){
        return $this->find('first', array(
            'conditions' => array(
                'sentence_id'           => $sentence['Sentence']['id'],
                'user_vocabulary_id'    => $usersVocabulary['UsersVocabulary']['id']
        )));
    }
    
    public function associate($sentence, $uVocabulary){
        //Check whether there is already an association
        $assoc = $this->findByCombination($sentence, $uVocabulary);
        
         if($assoc)
            throw new InvalidArgumentException('alreadyAssociated');
         
        $assoc = array( 
            'UsersVocabularySentences' => array(
                'sentence_id'                => $sentence['Sentence']['id'],
                'user_vocabulary_id'        => $uVocabulary['UsersVocabulary']['id'],
                'automatically_associated'  => false
            )
        );
        
        if(!$this->save($assoc))
            throw new RuntimeException(__('Unable to save association'));
    }
    
    public function unAssociate($sentence, $uVocabulary){
         //Check whether there is already an association
        $assoc = $this->findByCombination($sentence, $uVocabulary);
        
        if(!$assoc)
            throw new InvalidArgumentException('notAssociated');
        
        if (!$this->delete($assoc['UsersVocabularySentences']['id'], false)) {
            throw new RuntimeException(__('Unable to remove association'));
        }
    }
    
}
?>